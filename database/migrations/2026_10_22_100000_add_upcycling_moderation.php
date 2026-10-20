<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projets_upcycling', function (Blueprint $table) {
            // Une chaîne évite les contraintes enum différentes entre MySQL et SQLite.
            $table->string('statut', 20)->default('brouillon')->change();
            $table->text('commentaire_refus')->nullable();
            $table->timestamp('soumis_le')->nullable();
            $table->timestamp('decision_le')->nullable();
            $table->foreignId('decision_par')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projets_upcycling', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decision_par');
            $table->dropColumn(['commentaire_refus', 'soumis_le', 'decision_le']);
        });
        // Ne pas rétrécir les statuts : cela détruirait les valeurs soumis/rejete.
    }
};
