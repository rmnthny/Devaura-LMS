<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book', function (Blueprint $table) {
            $table->increments('book_id');
            $table->unsignedInteger('genre_id');
            $table->string('isbn', 13)->nullable();
            $table->string('book_title', 255);
            $table->enum('book_status', ['Available', 'Borrowed', 'Lost', 'Damaged'])->default('Available');
            $table->string('book_qr_value', 50)->unique();
            $table->date('date_published')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
            $table->foreign('genre_id')->references('genre_id')->on('genre')->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('book');
    }
};
