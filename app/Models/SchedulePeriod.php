<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['period_name'])]
class SchedulePeriod extends Model
{
    use HasFactory;

    protected $table = 'schedule_period';
    protected $primaryKey = 'period_id';
    public $timestamps = false;
}
