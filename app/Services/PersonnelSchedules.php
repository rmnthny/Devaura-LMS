<?php

namespace App\Services;

use App\Models\LibraryPersonnel;
use App\Models\PersonnelSchedule;
use App\Models\SchedulePeriod;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonnelSchedules
{
    public function manageablePersonnel(User $actor): Builder
    {
        $roles = [];
        if ($actor->can('schedules.manage-staff')) {
            $roles[] = Rbac::STAFF;
        }
        if ($actor->can('schedules.manage-admin')) {
            $roles[] = Rbac::ADMIN;
        }

        return $this->eligiblePersonnel()->whereHas('user', fn (Builder $account) => $account->role($roles));
    }

    public function visiblePersonnel(User $actor): Builder
    {
        return $this->eligiblePersonnel()->where(fn (Builder $query) => $query
            ->where('account_id', $actor->getKey())
            ->orWhereIn('personnel_id', $this->manageablePersonnel($actor)->select('personnel_id')));
    }

    private function eligiblePersonnel(): Builder
    {
        return LibraryPersonnel::whereHas('user', fn (Builder $account) => $account
            ->where('account_status', User::STATUS_ACTIVE)->role(Rbac::PERSONNEL_ROLES));
    }

    public function save(User $actor, array $data, ?array $original = null): array
    {
        try {
            return DB::transaction(function () use ($actor, $data, $original): array {
                $person = $this->eligiblePersonnel()->lockForUpdate()->find($data['personnel_id']);
                if (! $person) {
                    throw ValidationException::withMessages(['personnel_id' => 'Select an active library personnel account.']);
                }
                abort_unless($this->manageablePersonnel($actor)->whereKey($person->getKey())->exists(), 403);

                $this->assertNoWholeDayConflict($data, $original);

                if ($original === null) {
                    PersonnelSchedule::query()->insert($data);
                } else {
                    abort_unless($this->manageablePersonnel($actor)->lockForUpdate()->find($original['personnel_id']), 403);
                    $schedule = PersonnelSchedule::where($original)->lockForUpdate();
                    abort_unless($schedule->first(), 404);
                    $schedule->update($data);
                }

                activity('personnel_schedule')->causedBy($actor)->performedOn($person)
                    ->event($original === null ? 'created' : 'updated')
                    ->withProperties(['previous' => $original, 'schedule' => $data])
                    ->log('Saved a personnel schedule');

                return $data;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['period_id' => 'This person already has that period scheduled on this day.']);
        }
    }

    public function delete(User $actor, array $key): void
    {
        DB::transaction(function () use ($actor, $key): void {
            $person = $this->manageablePersonnel($actor)->lockForUpdate()->find($key['personnel_id']);
            abort_unless($person, 403);
            abort_unless(PersonnelSchedule::where($key)->delete(), 404);
            activity('personnel_schedule')->causedBy($actor)->performedOn($person)->event('deleted')
                ->withProperties(['schedule' => $key])->log('Removed a personnel schedule');
        });
    }

    private function assertNoWholeDayConflict(array $data, ?array $original): void
    {
        $wholeDayId = SchedulePeriod::where('period_name', 'Whole Day')->value('period_id');
        if (! $wholeDayId) {
            return;
        }

        $query = PersonnelSchedule::where('personnel_id', $data['personnel_id'])
            ->where('day_id', $data['day_id']);

        if ($original !== null) {
            $query->where(fn ($q) => $q
                ->where('personnel_id', '!=', $original['personnel_id'])
                ->orWhere('day_id', '!=', $original['day_id'])
                ->orWhere('period_id', '!=', $original['period_id']));
        }

        $existing = $query->pluck('period_id');
        if ($existing->isEmpty()) {
            return;
        }

        if ((int) $data['period_id'] === (int) $wholeDayId || $existing->contains($wholeDayId)) {
            throw ValidationException::withMessages([
                'period_id' => 'Whole Day cannot be combined with Morning or Afternoon on the same day.',
            ]);
        }
    }
}
