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
        Schema::table('module_managers', function (Blueprint $table) {
            $table->string('github_repository_slug')->nullable()->after('route_path');
            $table->string('github_branch')->nullable()->after('github_repository_slug');
            $table->string('github_commit_sha')->nullable()->after('github_branch');
            $table->timestamp('github_pushed_at')->nullable()->after('github_commit_sha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_managers', function (Blueprint $table) {
            $table->dropColumn(['github_repository_slug', 'github_branch', 'github_commit_sha', 'github_pushed_at']);
        });
    }
};
