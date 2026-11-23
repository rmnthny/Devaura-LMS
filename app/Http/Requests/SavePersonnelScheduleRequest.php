<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePersonnelScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'personnel_id' => ['required', 'integer', 'exists:library_personnel,personnel_id'],
            'day_id' => ['required', 'integer', 'exists:schedule_day,day_id'],
            'period_id' => ['required', 'integer', 'exists:schedule_period,period_id'],
        ];
    }
}
