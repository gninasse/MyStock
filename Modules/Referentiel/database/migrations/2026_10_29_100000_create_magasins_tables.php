<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magasins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('libelle', 100);
            $table->string('localisation', 200)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Magasins rattachés à un utilisateur (périmètre d'accès)
        Schema::create('magasin_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['magasin_id', 'user_id']);
        });

        Schema::create('responsables_magasin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magasin_id')->constrained('magasins')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('role_responsable', 20); // PRINCIPAL ou ADJOINT
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['magasin_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsables_magasin');
        Schema::dropIfExists('magasin_user');
        Schema::dropIfExists('magasins');
    }
};
