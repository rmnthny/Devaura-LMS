<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audit', function (Blueprint $table): void {
            $table->increments('audit_id');
            $table->unsignedInteger('conducted_by');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->enum('audit_status', ['In Progress', 'Completed', 'Cancelled'])->default('In Progress');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreign('conducted_by')->references('account_id')->on('user_account')->restrictOnDelete();
        });

        Schema::create('inventory_audit_item', function (Blueprint $table): void {
            $table->increments('audit_item_id');
            $table->unsignedInteger('audit_id');
            $table->unsignedInteger('book_id');
            $table->enum('expected_status', ['Available', 'Borrowed', 'Lost', 'Damaged']);
            $table->enum('verification_status', ['Present', 'Missing', 'Not Checked'])->default('Not Checked');
            $table->enum('observed_condition', ['Good', 'Damaged'])->nullable();
            $table->enum('discrepancy_type', ['Missing', 'Damaged', 'Status Mismatch', 'Other'])->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
            $table->foreign('audit_id')->references('audit_id')->on('inventory_audit')->cascadeOnDelete();
            $table->foreign('book_id')->references('book_id')->on('book')->restrictOnDelete();
            $table->unique(['audit_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_item');
        Schema::dropIfExists('inventory_audit');
    }
};
