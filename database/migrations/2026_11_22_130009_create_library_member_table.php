<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_member', function (Blueprint $table) {
            $table->increments('member_id');
            $table->unsignedInteger('account_id')->unique();
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_initial', 5)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('deleted_at')->nullable();
            $table->foreign('account_id')->references('account_id')->on('user_account')->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('library_member');
    }
};
