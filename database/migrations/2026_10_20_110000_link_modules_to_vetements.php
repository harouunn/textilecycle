<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le cycle du vêtement relie les quatre gestions du projet :
 *   vetements ──< demandes_reparation   (le vêtement est envoyé en réparation dans un atelier)
 *   vetements ──< projets_upcycling     (le vêtement est transformé en nouvel objet)
 *   vetements ──< dons                  (le vêtement est donné à une association)
 * La colonne est facultative : réparations, projets et dons peuvent toujours exister sans vêtement du catalogue.
 * Si le vêtement est supprimé, le lien est simplement vidé (nullOnDelete) : l'historique des autres gestions reste.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['demandes_reparation', 'projets_upcycling', 'dons'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $nom) {
            Schema::table($nom, function (Blueprint $table) {
                $table->foreignId('vetement_id')->nullable()->after('id')->constrained('vetements')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $nom) {
            Schema::table($nom, function (Blueprint $table) {
                $table->dropConstrainedForeignId('vetement_id');
            });
        }
    }
};
