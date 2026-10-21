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
            $table->json('analyse_vision')->nullable()->after('admin_notifie');
            $table->json('analyse_texte')->nullable()->after('analyse_vision');
            $table->timestamp('analysed_at')->nullable()->after('analyse_texte');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_reparation', function (Blueprint $table) {
            $table->dropColumn(['analyse_vision', 'analyse_texte', 'analysed_at']);
        });
    }
};
