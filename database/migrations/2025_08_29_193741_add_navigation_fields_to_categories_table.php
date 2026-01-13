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
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('name');
            $table->string('icon')->nullable()->after('order');
            $table->enum('navigation_type', ['subheading', 'dropdown'])->default('subheading')->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['order', 'icon', 'navigation_type']);
        });
    }
};
