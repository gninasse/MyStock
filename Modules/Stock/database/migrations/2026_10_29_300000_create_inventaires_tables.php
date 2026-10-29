<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->date('date_inventaire');
            $table->string('commentaire', 500)->nullable();
            $table->foreignId('user_id')->constrained('users');
            // Document d'ajustement généré à la validation (aucun si pas d'écart)
            $table->foreignId('document_id')->nullable()->constrained('documents');
            $table->timestamps();
            $table->index(['magasin_id', 'date_inventaire']);
        });

        Schema::create('lignes_inventaire', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('inventaires')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->restrictOnDelete();
            $table->integer('stock_theorique');
            $table->unsignedInteger('quantite_reelle');
            $table->integer('ecart');
            $table->unique(['inventaire_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_inventaire');
        Schema::dropIfExists('inventaires');
    }
};
