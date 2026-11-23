<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['day_id', 'day_name'])]
class ScheduleDay extends Model
{
    use HasFactory;

    protected $table = 'schedule_day';
    protected $primaryKey = 'day_id';
    public $timestamps = false;

    public $incrementing = false;
}
