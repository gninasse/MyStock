@extends('core::pdf.layout')

@php
    $total = $groupes->sum(fn ($g) => $g->count());
    $colonnes = 6 + ($avecTheorique ? 1 : 0);
@endphp

@section('titre', 'Fiche de comptage')
@section('sous-titre', $magasin->libelle.' ('.$magasin->code.')')
@section('pied', 'Fiche de comptage '.$magasin->code)

@push('styles')
<style>
    .case-saisie { border: .8px solid #9aa3ac; height: 15px; border-radius: 2px; }
    table.lignes td { padding-top: 6px; padding-bottom: 6px; }
</style>
@endpush

@section('contenu')
    <table class="infos">
        <tr>
            <td class="bloc" style="width: 34%">
                <div class="etiquette">Magasin</div>
                <div class="valeur">{{ $magasin->libelle }}</div>
                <div class="detail">{{ $magasin->code }}@if ($magasin->localisation) · {{ $magasin->localisation }}@endif</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 33%">
                <div class="etiquette">Périmètre</div>
                <div class="valeur">{{ $categorie ?? 'Toutes les catégories' }}</div>
                <div class="detail">{{ $total }} article(s) à compter</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 33%">
                <div class="etiquette">Date du comptage</div>
                <div class="valeur">____ / ____ / ________</div>
                <div class="detail">{{ $avecTheorique ? 'Stock théorique imprimé' : 'Comptage à l\'aveugle (stock théorique masqué)' }}</div>
            </td>
        </tr>
    </table>

    <div class="note">
        Compter physiquement chaque article et inscrire la quantité trouvée, y compris <b>0</b> si l'article est absent.
        Noter en observation les produits abîmés, périmés ou non référencés.
    </div>

    <table class="lignes" style="margin-top: 10px">
        <thead>
            <tr>
                <th class="centre" style="width: 4%">N°</th>
                <th style="width: 10%">Code</th>
                <th>Désignation</th>
                <th style="width: 8%">Unité</th>
                @if ($avecTheorique)<th class="num" style="width: 9%">Théorique</th>@endif
                <th class="centre" style="width: 13%">Quantité comptée</th>
                <th style="width: 22%">Observations</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 0; @endphp
            @forelse ($groupes as $libelle => $articles)
                <tr class="groupe"><th colspan="{{ $colonnes }}">{{ $libelle }} — {{ $articles->count() }} article(s)</th></tr>
                @foreach ($articles as $article)
                    <tr>
                        <td class="centre muet">{{ ++$n }}</td>
                        <td>{{ $article['code'] }}</td>
                        <td>
                            {{ $article['designation'] }}
                            @if ($lots->has($article['id']))
                                <div class="muet">Lots : {{ $lots[$article['id']]->map(fn ($l) => $l->numero.($l->date_peremption ? ' ('.\Illuminate\Support\Carbon::parse($l->date_peremption)->format('m/Y').')' : ''))->implode(', ') }}</div>
                            @endif
                        </td>
                        <td>{{ $unitesMesure[$article['unite_mesure']] ?? $article['unite_mesure'] }}</td>
                        @if ($avecTheorique)<td class="num">{{ $article['stock_theorique'] }}</td>@endif
                        <td><div class="case-saisie"></div></td>
                        <td><div class="case-saisie"></div></td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $colonnes }}" class="centre muet">Aucun article à inventorier</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td class="case" style="width: 32%"><div class="role">Compté par</div><div class="ligne">Nom, date et signature</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Contrôlé par</div><div class="ligne">Nom, date et signature</div></td>
            <td class="espace"></td>
            <td class="case" style="width: 32%"><div class="role">Responsable du magasin</div><div class="ligne">Visa</div></td>
        </tr>
    </table>
@endsection
