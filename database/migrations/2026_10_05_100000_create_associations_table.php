<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description');
            $table->string('adresse');
            $table->string('ville')->index();
            $table->string('telephone', 30);
            $table->string('email');
            $table->string('site_web')->nullable();
            $table->string('logo')->nullable();
            $table->text('besoins');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('associations');
    }
};
