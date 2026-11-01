<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paramètres de l'application modifiables à l'écran (définitions : config/parametres.php)
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 60)->unique();
            $table->text('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
