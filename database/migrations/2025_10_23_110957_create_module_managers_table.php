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
        Schema::create('module_managers', function (Blueprint $table) {
            $table->id();
            $table->string('module_name')->unique();
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->string('display_name_singular');
            $table->string('resource_type');
            $table->string('identifier_field')->default('id');
            $table->string('identifier_type')->default('number');
            $table->boolean('requires_auth')->default(true);
            $table->string('route_path');
            $table->json('fields');
            $table->boolean('enabled')->default(true);
            $table->boolean('dev_mode')->default(false);
            $table->json('translations')->nullable();
            $table->json('actions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_managers');
    }
};
