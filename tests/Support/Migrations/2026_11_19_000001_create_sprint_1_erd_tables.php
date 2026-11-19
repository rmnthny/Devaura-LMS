<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department', function (Blueprint $table) {
            $table->id('department_id');
            $table->string('department_name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('library_member', function (Blueprint $table) {
            $table->id('member_id');
            $table->foreignId('account_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('department', 'department_id')->nullOnDelete();
            $table->enum('member_role', ['Student', 'Faculty']);
            $table->string('reference_number', 50)->unique();
            $table->string('member_code', 30)->nullable()->unique();
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_initial', 5)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('library_personnel', function (Blueprint $table) {
            $table->id('personnel_id');
            $table->foreignId('department_id')->nullable()->constrained('department', 'department_id')->nullOnDelete();
            $table->foreignId('account_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_initial', 5)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('library_personnel');
        Schema::dropIfExists('library_member');
        Schema::dropIfExists('department');
    }
};
