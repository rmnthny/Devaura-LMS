<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PersonnelSchedule extends Pivot
{
    use HasFactory;

    protected $table = 'personnel_schedule';
    public $incrementing = false;
    public $timestamps = false;

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(LibraryPersonnel::class, 'personnel_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ScheduleDay::class, 'day_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(SchedulePeriod::class, 'period_id');
    }
}
