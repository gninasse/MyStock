@extends('core::layouts.master')

@use('Modules\Stock\Models\Document')
@php
    $libelles = Document::LIBELLES;
    $statuts = Document::STATUTS;
    $estTransfert = $document->type === Document::TRANSFERT;
    if ($estTransfert && $document->statut === Document::VALIDE) {
        $statuts[Document::VALIDE] = ['Reçu', 'success'];
    }
    $permissionSaisie = ['ENTREE' => 'stock.entrees.saisir', 'SORTIE' => 'stock.sorties.saisir', 'TRANSFERT' => 'stock.transferts.saisir'][$document->type] ?? null;
    $avecReception = $estTransfert && $document->lignes->whereNotNull('quantite_recue')->isNotEmpty();
    $avecLots = $document->lignes->contains(fn ($l) => $l->lots->isNotEmpty());
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
        @if ($document->estBrouillon() && $permissionSaisie && auth()->user()->can($permissionSaisie))
            <a href="{{ route('stock.documents.edit', $document->id) }}" class="btn btn-primary btn-sm ms-auto"><i class="fas fa-edit"></i> Modifier</a>
        @endif
        @if ($peutRetourner)
            <a href="{{ route('stock.documents.retour', $document->id) }}" class="btn btn-outline-primary btn-sm ms-auto"><i class="fas fa-undo"></i> Retourner au magasin source</a>
        @endif
        @if ($peutReceptionner)
            <a href="{{ route('stock.transferts.reception', $document->id) }}" class="btn btn-success btn-sm ms-auto"><i class="fas fa-dolly"></i> Réceptionner</a>
        @endif
        @if ($peutAnnulerTransfert)
            <button type="button" class="btn btn-outline-danger btn-sm {{ $peutReceptionner ? 'ms-2' : 'ms-auto' }}" id="btn-annuler-transfert"
                    data-url="{{ route('stock.documents.annuler-transfert', $document->id) }}" data-reference="{{ $document->reference }}">
                <i class="fas fa-undo"></i> Annuler le transfert
            </button>
        @endif
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Date du mouvement</dt><dd class="col-sm-9">{{ $document->date_mouvement->format('d/m/Y') }}</dd>
            <dt class="col-sm-3">{{ $estTransfert ? 'Magasin source' : 'Magasin' }}</dt><dd class="col-sm-9">{{ $document->magasin->libelle }} ({{ $document->magasin->code }})</dd>
            @if ($estTransfert)
                <dt class="col-sm-3">Magasin destinataire</dt><dd class="col-sm-9">{{ $document->magasinDestination?->libelle }} ({{ $document->magasinDestination?->code }})</dd>
            @endif
            @if ($document->type === 'SORTIE' && $document->service)
                <dt class="col-sm-3">Bénéficiaire</dt>
                <dd class="col-sm-9">{{ $document->beneficiaire }} <small class="text-muted">— {{ $document->direction?->libelle }} / {{ $document->service->libelle }}</small></dd>
            @endif
            <dt class="col-sm-3">Commentaire</dt><dd class="col-sm-9">{{ $document->commentaire ?: '—' }}</dd>
            <dt class="col-sm-3">Saisi par</dt><dd class="col-sm-9">{{ $document->utilisateur->full_name }} le {{ $document->created_at->format('d/m/Y H:i') }}</dd>
            @if ($document->validated_at)
                <dt class="col-sm-3">{{ $estTransfert ? 'Expédié par' : 'Validé par' }}</dt><dd class="col-sm-9">{{ $document->validateur?->full_name }} le {{ $document->validated_at->format('d/m/Y H:i:s') }}</dd>
            @endif
            @if ($document->recu_at)
                <dt class="col-sm-3">Reçu par</dt><dd class="col-sm-9">{{ $document->recepteur?->full_name }} le {{ $document->recu_at->format('d/m/Y H:i:s') }}</dd>
            @endif
            @if ($document->motif_ecart)
                <dt class="col-sm-3">Motif de l'écart</dt><dd class="col-sm-9">{{ $document->motif_ecart }}</dd>
            @endif
            @if ($document->documentOrigine)
                <dt class="col-sm-3">{{ $estTransfert ? 'Retour du transfert' : 'Transfert d\'origine' }}</dt>
                <dd class="col-sm-9"><a href="{{ route('stock.documents.show', $document->documentOrigine->id) }}">{{ $document->documentOrigine->reference }}</a></dd>
            @endif
            @if ($document->documentsLies->isNotEmpty())
                <dt class="col-sm-3">Mouvements liés</dt>
                <dd class="col-sm-9">
                    @foreach ($document->documentsLies as $lie)
                        <a href="{{ route('stock.documents.show', $lie->id) }}">{{ $lie->reference ?? 'brouillon' }}</a> <small class="text-muted">({{ $lie->libelle_type }})</small>@if (! $loop->last), @endif
                    @endforeach
                </dd>
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
            <thead><tr><th>Code</th><th>Désignation</th><th class="text-end">{{ $estTransfert ? 'Expédié' : 'Quantité' }}</th>@if ($avecReception)<th class="text-end">Reçu</th><th class="text-end">Écart</th>@endif<th>Unité</th>@if ($avecLots)<th>Lots</th>@endif</tr></thead>
            <tbody>
                @foreach ($document->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->article->code }}</td>
                        <td>{{ $ligne->article->designation }}</td>
                        <td class="text-end">{{ $ligne->sens > 0 ? '+' : '−' }}{{ $ligne->quantite }}</td>
                        @if ($avecReception)
                            <td class="text-end">{{ $ligne->quantite_recue }}</td>
                            <td class="text-end {{ $ligne->quantite_recue < $ligne->quantite ? 'text-danger fw-bold' : '' }}">{{ $ligne->quantite_recue - $ligne->quantite ?: '' }}</td>
                        @endif
                        <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                        @if ($avecLots)
                            <td class="small">
                                @foreach ($ligne->lots as $ventilation)
                                    <div>{{ $ventilation->lot->libelle }} : {{ $ventilation->quantite }}</div>
                                @endforeach
                                @if ($ligne->lots->sum('quantite') < $ligne->quantite)
                                    <div class="text-muted">Sans lot : {{ $ligne->quantite - $ligne->lots->sum('quantite') }}</div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/stock/document.js') }}"></script>
@endpush
