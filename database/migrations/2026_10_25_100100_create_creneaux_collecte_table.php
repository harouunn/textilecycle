<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Créneaux de collecte à domicile proposés par une association, avec un nombre maximal de dons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creneaux_collecte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->unsignedSmallInteger('capacite_max');
            $table->timestamps();

            $table->index(['association_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux_collecte');
    }
};
