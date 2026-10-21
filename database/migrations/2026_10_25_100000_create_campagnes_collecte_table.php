<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campagnes de collecte : une association fixe un objectif (kg) sur une période.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes_collecte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->json('type_articles_cibles')->nullable();
            $table->unsignedInteger('objectif_kg');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 20)->default('brouillon');
            $table->string('image')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes_collecte');
    }
};
