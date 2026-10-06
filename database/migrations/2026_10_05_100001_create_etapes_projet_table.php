<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapes_projet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_upcycling_id')->constrained('projets_upcycling')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('titre');
            $table->text('contenu');
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->unique(['projet_upcycling_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes_projet');
    }
};
