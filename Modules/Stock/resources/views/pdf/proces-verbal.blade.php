@extends('core::pdf.layout')

@php
    $lignes = $inventaire->lignes->sortBy(fn ($l) => ($l->article->categorie?->libelle ?? '').'|'.$l->article->code);
    $positifs = $lignes->where('ecart', '>', 0);
    $negatifs = $lignes->where('ecart', '<', 0);
    $sansEcart = $lignes->count() - $positifs->count() - $negatifs->count();
    $fiabilite = $lignes->count() ? round($sansEcart * 100 / $lignes->count(), 1) : 100;
    $formater = fn ($n) => number_format($n, 0, ',', ' ');
@endphp

@section('titre', app(\Modules\Referentiel\Services\EnteteDocuments::class)->libelle('PV_INVENTAIRE'))
@section('sous-titre', $inventaire->magasin->libelle.' · '.$inventaire->date_inventaire->format('d/m/Y'))
@section('pied', 'PV d\'inventaire '.$inventaire->magasin->code.' du '.$inventaire->date_inventaire->format('d/m/Y'))

@section('contenu')
    <table class="infos">
        <tr>
            <td class="bloc" style="width: 25%">
                <div class="etiquette">Articles inventoriés</div>
                <div class="valeur">{{ $lignes->count() }}</div>
                <div class="detail">{{ $sansEcart }} sans écart</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 25%">
                <div class="etiquette">Fiabilité du stock</div>
                <div class="valeur">{{ str_replace('.', ',', $fiabilite) }} %</div>
                <div class="detail">Articles conformes au théorique</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 25%">
                <div class="etiquette">Excédents</div>
                <div class="valeur positif">+{{ $formater($positifs->sum('ecart')) }}</div>
                <div class="detail">{{ $positifs->count() }} article(s)</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 25%">
                <div class="etiquette">Manquants</div>
                <div class="valeur negatif">{{ $formater($negatifs->sum('ecart')) }}</div>
                <div class="detail">{{ $negatifs->count() }} article(s)</div>
            </td>
        </tr>
    </table>

    <h2 class="section">Informations</h2>
    <dl class="meta">
        <dt>Magasin</dt><dd>{{ $inventaire->magasin->libelle }} ({{ $inventaire->magasin->code }})</dd>
        <dt>Date de l'inventaire</dt><dd>{{ $inventaire->date_inventaire->format('d/m/Y') }}</dd>
        <dt>Validé par</dt><dd>{{ $inventaire->utilisateur?->full_name }} le {{ $inventaire->created_at->format('d/m/Y H:i') }}</dd>
        <dt>Ajustement de stock</dt><dd>{{ $inventaire->document?->reference ?? 'Aucun écart : stock inchangé' }}</dd>
        @if ($inventaire->commentaire)<dt>Commentaire</dt><dd>{{ $inventaire->commentaire }}</dd>@endif
    </dl>

    <h2 class="section">Écarts constatés</h2>
    <table class="lignes">
        <thead>
            <tr>
                <th style="width: 10%">Code</th>
                <th>Désignation</th>
                <th style="width: 16%">Catégorie</th>
                <th style="width: 8%">Unité</th>
                <th class="num" style="width: 9%">Théorique</th>
                <th class="num" style="width: 9%">Compté</th>
                <th class="num" style="width: 8%">Écart</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lignes->where('ecart', '!=', 0) as $ligne)
                <tr>
                    <td>{{ $ligne->article->code }}</td>
                    <td>{{ $ligne->article->designation }}</td>
                    <td class="muet">{{ $ligne->article->categorie?->libelle }}</td>
                    <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                    <td class="num">{{ $formater($ligne->stock_theorique) }}</td>
                    <td class="num">{{ $formater($ligne->quantite_reelle) }}</td>
                    <td class="num {{ $ligne->ecart < 0 ? 'negatif' : 'positif' }}">{{ $ligne->ecart > 0 ? '+' : '' }}{{ $formater($ligne->ecart) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="centre muet">Aucun écart : le stock physique est conforme au stock théorique.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($sansEcart)
        <p class="muet" style="margin-top: 6px">{{ $sansEcart }} article(s) compté(s) sans écart ne figurent pas dans ce tableau.</p>
    @endif

    @include('core::pdf.partials.fait-a', ['date' => $inventaire->date_inventaire])
    <table class="signatures">
        <tr>
            <td class="case" style="width: 32%"><div class="role">La commission d'inventaire</div><div class="ligne">Noms et signatures</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Responsable du magasin</div><div class="ligne">Date et signature</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Direction</div><div class="ligne">Visa</div></td>
        </tr>
    </table>
@endsection
