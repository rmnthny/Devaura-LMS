<div class="row g-2">
    <div class="col-md">
        <label class="form-label" for="{{ $prefix }}-person">Personnel</label>
        <select class="form-select" id="{{ $prefix }}-person" name="personnel_id" required>
            @foreach ($personnel as $person)
                <option value="{{ $person->getKey() }}" @selected($schedule?->personnel_id === $person->getKey())>{{ $person->fullName() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md">
        <label class="form-label" for="{{ $prefix }}-day">Day</label>
        <select class="form-select" id="{{ $prefix }}-day" name="day_id" required>
            @foreach ($days as $day)
                <option value="{{ $day->getKey() }}" @selected($schedule?->day_id === $day->getKey())>{{ $day->day_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md">
        <label class="form-label" for="{{ $prefix }}-period">Period</label>
        <select class="form-select" id="{{ $prefix }}-period" name="period_id" required>
            @foreach ($periods as $period)
                <option value="{{ $period->getKey() }}" @selected($schedule?->period_id === $period->getKey())>{{ $period->period_name }}</option>
            @endforeach
        </select>
    </div>
</div>
