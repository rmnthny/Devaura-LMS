<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_period', function (Blueprint $table) {
            $table->tinyIncrements('period_id');
            $table->string('period_name', 20)->unique();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_period');
    }
};
