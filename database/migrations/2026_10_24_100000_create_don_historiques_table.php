<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des statuts d'un don : une ligne par création ou transition (DonWorkflowService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('don_id')->constrained('dons')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ancien_statut', 20)->nullable();
            $table->string('nouveau_statut', 20);
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_historiques');
    }
};
