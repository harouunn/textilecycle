<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projets_upcycling', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->string('vetement_origine');
            $table->string('resultat');
            $table->enum('difficulte', ['facile', 'moyen', 'difficile']);
            $table->unsignedInteger('duree_minutes');
            $table->text('materiel_necessaire');
            $table->string('photo_avant')->nullable();
            $table->string('photo_apres')->nullable();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->timestamps();

            $table->index(['statut', 'difficulte']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projets_upcycling');
    }
};
