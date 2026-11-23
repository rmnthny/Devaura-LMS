<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePersonnelScheduleRequest;
use App\Models\PersonnelSchedule;
use App\Models\ScheduleDay;
use App\Models\SchedulePeriod;
use App\Services\PersonnelSchedules;
use Illuminate\Http\Request;

class PersonnelScheduleController extends Controller
{
    public function index(Request $request, PersonnelSchedules $service)
    {
        $dayFilter = $request->integer('day_id') ?: null;
        $personnelFilter = $request->integer('personnel_id') ?: null;

        $schedules = PersonnelSchedule::with(['personnel', 'day', 'period'])
            ->whereIn('personnel_id', $service->visiblePersonnel($request->user())->select('personnel_id'))
            ->when($dayFilter, fn ($q) => $q->where('day_id', $dayFilter))
            ->when($personnelFilter, fn ($q) => $q->where('personnel_id', $personnelFilter))
            ->orderBy('day_id')->orderBy('period_id')->orderBy('personnel_id')
            ->paginate(25)->withQueryString();

        return $request->expectsJson() ? $schedules : view('schedules.index', [
            'schedules' => $schedules,
            'personnel' => $service->manageablePersonnel($request->user())->orderBy('last_name')->get(),
            'days' => ScheduleDay::orderBy('day_id')->get(),
            'periods' => SchedulePeriod::orderBy('period_id')->get(),
            'dayFilter' => $dayFilter,
            'personnelFilter' => $personnelFilter,
        ]);
    }

    public function store(SavePersonnelScheduleRequest $request, PersonnelSchedules $service)
    {
        $schedule = $service->save($request->user(), $request->validated());

        return $request->expectsJson()
            ? response()->json($schedule, 201)
            : to_route('schedules.index')->with('status', 'Schedule added.');
    }

    public function update(SavePersonnelScheduleRequest $request, int $personnel, int $day, int $period, PersonnelSchedules $service)
    {
        $schedule = $service->save($request->user(), $request->validated(), $this->key($personnel, $day, $period));

        return $request->expectsJson()
            ? response()->json($schedule)
            : to_route('schedules.index')->with('status', 'Schedule updated.');
    }

    public function destroy(Request $request, int $personnel, int $day, int $period, PersonnelSchedules $service)
    {
        $service->delete($request->user(), $this->key($personnel, $day, $period));

        return $request->expectsJson()
            ? response()->noContent()
            : to_route('schedules.index')->with('status', 'Schedule removed.');
    }

    private function key(int $personnel, int $day, int $period): array
    {
        return ['personnel_id' => $personnel, 'day_id' => $day, 'period_id' => $period];
    }
}
