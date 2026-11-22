<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_schedule', function (Blueprint $table) {
            $table->unsignedInteger('personnel_id');
            $table->unsignedTinyInteger('day_id');
            $table->unsignedTinyInteger('period_id');
            $table->primary(['personnel_id', 'day_id', 'period_id']);
            $table->foreign('personnel_id')->references('personnel_id')->on('library_personnel')->restrictOnDelete();
            $table->foreign('day_id')->references('day_id')->on('schedule_day')->restrictOnDelete();
            $table->foreign('period_id')->references('period_id')->on('schedule_period')->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_schedule');
    }
};
