<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations : vérification de l'adresse e-mail par code à 6 chiffres.
     * Le code est stocké haché (comme un mot de passe), avec sa date d'expiration et le nombre d'essais.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code_verification')->nullable()->after('email_verified_at');
            $table->timestamp('code_verification_expire_le')->nullable()->after('code_verification');
            $table->unsignedTinyInteger('code_verification_essais')->default(0)->after('code_verification_expire_le');
        });

        // Les comptes existants restent utilisables : on considère leur adresse comme vérifiée.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['code_verification', 'code_verification_expire_le', 'code_verification_essais']);
        });
    }
};
