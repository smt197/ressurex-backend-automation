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
        Schema::create('github_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('protected')->default(false);
            $table->string('commit_sha')->nullable();
            $table->text('commit_message')->nullable();
            $table->timestamp('commit_date')->nullable();

            // Lien vers le repository
            $table->foreignId('github_repository_id')
                ->constrained('github_repositories')
                ->onDelete('cascade');

            $table->timestamps();

            // Index et contrainte unique
            $table->unique(['github_repository_id', 'name']);
            $table->index('protected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_branches');
    }
};
