<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('libelle', 100);
            $table->string('unite_par_defaut', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('designation', 200);
            $table->string('unite_mesure', 20);
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            $table->unsignedInteger('stock_minimum')->nullable();
            $table->boolean('actif')->default(true);
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('articles_frequents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
            $table->unique(['magasin_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles_frequents');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
    }
};
