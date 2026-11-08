{{-- Document d'exemple : montre l'en-tête officiel enregistré sur un bon fictif. --}}
@extends('core::pdf.layout')

@php
    $entete = app(\Modules\Referentiel\Services\EnteteDocuments::class);
    $filigrane = 'SPÉCIMEN';
@endphp

@section('titre', $entete->libelle('TRANSFERT'))
@section('reference', $entete->reference('TRF-'.now()->format('Ymd').'-001'))
@section('sous-titre', 'Document d\'exemple — aperçu de l\'en-tête')
@section('pied', 'Aperçu de l\'en-tête')

@section('contenu')
    <table class="infos">
        <tr>
            <td class="bloc" style="width: 49%"><div class="etiquette">Magasin expéditeur</div><div class="valeur">Magasin central</div></td>
            <td class="espace"></td>
            <td class="bloc" style="width: 49%"><div class="etiquette">Magasin destinataire</div><div class="valeur">Pharmacie des urgences</div></td>
        </tr>
    </table>

    <h2 class="section">Articles</h2>
    <table class="lignes">
        <thead><tr><th style="width: 6%">#</th><th>Article</th><th class="num" style="width: 16%">Quantité</th><th style="width: 16%">Unité</th></tr></thead>
        <tbody>
            <tr><td>1</td><td>Gants d'examen non stériles, taille M</td><td class="num">50</td><td>Boîte</td></tr>
            <tr><td>2</td><td>Seringues 5 ml</td><td class="num">200</td><td>Unité</td></tr>
            <tr><td>3</td><td>Solution hydroalcoolique 500 ml</td><td class="num">24</td><td>Flacon</td></tr>
        </tbody>
    </table>

    @include('core::pdf.partials.fait-a')
    <table class="signatures">
        <tr>
            <td class="case" style="width: 32%"><div class="role">Magasin expéditeur</div><div class="ligne">Remis le — nom et signature</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Transporteur</div><div class="ligne">Nom et signature</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Magasin destinataire</div><div class="ligne">Reçu le — nom et signature</div></td>
        </tr>
    </table>
@endsection
