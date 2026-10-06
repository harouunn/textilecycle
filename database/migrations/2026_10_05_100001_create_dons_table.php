<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type_article', ['vetements_homme', 'vetements_femme', 'vetements_enfant', 'chaussures', 'linge_maison', 'accessoires']);
            $table->unsignedInteger('quantite');
            $table->decimal('poids_kg', 8, 2)->nullable();
            $table->enum('etat_general', ['bon', 'moyen', 'a_recycler']);
            $table->enum('mode_remise', ['depot_sur_place', 'collecte_a_domicile']);
            $table->date('date_remise');
            $table->string('adresse_collecte')->nullable();
            $table->enum('statut', ['propose', 'accepte', 'recu', 'refuse'])->default('propose')->index();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dons');
    }
};
