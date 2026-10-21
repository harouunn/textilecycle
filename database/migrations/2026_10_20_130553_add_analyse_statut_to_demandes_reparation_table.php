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
        Schema::table('demandes_reparation', function (Blueprint $table) {
            // Statut de l'analyse IA : null (pas encore analysé), "ok" (succès), "erreur" (échec)
            $table->enum('analyse_statut', ['ok', 'erreur'])->nullable()->after('diagnostic');
            
            // Booléen pour savoir si l'admin a déjà été notifié pour cette demande
            $table->boolean('admin_notifie')->default(false)->after('analyse_statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes_reparation', function (Blueprint $table) {
            $table->dropColumn(['analyse_statut', 'admin_notifie']);
        });
    }
};
