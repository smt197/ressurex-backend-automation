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
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->nullable();
            $table->string('name');
            $table->string('full_name')->unique();
            $table->string('owner');
            $table->text('description')->nullable();
            $table->string('html_url');
            $table->string('default_branch')->default('main');
            $table->boolean('private')->default(false);
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->bigInteger('github_id')->nullable()->unique();
            $table->timestamp('last_synced_at')->nullable();

            // Lien vers l'utilisateur propriétaire
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('owner');
            $table->index('private');
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
