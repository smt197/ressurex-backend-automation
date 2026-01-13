<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('message');
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_mime_type')->nullable()->after('file_name');
            $table->unsignedInteger('file_size')->nullable()->after('file_mime_type');
            $table->enum('message_type', ['text', 'file', 'image'])->default('text')->after('file_size');
            $table->text('message')->nullable()->change(); // Rendre le message textuel optionnel si c'est un fichier
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'file_mime_type', 'file_size', 'message_type']);
            $table->text('message')->nullable(false)->change(); // Revenir à l'état précédent si nécessaire
        });
    }
};
