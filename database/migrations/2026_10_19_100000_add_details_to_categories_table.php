<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations : slug (URL lisible), couleur (badge), ordre d'affichage et activation.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('nom');
            $table->string('couleur', 7)->default('#3d7a4f')->after('icone');
            $table->unsignedSmallInteger('ordre')->default(0)->after('couleur');
            $table->boolean('active')->default(true)->after('ordre');
        });

        // Les catégories existantes reçoivent un slug et un ordre à partir de leur nom.
        DB::table('categories')->orderBy('nom')->get(['id', 'nom'])->each(function ($categorie, $index) {
            DB::table('categories')->where('id', $categorie->id)->update([
                'slug' => Str::slug($categorie->nom).'-'.$categorie->id,
                'ordre' => $index + 1,
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
            $table->index(['active', 'ordre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['active', 'ordre']);
            $table->dropColumn(['slug', 'couleur', 'ordre', 'active']);
        });
    }
};
