<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_conversations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Utiliser UUID pour correspondre à votre frontend 'id: string'
            $table->string('name')->nullable(); // Pour les chats de groupe
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};
