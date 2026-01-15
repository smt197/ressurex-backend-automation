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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_manager_id')->nullable()->constrained()->onDelete('set null');
            $table->string('module_slug');
            $table->string('branch_name');
            $table->string('dokploy_deployment_id')->nullable();
            $table->enum('status', ['pending', 'building', 'deploying', 'success', 'failed'])->default('pending');
            $table->text('message')->nullable();
            $table->json('logs')->nullable();
            $table->integer('progress')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index(['module_slug', 'status']);
            $table->index('dokploy_deployment_id');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
