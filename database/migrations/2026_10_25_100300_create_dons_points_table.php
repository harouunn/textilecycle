<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points de gamification gagnés par les donateurs. Une même raison ne rapporte qu'une fois par don.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dons_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('don_id')->nullable()->constrained('dons')->nullOnDelete();
            $table->integer('points');
            $table->string('raison');
            $table->timestamps();

            $table->unique(['don_id', 'raison']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dons_points');
    }
};
