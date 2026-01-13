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
            $table->json('roles')->nullable()->after('requires_auth');
        });

        // Set default value for existing records
        \DB::table('module_managers')->whereNull('roles')->update(['roles' => json_encode(['user'])]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_managers', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
