<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_account', function (Blueprint $table) {
            $table->increments('account_id');
            $table->string('reference_number', 50)->unique();
            $table->string('username', 50)->unique();
            $table->string('email', 254)->unique();
            $table->string('password_hash', 255);
            $table->enum('account_status', ['Active', 'Inactive', 'Locked', 'Pending']);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
            $table->string('user_qr_value', 50)->unique();
            // Roles come from Spatie model_has_roles; no role_id column here.
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('user_account');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
