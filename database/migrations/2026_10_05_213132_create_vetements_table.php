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
        Schema::create('vetements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->enum('taille', ['XS', 'S', 'M', 'L', 'XL', 'XXL']);
            $table->enum('genre', ['homme', 'femme', 'enfant', 'unisexe']);
            $table->string('matiere');
            $table->enum('etat', ['neuf', 'tres_bon', 'bon', 'use', 'a_reparer']);
            $table->string('photo')->nullable();
            $table->enum('statut', ['disponible', 'reserve', 'donne', 'recycle'])->default('disponible');
            $table->date('date_depot');
            $table->timestamps();

            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vetements');
    }
};
