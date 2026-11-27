<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_repair', function (Blueprint $table): void {
            $table->increments('repair_id');
            $table->unsignedInteger('transaction_id')->unique();
            $table->date('scheduled_on');
            $table->dateTime('completed_at')->nullable();
            $table->enum('outcome', ['Available', 'Damaged'])->nullable();
            $table->timestamps();
            $table->foreign('transaction_id')->references('transaction_id')->on('borrow_transaction')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_repair');
    }
};
