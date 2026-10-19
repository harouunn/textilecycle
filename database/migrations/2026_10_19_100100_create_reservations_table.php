<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations : un client réserve un vêtement du catalogue pour venir le retirer.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('vetement_id')->constrained('vetements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date_retrait');
            $table->enum('creneau', ['matin', 'apres_midi']);
            $table->string('telephone', 20);
            $table->text('message')->nullable();
            $table->enum('statut', ['en_attente', 'confirmee', 'retiree', 'annulee'])->default('en_attente');
            $table->text('motif_annulation')->nullable();
            $table->timestamp('confirmee_le')->nullable();
            $table->timestamp('retiree_le')->nullable();
            $table->timestamp('annulee_le')->nullable();
            $table->timestamps();

            $table->index('statut');
            $table->index(['vetement_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
