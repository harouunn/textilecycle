<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dons : statuts du workflow (planifié, distribué, annulé), archivage, champs d'analyse et index de filtrage.
 * Le statut devient une chaîne : la liste des valeurs est portée par l'enum App\Enums\Dons\DonStatut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dons', function (Blueprint $table) {
            $table->string('statut', 20)->default('propose')->change();
        });

        Schema::table('dons', function (Blueprint $table) {
            $table->unsignedTinyInteger('score_risque')->nullable()->after('statut');
            $table->json('ia_analyse')->nullable()->after('score_risque');
            $table->softDeletes();
            $table->index(['association_id', 'statut']);
        });

        // Déjà créé par la migration d'origine sur les bases existantes.
        if (! Schema::hasIndex('dons', ['statut'])) {
            Schema::table('dons', fn (Blueprint $table) => $table->index('statut'));
        }

        // Les dons créés avant l'historique reçoivent leur étape de création.
        DB::table('don_historiques')->insertUsing(
            ['don_id', 'user_id', 'ancien_statut', 'nouveau_statut', 'commentaire', 'created_at', 'updated_at'],
            DB::table('dons')->select('id', DB::raw('NULL'), DB::raw('NULL'), 'statut', DB::raw('NULL'), 'created_at', 'created_at')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('don_historiques')->whereColumn('don_historiques.don_id', 'dons.id')),
        );
    }

    public function down(): void
    {
        // MySQL sert la clé étrangère association_id avec l'index composé : il lui faut un index simple avant de le supprimer.
        if (! Schema::hasIndex('dons', ['association_id'])) {
            Schema::table('dons', fn (Blueprint $table) => $table->index('association_id'));
        }

        Schema::table('dons', function (Blueprint $table) {
            $table->dropIndex(['association_id', 'statut']);
            $table->dropSoftDeletes();
            $table->dropColumn(['score_risque', 'ia_analyse']);
        });

        // Les nouveaux statuts sont ramenés à l'ancien statut le plus proche avant de restaurer l'enum.
        DB::table('dons')->where('statut', 'planifie')->update(['statut' => 'accepte']);
        DB::table('dons')->where('statut', 'distribue')->update(['statut' => 'recu']);
        DB::table('dons')->where('statut', 'annule')->update(['statut' => 'refuse']);

        Schema::table('dons', function (Blueprint $table) {
            $table->enum('statut', ['propose', 'accepte', 'recu', 'refuse'])->default('propose')->change();
        });
    }
};
