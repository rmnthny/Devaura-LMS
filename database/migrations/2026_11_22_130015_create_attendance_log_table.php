<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('account_id')->nullable();
            $table->unsignedInteger('visitor_id')->nullable();
            $table->string('user_role', 20);
            $table->dateTime('time_in_at');
            $table->dateTime('time_out_at')->nullable();
            $table->foreign('account_id')->references('account_id')->on('user_account')->restrictOnDelete();
            $table->foreign('visitor_id')->references('visitor_id')->on('walk_in_visitor')->restrictOnDelete();
        });

        // Requires MySQL 8.0.16+ for enforced CHECK constraints.
        DB::statement("ALTER TABLE `attendance_log` ADD CONSTRAINT `chk_attendance_identity` CHECK ((`account_id` IS NOT NULL AND `visitor_id` IS NULL) OR (`account_id` IS NULL AND `visitor_id` IS NOT NULL))");
        DB::statement("ALTER TABLE `attendance_log` ADD CONSTRAINT `chk_attendance_time_order` CHECK (`time_out_at` IS NULL OR `time_out_at` >= `time_in_at`)");
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_log');
    }
};
