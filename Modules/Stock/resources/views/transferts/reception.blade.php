@extends('core::layouts.master')

@section('header', 'Réception du transfert '.$transfert->reference)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.transferts.a-receptionner') }}">À réceptionner</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $transfert->reference }}</li>
@endsection

@section('content')
<div id="page-reception" data-url="{{ route('stock.transferts.receptionner', $transfert->id) }}" data-retour="{{ route('stock.documents.show', $transfert->id) }}">
    <div class="alert alert-danger d-none" id="erreurs"></div>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Depuis</dt><dd class="col-sm-9">{{ $transfert->magasin->libelle }} ({{ $transfert->magasin->code }})</dd>
                <dt class="col-sm-3">Vers</dt><dd class="col-sm-9">{{ $transfert->magasinDestination->libelle }} ({{ $transfert->magasinDestination->code }})</dd>
                <dt class="col-sm-3">Expédié par</dt><dd class="col-sm-9">{{ $transfert->validateur?->full_name }} le {{ $transfert->validated_at->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-3">Commentaire</dt><dd class="col-sm-9">{{ $transfert->commentaire ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Quantités reçues</h3></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr><th>Article</th><th class="text-end">Expédié</th><th style="width: 160px">Reçu</th><th class="text-end">Écart</th><th>Unité</th></tr>
                </thead>
                <tbody>
                    @foreach ($transfert->lignes as $ligne)
                        <tr data-article="{{ $ligne->article_id }}" data-expedie="{{ $ligne->quantite }}">
                            <td>{{ $ligne->article->designation }} <small class="text-muted">({{ $ligne->article->code }})</small></td>
                            <td class="text-end">{{ $ligne->quantite }}</td>
                            <td><input type="number" class="form-control form-control-sm qte-recue" min="0" max="{{ $ligne->quantite }}" step="1" value="{{ $ligne->quantite }}"
                                       aria-label="Quantité reçue pour {{ $ligne->article->designation }}"></td>
                            <td class="text-end ecart"></td>
                            <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3 d-none" id="zone-motif">
        <div class="card-body">
            <label for="motif" class="form-label">Motif de l'écart <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="motif" maxlength="500" placeholder="Ex. : 5 cartons endommagés pendant le transport">
            <div class="form-text">
                @if ($ecartReintegre)
                    La quantité non reçue sera réintégrée dans le stock de {{ $transfert->magasin->libelle }}.
                @else
                    La quantité non reçue sera comptée comme perte en transit.
                @endif
            </div>
        </div>
    </div>

    <div class="text-end mb-4">
        <a href="{{ route('stock.transferts.a-receptionner') }}" class="btn btn-secondary">Retour</a>
        <button type="button" class="btn btn-outline-danger" id="btn-refuser"><i class="fas fa-ban"></i> Refuser le transfert</button>
        <button type="button" class="btn btn-success" id="btn-receptionner"><i class="fas fa-check"></i> Valider la réception</button>
    </div>
</div>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/stock/reception.js') }}"></script>
@endpush
