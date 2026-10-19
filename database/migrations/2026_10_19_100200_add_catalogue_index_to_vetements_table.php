<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations : index couvrant la requête du catalogue (approuvés, disponibles, par catégorie).
     */
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->index(['moderation', 'statut', 'categorie_id'], 'vetements_catalogue_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropIndex('vetements_catalogue_index');
        });
    }
};
