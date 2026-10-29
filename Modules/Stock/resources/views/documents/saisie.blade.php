@extends('core::layouts.master')

@php
    $estSortie = $document->type === \Modules\Stock\Models\Document::SORTIE;
    $titre = $estSortie ? 'Sortie de stock' : 'Entrée de stock';
@endphp

@section('header', $titre)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.documents.index') }}">Documents</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $document->exists ? 'Modifier le brouillon' : 'Nouvelle '.($estSortie ? 'sortie' : 'entrée') }}</li>
@endsection

@section('content')
<div id="page-saisie"
     data-document="{{ json_encode([
         'id' => $document->id,
         'type' => $document->type,
         'segment' => $segment,
         'magasin_id' => $document->magasin_id,
         'direction_id' => $document->direction_id,
         'service_id' => $document->service_id,
         'unite_id' => $document->unite_id,
     ]) }}"
     data-lignes="{{ json_encode($lignes) }}"
     data-unites="{{ json_encode($unitesMesure) }}"
     data-peut-creer-article="{{ ! $estSortie && auth()->user()->can('referentiel.articles.store') ? 1 : 0 }}">

    <div class="alert alert-danger d-none" id="erreurs"></div>

    <form id="saisieForm" novalidate>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">{{ $titre }}</h3>
                <span class="badge text-bg-secondary ms-2">Brouillon</span>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label for="date_mouvement" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date_mouvement" name="date_mouvement"
                           value="{{ $document->date_mouvement->toDateString() }}"
                           max="{{ now()->toDateString() }}"
                           min="{{ now()->subDays(config('stock.jours_retroactifs_max'))->toDateString() }}" required>
                </div>
                <div class="col-md-5">
                    <label for="magasin_id" class="form-label">Magasin {{ $estSortie ? 'source' : 'destinataire' }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="magasin_id" name="magasin_id" required>
                        <option value="">— Choisir un magasin —</option>
                        @foreach ($magasins as $magasin)
                            <option value="{{ $magasin->id }}" @selected($magasin->id === $document->magasin_id)>{{ $magasin->libelle }} ({{ $magasin->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($estSortie)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Bénéficiaire</h3></div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label for="direction_id" class="form-label">Direction <span class="text-danger">*</span></label>
                    <select class="form-select" id="direction_id" name="direction_id"><option value="">— Choisir —</option></select>
                </div>
                <div class="col-md-4">
                    <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                    <select class="form-select" id="service_id" name="service_id" disabled><option value="">— Choisir —</option></select>
                </div>
                <div class="col-md-4">
                    <label for="unite_id" class="form-label">Unité <small class="text-muted">(optionnel)</small></label>
                    <select class="form-select" id="unite_id" name="unite_id" disabled><option value="">— Aucune —</option></select>
                </div>
                <div class="col-12 text-muted">→ Bénéficiaire final : <strong id="beneficiaire-final">—</strong></div>
            </div>
        </div>
        @endif

        <div class="card mb-3 d-none" id="zone-articles">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-star text-warning"></i> Articles fréquents</h3>
                @if ($estSortie)
                <div class="form-check ms-auto">
                    <input class="form-check-input" type="checkbox" id="avec-stock" checked>
                    <label class="form-check-label" for="avec-stock">Afficher uniquement les articles avec stock disponible</label>
                </div>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody id="articles-frequents"></tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <label class="form-label fw-bold"><i class="fas fa-plus"></i> Ajouter un autre article</label>
                <div class="row g-2">
                    <div class="col-md-8 position-relative">
                        <input type="text" class="form-control" id="recherche-article" placeholder="Rechercher un article (code ou désignation)…" autocomplete="off">
                        <div class="list-group position-absolute w-100 shadow d-none" id="resultats-recherche" style="z-index: 1050; max-height: 300px; overflow-y: auto"></div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="recherche-categorie">
                            <option value="">Toutes les catégories</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" data-unite="{{ $categorie->unite_par_defaut }}">{{ $categorie->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Articles ajoutés (<span id="nb-lignes">0</span>)</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Article</th>
                            <th style="width: 140px">Quantité</th>
                            <th>Unité</th>
                            @if ($estSortie)<th>Stock dispo.</th>@endif
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="lignes"></tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <label for="commentaire" class="form-label">Commentaire</label>
                <input type="text" class="form-control" id="commentaire" name="commentaire" maxlength="500"
                       value="{{ $document->commentaire }}"
                       placeholder="{{ $estSortie ? 'Ex. : Dotation trimestrielle service informatique' : 'Ex. : Livraison fournisseur BuroPlus - BC 458' }}">
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('stock.documents.index') }}" class="btn btn-secondary">Annuler</a>
                <button type="button" class="btn btn-outline-primary" data-action="brouillon"><i class="fas fa-save"></i> Enregistrer brouillon</button>
                <button type="button" class="btn btn-success" data-action="valider"><i class="fas fa-check"></i> Valider {{ $estSortie ? 'la sortie' : 'l\'entrée' }}</button>
            </div>
        </div>
    </form>
</div>

@unless ($estSortie)
<div class="modal fade" id="articleRapideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="articleRapideForm" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Création rapide d'article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ar_code" class="form-label">Code</label>
                        <input type="text" class="form-control text-uppercase" id="ar_code" name="code" maxlength="30" placeholder="Laisser vide pour générer">
                    </div>
                    <div class="mb-3">
                        <label for="ar_designation" class="form-label">Désignation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ar_designation" name="designation" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label for="ar_categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="ar_categorie" name="categorie_id" required>
                            <option value="">— Choisir —</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" data-unite="{{ $categorie->unite_par_defaut }}">{{ $categorie->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ar_unite" class="form-label">Unité <span class="text-danger">*</span></label>
                        <select class="form-select" id="ar_unite" name="unite_mesure" required>
                            <option value="">— Choisir —</option>
                            @foreach ($unitesMesure as $code => $libelle)
                                <option value="{{ $code }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ar_stock_minimum" class="form-label">Stock minimum</label>
                        <input type="number" class="form-control" id="ar_stock_minimum" name="stock_minimum" min="0" step="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer et ajouter à l'entrée</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endunless
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/stock/saisie.js') }}"></script>
@endpush
