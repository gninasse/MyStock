@extends('core::layouts.master')

@section('header', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@section('content')
@php
    $types = [
        'ENTREE' => ['Entrée', 'success'], 'SORTIE' => ['Sortie', 'danger'], 'AJUSTEMENT' => ['Ajustement', 'info'],
        'TRANSFERT' => ['Transfert', 'warning'], 'RECEPTION' => ['Réception', 'success'], 'REINTEGRATION' => ['Réintégration', 'secondary'],
    ];
@endphp

{{-- Raccourcis selon le rôle --}}
<div class="mb-3 d-flex flex-wrap gap-2">
    @can('stock.entrees.saisir')
        <a href="{{ route('stock.saisie.create', 'entrees') }}" class="btn btn-success"><i class="fas fa-arrow-down"></i> Nouvelle entrée</a>
    @endcan
    @can('stock.sorties.saisir')
        <a href="{{ route('stock.saisie.create', 'sorties') }}" class="btn btn-danger"><i class="fas fa-arrow-up"></i> Nouvelle sortie</a>
    @endcan
    @can('stock.transferts.saisir')
        <a href="{{ route('stock.saisie.create', 'transferts') }}" class="btn btn-warning"><i class="fas fa-truck"></i> Nouveau transfert</a>
    @endcan
    @can('stock.inventaires.saisir')
        <a href="{{ route('stock.inventaires.create') }}" class="btn btn-primary"><i class="fas fa-clipboard-check"></i> Saisir un inventaire</a>
    @endcan
</div>

@if ($nombrePeremptions)
    <div class="alert alert-warning d-flex align-items-center">
        <i class="fas fa-hourglass-half me-2"></i>
        {{ $nombrePeremptions }} lot(s) en stock périmé(s) ou qui périment dans les {{ \Modules\Referentiel\Services\Parametres::get('peremption_alerte_jours') }} jours.
        <a href="{{ route('etats.peremptions') }}" class="ms-auto">Voir les péremptions</a>
    </div>
@endif

{{-- Indicateurs --}}
<div class="row">
    <div class="col-md-4">
        <div class="small-box text-bg-primary">
            <div class="inner"><h3>{{ $indicateurs['articles_en_stock'] }}</h3><p>Articles en stock</p></div>
            <i class="small-box-icon bi bi-box-seam"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-success">
            <div class="inner"><h3>{{ $indicateurs['magasins'] }}</h3><p>Magasins</p></div>
            <i class="small-box-icon bi bi-shop"></i>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-warning">
            <div class="inner"><h3>{{ $indicateurs['mouvements_du_jour'] }}</h3><p>Mouvements validés aujourd'hui</p></div>
            <i class="small-box-icon bi bi-arrow-left-right"></i>
        </div>
    </div>
</div>

<div class="row">
    @if ($alertes !== null)
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">⚠️ Articles sous le seuil minimum</h3>
                @if ($nombreAlertes)
                    <span class="badge text-bg-danger ms-2">{{ $nombreAlertes }}</span>
                    @can('etats.stock-magasin')
                        <a href="{{ route('etats.stock-magasin', ['envoye' => 1, 'sans_stock' => 1, 'alerte' => 1]) }}" class="ms-auto small">Tout voir</a>
                    @endcan
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Article</th><th>Magasin</th><th class="text-end">Stock</th><th class="text-end">Minimum</th></tr></thead>
                    <tbody>
                        @forelse ($alertes as $alerte)
                            <tr class="{{ $alerte->niveau === 'sous' ? 'table-danger' : 'table-warning' }}">
                                <td>{{ $alerte->designation }} <small class="text-muted">({{ $alerte->code }})</small></td>
                                <td>{{ $alerte->magasin }}</td>
                                <td class="text-end">{{ $alerte->quantite }} {{ $unitesMesure[$alerte->unite_mesure] ?? '' }}</td>
                                <td class="text-end">{{ $alerte->stock_minimum }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Aucun article en alerte 👍</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($derniersMouvements !== null)
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">🕐 Derniers mouvements</h3>
                <a href="{{ route('stock.documents.index') }}" class="ms-auto small">Liste des documents</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Référence</th><th>Type</th><th>Magasin</th><th>Lignes</th><th>Validé le</th></tr></thead>
                    <tbody>
                        @forelse ($derniersMouvements as $document)
                            <tr>
                                <td><a href="{{ route('stock.documents.show', $document->id) }}">{{ $document->reference }}</a></td>
                                <td><span class="badge text-bg-{{ $types[$document->type][1] }}">{{ $types[$document->type][0] }}</span></td>
                                <td>{{ $document->magasin->libelle }}</td>
                                <td>{{ $document->lignes_count }}</td>
                                <td>{{ $document->validated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucun mouvement validé</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($transfertsEnTransit !== null)
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">🚚 Transferts en transit</h3>
                @can('etats.transferts')
                    <a href="{{ route('etats.transferts') }}" class="ms-auto small">État des transferts</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Transfert</th><th>Depuis</th><th>Vers</th><th>Expédié</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($transfertsEnTransit as $transfert)
                            <tr>
                                <td><a href="{{ route('stock.documents.show', $transfert->id) }}">{{ $transfert->reference }}</a></td>
                                <td>{{ $transfert->magasin->libelle }}</td>
                                <td>{{ $transfert->magasinDestination->libelle }}</td>
                                <td title="{{ $transfert->validated_at->format('d/m/Y H:i') }}">{{ $transfert->validated_at->diffForHumans() }}</td>
                                <td class="text-end">
                                    @if (in_array($transfert->magasin_destination_id, $magasinsVisibles) && auth()->user()->can('stock.transferts.receptionner'))
                                        <a href="{{ route('stock.transferts.reception', $transfert->id) }}" class="btn btn-success btn-sm py-0">Réceptionner</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Aucun transfert en transit</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($dernieresActions !== null)
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">📋 Dernières actions</h3>
                <a href="{{ route('cores.activities.index') }}" class="ms-auto small">Journal d'audit</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($dernieresActions as $action)
                    <li class="list-group-item small">
                        <span class="text-muted">{{ $action->created_at->format('d/m/Y H:i') }}</span>
                        — {{ $action->causer?->full_name ?? 'Système' }}
                        — <strong>{{ $action->description }}</strong>
                        <span class="text-muted">({{ $action->module ?? $action->log_name }})</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune action</li>
                @endforelse
            </ul>
        </div>
    </div>
    @endif
</div>
@stop
