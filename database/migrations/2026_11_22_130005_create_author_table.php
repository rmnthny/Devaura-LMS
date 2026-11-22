<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author', function (Blueprint $table) {
            $table->increments('author_id');
            $table->string('last_name', 50)->nullable();
            $table->string('first_name', 50)->nullable();
            $table->string('middle_initial', 5)->nullable();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('author');
    }
};
