<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Les vêtements existants restent publiés (« approuve »).
     */
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->string('moderation', 20)->default('approuve')->after('statut')->index();
            $table->text('motif_refus')->nullable()->after('moderation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropIndex(['moderation']);
            $table->dropColumn(['moderation', 'motif_refus']);
        });
    }
};
