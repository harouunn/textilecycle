<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dons : campagne et créneau de collecte choisis, référence du reçu (attribuée quand le don est reçu,
 * par DonWorkflowService ; les dons déjà reçus sont complétés ici).
 * Supprimer une campagne ou un créneau vide simplement le lien : le don reste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->foreignId('campagne_id')->nullable()->after('association_id')->constrained('campagnes_collecte')->nullOnDelete();
            $table->foreignId('creneau_id')->nullable()->after('campagne_id')->constrained('creneaux_collecte')->nullOnDelete();
            $table->string('reference', 20)->nullable()->unique()->after('id');
        });

        // Les dons déjà reçus avant cette version reçoivent leur référence (année de leur dernière mise à jour) :
        // leur reçu PDF sera généré au premier téléchargement.
        DB::table('dons')->whereIn('statut', ['recu', 'distribue'])->whereNull('reference')->orderBy('id')
            ->each(fn ($don) => DB::table('dons')->where('id', $don->id)->update([
                'reference' => sprintf('DON-%d-%06d', Carbon::parse($don->updated_at ?? $don->created_at)->year, $don->id),
            ]));
    }

    public function down(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
            $table->dropConstrainedForeignId('creneau_id');
            $table->dropConstrainedForeignId('campagne_id');
        });
    }
};
