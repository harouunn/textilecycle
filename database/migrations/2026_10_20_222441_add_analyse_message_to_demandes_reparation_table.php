<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_reparation', function (Blueprint $table) {
            $table->text('analyse_message')->nullable()->after('analysed_at');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_reparation', function (Blueprint $table) {
            $table->dropColumn('analyse_message');
        });
    }
};
