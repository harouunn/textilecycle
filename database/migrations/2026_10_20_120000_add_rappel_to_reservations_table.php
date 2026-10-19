<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations : date d'envoi du rappel (la veille du retrait), pour ne l'envoyer qu'une fois.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('rappel_envoye_le')->nullable()->after('annulee_le');
            $table->index(['statut', 'date_retrait']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['statut', 'date_retrait']);
            $table->dropColumn('rappel_envoye_le');
        });
    }
};
