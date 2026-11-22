<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walk_in_visitor', function (Blueprint $table) {
            $table->increments('visitor_id');
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_initial', 5)->nullable();
            $table->string('email', 254)->unique();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('walk_in_visitor');
    }
};
