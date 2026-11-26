<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_log', function (Blueprint $table): void {
            $table->index(['time_out_at', 'account_id'], 'attendance_account_open_idx');
            $table->index(['time_out_at', 'visitor_id'], 'attendance_visitor_open_idx');
            $table->index(['time_out_at', 'time_in_at'], 'attendance_coverage_idx');
            $table->index(['time_in_at', 'log_id'], 'attendance_visit_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_log', function (Blueprint $table): void {
            foreach (['attendance_account_open_idx', 'attendance_visitor_open_idx', 'attendance_coverage_idx', 'attendance_visit_order_idx'] as $index) {
                $table->dropIndex($index);
            }
        });
    }
};
