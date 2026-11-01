<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lots et péremption. Le stock total reste dans `stocks` ; `stocks_lots` en donne
     * la part identifiée par lot (le reste est « sans lot »). Chaque ligne de mouvement
     * est ventilée par lot dans `lignes_lots` : saisie à l'entrée, FEFO à la sortie.
     */
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->restrictOnDelete();
            $table->string('numero', 50);
            $table->date('date_peremption')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'numero']);
        });

        Schema::create('stocks_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->restrictOnDelete();
            $table->unsignedInteger('quantite')->default(0);
            $table->timestamps();
            $table->unique(['magasin_id', 'lot_id']);
        });

        Schema::create('lignes_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_mouvement_id')->constrained('lignes_mouvement')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->unique(['ligne_mouvement_id', 'lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_lots');
        Schema::dropIfExists('stocks_lots');
        Schema::dropIfExists('lots');
    }
};
