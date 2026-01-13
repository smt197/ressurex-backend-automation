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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->nullable();
            $table->string('name');
            $table->string('allias_name')->nullable();
            $table->text('description')->nullable();
            $table->text('size')->nullable();
            // Lien vers l'utilisateur propriétaire
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Si l'utilisateur est supprimé, ses documents le sont aussi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fichiers');
    }
};
