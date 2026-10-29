@extends('core::layouts.master')

@php
    $libelles = ['ENTREE' => 'Entrée', 'SORTIE' => 'Sortie', 'AJUSTEMENT' => 'Ajustement'];
    $statuts = ['BROUILLON' => ['Brouillon', 'secondary'], 'VALIDE' => ['Validé', 'primary'], 'ANNULE' => ['Annulé', 'dark']];
@endphp

@section('header', $libelles[$document->type].' '.($document->reference ?? '(brouillon)'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.documents.index') }}">Documents</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $document->reference ?? 'Brouillon' }}</li>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">{{ $libelles[$document->type] }} {{ $document->reference }}</h3>
        <span class="badge text-bg-{{ $statuts[$document->statut][1] }} ms-2">{{ $statuts[$document->statut][0] }}</span>
        @if ($document->estBrouillon() && auth()->user()->can($document->type === 'SORTIE' ? 'stock.sorties.saisir' : 'stock.entrees.saisir'))
            <a href="{{ route('stock.documents.edit', $document->id) }}" class="btn btn-primary btn-sm ms-auto"><i class="fas fa-edit"></i> Modifier</a>
        @endif
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Date du mouvement</dt><dd class="col-sm-9">{{ $document->date_mouvement->format('d/m/Y') }}</dd>
            <dt class="col-sm-3">Magasin</dt><dd class="col-sm-9">{{ $document->magasin->libelle }} ({{ $document->magasin->code }})</dd>
            @if ($document->type === 'SORTIE' && $document->service)
                <dt class="col-sm-3">Bénéficiaire</dt>
                <dd class="col-sm-9">{{ $document->beneficiaire }} <small class="text-muted">— {{ $document->direction?->libelle }} / {{ $document->service->libelle }}</small></dd>
            @endif
            <dt class="col-sm-3">Commentaire</dt><dd class="col-sm-9">{{ $document->commentaire ?: '—' }}</dd>
            <dt class="col-sm-3">Saisi par</dt><dd class="col-sm-9">{{ $document->utilisateur->full_name }} le {{ $document->created_at->format('d/m/Y H:i') }}</dd>
            @if ($document->validated_at)
                <dt class="col-sm-3">Validé par</dt><dd class="col-sm-9">{{ $document->validateur?->full_name }} le {{ $document->validated_at->format('d/m/Y H:i:s') }}</dd>
            @endif
            @if ($document->inventaire)
                <dt class="col-sm-3">Inventaire</dt>
                <dd class="col-sm-9"><a href="{{ route('stock.inventaires.show', $document->inventaire->id) }}">Inventaire du {{ $document->inventaire->date_inventaire->format('d/m/Y') }}</a></dd>
            @endif
            @if ($document->documentAnnule)
                <dt class="col-sm-3">Annule</dt>
                <dd class="col-sm-9"><a href="{{ route('stock.documents.show', $document->documentAnnule->id) }}">{{ $document->documentAnnule->reference }}</a></dd>
            @endif
            @if ($document->annulation)
                <dt class="col-sm-3">Annulé par</dt>
                <dd class="col-sm-9"><a href="{{ route('stock.documents.show', $document->annulation->id) }}">{{ $document->annulation->reference }}</a></dd>
            @endif
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Articles ({{ $document->lignes->count() }})</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Code</th><th>Désignation</th><th class="text-end">Quantité</th><th>Unité</th></tr></thead>
            <tbody>
                @foreach ($document->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->article->code }}</td>
                        <td>{{ $ligne->article->designation }}</td>
                        <td class="text-end">{{ $ligne->sens > 0 ? '+' : '−' }}{{ $ligne->quantite }}</td>
                        <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
