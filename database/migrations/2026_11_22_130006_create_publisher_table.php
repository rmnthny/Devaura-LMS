<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher', function (Blueprint $table) {
            $table->increments('publisher_id');
            $table->string('publisher_name', 255);
            $table->enum('publisher_type', ['Individual', 'Organization'])->nullable();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('publisher');
    }
};
