@extends('core::layouts.master')

@section('header', 'Inventaire physique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.inventaires.index') }}">Inventaires</a></li>
    <li class="breadcrumb-item active" aria-current="page">Saisir un inventaire</li>
@endsection

@section('content')
<div id="page-inventaire" data-unites="{{ json_encode($unitesMesure) }}">
    <div class="alert alert-danger d-none" id="erreurs"></div>

    <div class="card mb-3">
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label for="date_inventaire" class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="date_inventaire" value="{{ now()->toDateString() }}"
                       max="{{ now()->toDateString() }}"
                       min="{{ now()->subDays(config('stock.jours_retroactifs_max'))->toDateString() }}">
            </div>
            <div class="col-md-5">
                <label for="magasin_id" class="form-label">Magasin <span class="text-danger">*</span></label>
                <select class="form-select" id="magasin_id">
                    <option value="">— Choisir un magasin —</option>
                    @foreach ($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->libelle }} ({{ $magasin->code }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card mb-3 d-none" id="zone-comptage">
        <div class="card-header row g-2 align-items-center">
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="filtre-categorie">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" id="filtre-recherche" placeholder="Rechercher un article…">
            </div>
            <div class="col-md-5">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="filtre-stock">
                    <label class="form-check-label" for="filtre-stock">Afficher uniquement les articles avec stock théorique &gt; 0</label>
                </div>
            </div>
        </div>
        <div class="card-body p-0" style="max-height: 520px; overflow-y: auto">
            <table class="table table-sm table-hover mb-0">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>Code</th>
                        <th>Désignation</th>
                        <th>Unité</th>
                        <th class="text-end">Stock théorique</th>
                        <th style="width: 140px">Qté réelle</th>
                        <th class="text-end" style="width: 100px">Écart</th>
                    </tr>
                </thead>
                <tbody id="lignes-inventaire"></tbody>
            </table>
        </div>
        <div class="card-footer">
            <p class="mb-2" id="recapitulatif"></p>
            <label for="commentaire" class="form-label">Commentaire <span class="text-danger d-none" id="commentaire-requis">* (obligatoire si des écarts existent)</span></label>
            <input type="text" class="form-control mb-3" id="commentaire" maxlength="500" placeholder="Ex. : Inventaire trimestriel - Écarts justifiés">
            <div class="text-end">
                <a href="{{ route('stock.inventaires.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="button" class="btn btn-success" id="btn-valider"><i class="fas fa-check"></i> Valider l'inventaire</button>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/stock/inventaire.js') }}"></script>
@endpush
