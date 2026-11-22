<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_transaction', function (Blueprint $table) {
            $table->increments('transaction_id');
            $table->unsignedInteger('borrower_id');
            $table->unsignedInteger('borrow_processed_by');
            $table->unsignedInteger('return_processed_by')->nullable();
            $table->unsignedInteger('book_id');
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->date('due_date');
            $table->enum('transaction_status', ['Borrowed', 'Returned', 'Overdue', 'Lost'])->default('Borrowed');
            $table->string('transaction_code', 30)->unique();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->foreign('borrower_id')->references('account_id')->on('user_account')->restrictOnDelete();
            $table->foreign('borrow_processed_by')->references('account_id')->on('user_account')->restrictOnDelete();
            $table->foreign('return_processed_by')->references('account_id')->on('user_account')->restrictOnDelete();
            $table->foreign('book_id')->references('book_id')->on('book')->restrictOnDelete();
        });

        // Staff-assisted borrowing only; personnel cannot process their own borrowing or return.
        // Requires MySQL 8.0.16+ for enforced CHECK constraints.
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_no_self_borrow` CHECK (`borrow_processed_by` <> `borrower_id`)");
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_no_self_return` CHECK (`return_processed_by` IS NULL OR `return_processed_by` <> `borrower_id`)");
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_borrow_date_order` CHECK (`due_date` >= `borrow_date`)");
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_return_date_order` CHECK (`return_date` IS NULL OR `return_date` >= `borrow_date`)");
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_return_fields_together` CHECK ((`return_date` IS NULL AND `return_processed_by` IS NULL) OR (`return_date` IS NOT NULL AND `return_processed_by` IS NOT NULL))");
        DB::statement("ALTER TABLE `borrow_transaction` ADD CONSTRAINT `chk_returned_status_complete` CHECK (`transaction_status` <> 'Returned' OR (`return_date` IS NOT NULL AND `return_processed_by` IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_transaction');
    }
};
