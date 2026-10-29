<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // Ex: 'L' pour Lokossa, 'P' pour Porto-Novo
            $table->string('nom');
            $table->unsignedInteger('capacite'); // Capacité maximale physique
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres');
    }
};