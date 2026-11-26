<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\User;
use App\Models\WalkInVisitor;
use App\Support\MemberFieldRules;
use App\Support\Rbac;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Attendance
{
    public const OPERATORS = [Rbac::SUPERADMIN, Rbac::ADMIN, Rbac::STAFF];

    public function kioskOpen(?int $excludingAccount = null): bool
    {
        $local = now(config('attendance.timezone'));
        $period = $this->shiftFor($local->format('H:i'));

        // A role change cannot turn an existing member visit into personnel coverage.
        return AttendanceLog::whereNull('time_out_at')
            ->whereIn('user_role', self::OPERATORS)
            ->where('time_in_at', '>=', $local->copy()->startOfDay()->utc())
            ->where('time_in_at', '<=', $local->copy()->utc())
            ->when($excludingAccount !== null, fn ($query) => $query->where('account_id', '<>', $excludingAccount))
            ->whereHas('account', fn ($accounts) => $accounts->whereNull('user_account.deleted_at')
                ->where('account_status', User::STATUS_ACTIVE)
                ->where(function ($coverage) use ($period, $local): void {
                    $coverage->role(Rbac::SUPERADMIN);
                    if ($period !== null) {
                        $coverage->orWhere(fn ($scheduled) => $scheduled->role([Rbac::ADMIN, Rbac::STAFF])
                            ->whereHas('personnel.schedules', fn ($schedules) => $schedules
                                ->whereHas('day', fn ($day) => $day->where('day_name', $local->format('l')))
                                ->whereHas('period', fn ($periods) => $periods->whereIn('period_name', [$period, 'Whole Day']))));
                    }
                }))
            ->exists();
    }

    private function shiftFor(string $time): ?string
    {
        $opens = config('attendance.opens_at');
        $handover = config('attendance.afternoon_at');
        $closes = config('attendance.closes_at');
        foreach ([$opens, $handover, $closes] as $hour) {
            if (! is_string($hour) || ! preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/', $hour)) {
                return null;
            }
        }
        if ($opens >= $handover || $handover >= $closes || $time < $opens || $time >= $closes) {
            return null;
        }

        return $time < $handover ? 'Morning' : 'Afternoon';
    }

    public function record(array $data, ?User $actor, bool $public = false): string
    {
        return $this->locked(function () use ($data, $actor, $public): string {
            if ($public && ! $this->kioskOpen()) {
                $this->fail('The kiosk is closed until the Head Librarian or someone assigned to the current shift times in.');
            }

            $action = $data['action'];
            if ($data['type'] === 'visitor') {
                $visitor = $action === 'in'
                    ? WalkInVisitor::firstOrCreate(['email' => strtolower($data['email'])], [
                        'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                        'middle_initial' => MemberFieldRules::formatMiddleInitial($data['middle_initial'] ?? null),
                    ])
                    : WalkInVisitor::where('email', strtolower($data['email']))->first();
                if (! $visitor) {
                    $this->fail('There is no open visit for that email.');
                }
                $identity = ['visitor_id' => $visitor->getKey()];
                $role = Rbac::VISITOR;
            } else {
                $accounts = User::query()->where('account_status', User::STATUS_ACTIVE);
                if ($data['type'] === 'self') {
                    $accounts->whereKey($actor->getKey());
                } else {
                    $code = $data['identifier'];
                    $accounts->where(fn ($query) => $query->where('reference_number', $code)->orWhere('user_qr_value', $code));
                }
                $matches = $accounts->limit(2)->lockForUpdate()->get();
                $account = $matches->first();
                $eligible = $public ? Rbac::MEMBER_ROLES : [...Rbac::MEMBER_ROLES, ...Rbac::PERSONNEL_ROLES];
                if ($matches->count() !== 1 || ! $account->hasAnyRole($eligible)) {
                    $this->fail('No eligible account matches that code.');
                }
                $identity = ['account_id' => $account->getKey()];
                $role = $account->getRoleNames()->first();
            }

            $visit = AttendanceLog::where($identity)->whereNull('time_out_at')->lockForUpdate()->first();
            if ($action === 'in') {
                if ($visit) {
                    return 'Already timed in. Your original time was kept.';
                }
                $visit = AttendanceLog::create($identity + [
                    'user_role' => $role, 'time_in_at' => now(), 'visit_purpose' => $data['visit_purpose'] ?? null,
                ]);
            } else {
                if (! $visit) {
                    $this->fail('There is no open visit to time out.');
                }
                $visit->update(['time_out_at' => now()]);
            }
            activity('attendance')->causedBy($actor)->performedOn($visit)
                ->event($action === 'in' ? 'timed_in' : 'timed_out')->log('Recorded library attendance');

            if ($action === 'out' && ! $public && in_array($role, self::OPERATORS, true) && ! $this->kioskOpen()) {
                $remaining = AttendanceLog::whereNull('time_out_at')->count();

                return "Time Out recorded. The kiosk is closed. Open visits: {$remaining}.";
            }

            return $action === 'in' ? 'Time In recorded.' : 'Time Out recorded.';
        });
    }

    public function forceTimeOut(int $id, User $actor, string $reason): void
    {
        $this->locked(function () use ($id, $actor, $reason): void {
            $visit = AttendanceLog::lockForUpdate()->findOrFail($id);
            if ($visit->time_out_at) {
                $this->fail('This visit is already closed.');
            }
            $visit->update(['time_out_at' => now()]);
            activity('attendance')->causedBy($actor)->performedOn($visit)->event('forced_time_out')
                ->withProperties(['reason' => $reason])->log('Closed an open visit');
        });
    }

    private function locked(Closure $callback): mixed
    {
        // Keep the coverage check and attendance update together across kiosk requests.
        try {
            return Cache::store('database')->lock('library-attendance', 30)->block(5, fn () => DB::transaction($callback));
        } catch (LockTimeoutException) {
            $this->fail('Attendance is busy. Please try again.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['attendance' => $message]);
    }
}
