<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_day', function (Blueprint $table) {
            // Fixed lookup IDs (e.g., 1=Monday ... 7=Sunday); no AUTO_INCREMENT in ERD.
            $table->unsignedTinyInteger('day_id')->primary();
            $table->string('day_name', 10)->unique();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_day');
    }
};
