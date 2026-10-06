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
        Schema::create('demandes_reparation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->enum('type_vetement', ['haut', 'pantalon', 'robe_jupe', 'maille', 'veste_manteau', 'autre']);
            $table->text('description')->nullable();
            $table->string('photo')->nullable();

            // Diagnostic automatique, recalculé à chaque modification de la demande
            $table->json('reparations');
            $table->decimal('cout_estime', 8, 2);
            $table->unsignedSmallInteger('delai_estime_jours');
            $table->text('diagnostic');

            // Traitement par l'atelier
            $table->enum('statut', ['en_attente', 'acceptee', 'en_cours', 'terminee', 'refusee'])->default('en_attente');
            $table->decimal('cout_final', 8, 2)->nullable();
            $table->date('date_prevue')->nullable();
            $table->text('reponse_atelier')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_reparation');
    }
};
