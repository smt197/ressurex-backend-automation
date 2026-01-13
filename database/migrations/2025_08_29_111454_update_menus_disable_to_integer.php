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
        Schema::table('menus', function (Blueprint $table) {
            // Convertir la colonne boolean en tinyInteger
            $table->tinyInteger('disable')->default(1)->change(); // 1 = actif, 0 = désactivé
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Revenir au type boolean
            $table->boolean('disable')->default(false)->change();
        });
    }
};
