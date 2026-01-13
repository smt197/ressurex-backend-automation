<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlugToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Il est important que le slug soit unique.
            // Mettez-le nullable si vous avez des utilisateurs existants qui n'auront pas de slug initialement.
            // Le package gérera l'unicité en ajoutant des suffixes (-1, -2) si nécessaire.
            $table->string('slug')->unique()->nullable()->after('email'); // ou une autre position pertinente
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
