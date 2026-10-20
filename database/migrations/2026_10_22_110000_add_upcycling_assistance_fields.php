<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projets_upcycling', fn (Blueprint $table) => $table->json('traductions')->nullable());
        Schema::table('etapes_projet', function (Blueprint $table) {
            $table->text('outils_necessaires')->nullable();
            $table->unsignedInteger('duree_minutes')->nullable();
            $table->text('conseils_securite')->nullable();
            $table->json('traductions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projets_upcycling', fn (Blueprint $table) => $table->dropColumn('traductions'));
        Schema::table('etapes_projet', fn (Blueprint $table) => $table->dropColumn(['outils_necessaires', 'duree_minutes', 'conseils_securite', 'traductions']));
    }
};
