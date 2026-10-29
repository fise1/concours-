<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained()->cascadeOnDelete();
            $table->foreignId('centre_id')->constrained()->cascadeOnDelete();
            $table->string('npi', 10);
            $table->string('numero_table', 20); // Ex: L-0001, P-0464
            $table->enum('statut', [
                'valide', 
                'en_attente_verification', 
                'en_attente_validation_capacite'
            ])->default('valide');
            $table->timestamp('cree_le')->useCurrent();
            $table->timestamps();

            // Index et contraintes d'unicité pour bloquer les doublons
            $table->unique(['concours_id', 'npi'], 'uk_concours_npi');
            $table->index(['centre_id', 'numero_table'], 'idx_centre_numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};