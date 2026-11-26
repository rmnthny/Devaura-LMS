<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Services\Attendance;
use App\Support\MemberFieldRules;
use App\Support\Rbac;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function kiosk(Attendance $attendance)
    {
        return response()->view('attendance.kiosk', ['open' => $attendance->kioskOpen()])
            ->header('Cache-Control', 'no-store');
    }

    public function kioskRecord(Request $request, Attendance $attendance)
    {
        try {
            return $this->record($request, $attendance, true);
        } catch (ValidationException $error) {
            if ($request->expectsJson()) {
                throw $error;
            }

            return to_route('kiosk.index')->withErrors($error->errors(), 'kiosk');
        }
    }

    public function assistedRecord(Request $request, Attendance $attendance)
    {
        return $this->record($request, $attendance, false);
    }

    private function record(Request $request, Attendance $attendance, bool $public)
    {
        $visitor = $request->input('type') === 'visitor';
        $visitorIn = $visitor && $request->input('action') === 'in';
        $data = $request->validate([
            'action' => ['required', Rule::in(['in', 'out'])],
            'type' => ['required', Rule::in(['account', 'visitor'])],
            'identifier' => ['exclude_unless:type,account', 'required', 'string', 'max:50'],
            'email' => ['exclude_unless:type,visitor', ...MemberFieldRules::email()],
            'first_name' => [Rule::excludeIf(! $visitorIn), ...MemberFieldRules::names()['first_name']],
            'last_name' => [Rule::excludeIf(! $visitorIn), ...MemberFieldRules::names()['last_name']],
            'middle_initial' => [Rule::excludeIf(! $visitorIn), ...MemberFieldRules::names()['middle_initial']],
            'visit_purpose' => [Rule::excludeIf(! $visitorIn), 'required', 'string', 'max:255'],
        ], MemberFieldRules::messages());

        $message = $attendance->record($data, $public ? null : $request->user(), $public);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return $public ? to_route('kiosk.index')->with('kiosk_status', $message)
            : back()->with('status', $message);
    }

    public function own(Request $request, Attendance $attendance)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['in', 'out'])]]);
        $user = $request->user();
        $message = $attendance->record($data + ['type' => 'self'], $user);
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        $destination = $user->can('attendance.view') ? 'attendance.index' : 'profile.edit';

        return to_route($destination)->with('status', $message);
    }

    public function index(Request $request, Attendance $attendance)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'through' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date'), 'after_or_equal:date')],
            'role' => ['nullable', Rule::in([...Rbac::MEMBER_ROLES, ...Rbac::PERSONNEL_ROLES, Rbac::VISITOR])],
            'status' => ['nullable', Rule::in(['open', 'closed'])],
        ]);
        $timezone = config('attendance.timezone');
        $date = $filters['date'] ?? null;
        if (! $request->has('date') && ! isset($filters['through']) && ($filters['status'] ?? '') !== 'open') {
            $date = now($timezone)->toDateString();
        }
        $start = $date ? Carbon::parse($date, $timezone)->startOfDay() : null;
        $end = isset($filters['through']) ? Carbon::parse($filters['through'], $timezone)->startOfDay()->addDay()
            : $start?->copy()->addDay();
        $visits = AttendanceLog::with(['account.member', 'account.personnel', 'visitor'])
            ->when($start, fn ($query) => $query->where('time_in_at', '>=', $start->copy()->utc()))
            ->when($end, fn ($query) => $query->where('time_in_at', '<', $end->utc()))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('user_role', $role))
            ->when(($filters['status'] ?? '') === 'open', fn ($query) => $query->whereNull('time_out_at'))
            ->when(($filters['status'] ?? '') === 'closed', fn ($query) => $query->whereNotNull('time_out_at'))
            ->latest('time_in_at')->latest('log_id')->paginate(20)->withQueryString();
        $ownVisit = AttendanceLog::where('account_id', $request->user()->getKey())->whereNull('time_out_at')->exists();
        $inside = AttendanceLog::whereNull('time_out_at')->count() - (int) $ownVisit;
        $open = $attendance->kioskOpen();
        $lastOnDuty = $ownVisit && $open && ! $attendance->kioskOpen($request->user()->getKey());

        return view('attendance.index', compact('visits', 'date', 'filters', 'timezone', 'ownVisit', 'open', 'inside', 'lastOnDuty'));
    }

    public function force(Request $request, int $visit, Attendance $attendance)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $attendance->forceTimeOut($visit, $request->user(), $data['reason']);

        return $request->expectsJson() ? response()->noContent() : back()->with('status', 'Visit closed.');
    }
}
