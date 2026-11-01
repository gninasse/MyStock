<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transferts entre magasins : un TRANSFERT débite la source (statut EN_TRANSIT),
     * une RECEPTION crédite la destination, une REINTEGRATION recrédite la source
     * (écart de réception ou annulation avant réception).
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('magasin_destination_id')->nullable()->after('magasin_id')->constrained('magasins')->restrictOnDelete();
            // Réception / réintégration / retour : document TRANSFERT d'origine
            $table->foreignId('document_origine_id')->nullable()->after('document_annule_id')->constrained('documents');
            $table->string('motif_ecart', 500)->nullable()->after('commentaire');
            $table->timestamp('recu_at')->nullable()->after('valide_par');
            $table->foreignId('recu_par')->nullable()->after('recu_at')->constrained('users');
            $table->index(['magasin_destination_id', 'statut']);
        });

        Schema::table('lignes_mouvement', function (Blueprint $table) {
            // Quantité effectivement reçue à destination (lignes de TRANSFERT)
            $table->unsignedInteger('quantite_recue')->nullable()->after('quantite');
        });
    }

    public function down(): void
    {
        Schema::table('lignes_mouvement', function (Blueprint $table) {
            $table->dropColumn('quantite_recue');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['magasin_destination_id', 'statut']);
            $table->dropConstrainedForeignId('recu_par');
            $table->dropColumn('recu_at');
            $table->dropColumn('motif_ecart');
            $table->dropConstrainedForeignId('document_origine_id');
            $table->dropConstrainedForeignId('magasin_destination_id');
        });
    }
};
