<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des appels IA (page « Journal IA » du back office) : un appel = une ligne, sans le contenu échangé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fonctionnalite', 40);
            $table->unsignedInteger('duree_ms');
            $table->boolean('succes');
            $table->string('erreur')->nullable();
            $table->timestamps();

            $table->index(['fonctionnalite', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
