<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_publisher', function (Blueprint $table) {
            $table->unsignedInteger('book_id');
            $table->unsignedInteger('publisher_id');
            $table->primary(['book_id', 'publisher_id']);
            $table->foreign('book_id')->references('book_id')->on('book')->restrictOnDelete();
            $table->foreign('publisher_id')->references('publisher_id')->on('publisher')->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('book_publisher');
    }
};
