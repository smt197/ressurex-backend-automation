<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 500)->nullable();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('photo', 500)->nullable();
            $table->string('email')->unique();
            $table->date('birthday')->nullable(); // Nouveau champ ajouté ici
            $table->string('phone', 20)->nullable();
            $table->boolean('confirmed')->nullable()->default(false);
            $table->boolean('otp_enabled')->default(false);
            $table->boolean('otp_status_auth')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->integer('session_normal')->default(10800); // Stocke la durée normale de session en minutes 180 heures
            $table->integer('session_expiration')->default(25200); // Stocke la durée prolongée (Remember Me) en minutes 420 heures
            $table->integer('session_user_second')->default(10800);
            $table->rememberToken();
            $table->timestamps();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
