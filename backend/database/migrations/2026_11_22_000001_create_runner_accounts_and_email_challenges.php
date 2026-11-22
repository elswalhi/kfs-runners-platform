<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runner_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->timestamp('email_verified_at');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('team_name', 120)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('runner_email_challenges', function (Blueprint $table) {
            $table->char('session_key', 64)->primary();
            $table->string('email', 190);
            $table->char('code_hash', 64);
            $table->string('event_slug', 200)->nullable();
            $table->string('race_slug', 120)->nullable();
            $table->string('kit', 10)->nullable();
            $table->boolean('return_to_registration')->default(false);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('resend_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runner_email_challenges');
        Schema::dropIfExists('runner_accounts');
    }
};
