<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_conversation_user_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Vous pourriez ajouter des champs comme 'joined_at', 'last_read_message_id', etc.
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversation_user');
    }
};
