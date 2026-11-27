<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_transaction', function (Blueprint $table): void {
            $table->uuid('checkout_request_id')->nullable()->unique();
            $table->index(['borrower_id', 'transaction_status', 'due_date'], 'loans_by_borrower');
            $table->index(['transaction_status', 'due_date'], 'loans_due');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('email_attempted_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', fn (Blueprint $table) => $table->dropColumn(['email_attempted_at', 'email_sent_at']));

        Schema::table('borrow_transaction', function (Blueprint $table): void {
            $table->dropIndex('loans_due');
            $table->dropIndex('loans_by_borrower');
            $table->dropUnique(['checkout_request_id']);
            $table->dropColumn('checkout_request_id');
        });
    }
};
