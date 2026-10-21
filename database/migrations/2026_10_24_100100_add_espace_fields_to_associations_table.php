<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Associations : géolocalisation, capacité d'accueil, responsable (Espace association) et archivage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('ville');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('capacite_mensuelle_kg')->default(0)->after('besoins');
            $table->foreignId('responsable_id')->nullable()->after('active')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        // Déjà créé par la migration d'origine sur les bases existantes.
        if (! Schema::hasIndex('associations', ['ville'])) {
            Schema::table('associations', fn (Blueprint $table) => $table->index('ville'));
        }
    }

    public function down(): void
    {
        Schema::table('associations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsable_id');
            $table->dropSoftDeletes();
            $table->dropColumn(['latitude', 'longitude', 'capacite_mensuelle_kg']);
        });
    }
};
