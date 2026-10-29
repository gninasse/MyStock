<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // ENTREE, SORTIE, AJUSTEMENT
            $table->string('reference', 30)->nullable()->unique(); // attribuée à la validation
            $table->date('date_mouvement');
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            // Bénéficiaire d'une sortie : l'Unité si elle est choisie, sinon le Service
            $table->foreignId('direction_id')->nullable()->constrained('directions')->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->restrictOnDelete();
            $table->foreignId('unite_id')->nullable()->constrained('unites')->restrictOnDelete();
            $table->string('commentaire', 500)->nullable();
            $table->string('statut', 20)->default('BROUILLON'); // BROUILLON, VALIDE, ANNULE
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users');
            $table->foreignId('document_annule_id')->nullable()->constrained('documents');
            $table->timestamps();
            $table->index(['magasin_id', 'statut']);
            $table->index(['type', 'date_mouvement']);
        });

        Schema::create('lignes_mouvement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->smallInteger('sens'); // +1 augmente le stock, -1 le diminue
            $table->timestamps();
            $table->unique(['document_id', 'article_id']);
        });

        // Stock courant par article et par magasin, mis à jour à chaque validation (SFD 12.1)
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('article_id')->constrained('articles')->restrictOnDelete();
            $table->integer('quantite')->default(0);
            $table->timestamps();
            $table->unique(['magasin_id', 'article_id']);
        });

        // Compteur séquentiel par type et par jour pour les références (SFD 12.2)
        Schema::create('compteurs_reference', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe', 3);
            $table->date('jour');
            $table->unsignedInteger('dernier')->default(0);
            $table->unique(['prefixe', 'jour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compteurs_reference');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('lignes_mouvement');
        Schema::dropIfExists('documents');
    }
};
