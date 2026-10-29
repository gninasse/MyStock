@extends('core::layouts.master')

@section('header', 'Inventaire du '.$inventaire->date_inventaire->format('d/m/Y'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.inventaires.index') }}">Inventaires</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $inventaire->date_inventaire->format('d/m/Y') }}</li>
@endsection

@section('content')
@php
    $positifs = $inventaire->lignes->where('ecart', '>', 0)->count();
    $negatifs = $inventaire->lignes->where('ecart', '<', 0)->count();
@endphp
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Magasin</dt><dd class="col-sm-9">{{ $inventaire->magasin->libelle }} ({{ $inventaire->magasin->code }})</dd>
            <dt class="col-sm-3">Validé par</dt><dd class="col-sm-9">{{ $inventaire->utilisateur->full_name }} le {{ $inventaire->created_at->format('d/m/Y H:i:s') }}</dd>
            <dt class="col-sm-3">Commentaire</dt><dd class="col-sm-9">{{ $inventaire->commentaire ?: '—' }}</dd>
            <dt class="col-sm-3">Récapitulatif</dt>
            <dd class="col-sm-9">
                Sans écart : {{ $inventaire->lignes->count() - $positifs - $negatifs }}
                | Écart positif : <span class="text-success">{{ $positifs }}</span>
                | Écart négatif : <span class="text-danger">{{ $negatifs }}</span>
            </dd>
            <dt class="col-sm-3">Ajustement</dt>
            <dd class="col-sm-9">
                @if ($inventaire->document)
                    <a href="{{ route('stock.documents.show', $inventaire->document->id) }}">{{ $inventaire->document->reference }}</a>
                @else
                    Aucun écart, stock inchangé
                @endif
            </dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr><th>Code</th><th>Désignation</th><th>Unité</th><th class="text-end">Stock théorique</th><th class="text-end">Qté réelle</th><th class="text-end">Écart</th></tr>
            </thead>
            <tbody>
                @foreach ($inventaire->lignes->sortBy('article.code') as $ligne)
                    <tr>
                        <td>{{ $ligne->article->code }}</td>
                        <td>{{ $ligne->article->designation }}</td>
                        <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                        <td class="text-end">{{ $ligne->stock_theorique }}</td>
                        <td class="text-end">{{ $ligne->quantite_reelle }}</td>
                        <td class="text-end fw-bold {{ $ligne->ecart > 0 ? 'text-success' : ($ligne->ecart < 0 ? 'text-danger' : 'text-muted') }}">
                            {{ $ligne->ecart > 0 ? '+' : '' }}{{ $ligne->ecart }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
