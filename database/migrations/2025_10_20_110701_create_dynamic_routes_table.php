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
        Schema::create('dynamic_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('uri');
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'])->default('GET');
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->text('description')->nullable();

            // Authentication & Authorization
            $table->boolean('requires_auth')->default(false);
            $table->enum('guard', ['web', 'api'])->default('web');
            $table->json('middleware')->nullable();
            $table->json('permissions')->nullable();
            $table->json('roles')->nullable();

            // Status & Meta
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('guard');
            $table->index(['method', 'uri']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_routes');
    }
};
