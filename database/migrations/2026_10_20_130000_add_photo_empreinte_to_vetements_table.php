<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations : empreinte visuelle de la photo (64 bits en hexadécimal) pour repérer les doublons.
     */
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->string('photo_empreinte', 16)->nullable()->after('photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn('photo_empreinte');
        });
    }
};
