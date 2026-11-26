<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $table = 'attendance_log';

    protected $primaryKey = 'log_id';

    protected $fillable = ['account_id', 'visitor_id', 'user_role', 'visit_purpose', 'time_in_at', 'time_out_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['time_in_at' => 'datetime', 'time_out_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id')->withTrashed();
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(WalkInVisitor::class, 'visitor_id');
    }
}
