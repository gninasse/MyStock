@extends('core::layouts.master')

@section('header', 'Articles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Référentiels</a></li>
    <li class="breadcrumb-item active" aria-current="page">Articles</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des articles</h3>
    </div>
    <div class="card-body">
        <div id="toolbar" class="d-flex gap-1">
            @can('referentiel.articles.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter un article">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            {{-- Modifier et désactiver : réservé à l'Admin, grisé pour les autres rôles (SFD 5.2.1) --}}
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier"
                    data-autorise="{{ auth()->user()->can('referentiel.articles.update') ? 1 : 0 }}">
                <i class="fas fa-edit"></i>
            </button>
            <button id="btn-toggle" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Activer / Désactiver"
                    data-autorise="{{ auth()->user()->can('referentiel.articles.toggle-status') ? 1 : 0 }}">
                <i class="fas fa-power-off"></i>
            </button>
            <select id="filtre-categorie" class="form-select ms-2" style="width: auto">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                @endforeach
            </select>
        </div>
        <table id="articles-table"
               data-toggle="table"
               data-url="{{ route('referentiel.articles.data') }}"
               data-query-params="articlesQueryParams"
               data-pagination="true"
               data-side-pagination="server"
               data-page-size="30"
               data-page-list="[30, 60, 100]"
               data-search="true"
               data-show-refresh="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id">
            <thead>
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="unite_libelle" data-sortable="false">Unité</th>
                    <th data-field="categorie.libelle">Catégorie</th>
                    <th data-field="stock_minimum" data-sortable="true">Stock minimum</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="articleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="articleForm" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un article</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="code" name="code" maxlength="30" required>
                    </div>
                    <div class="mb-3">
                        <label for="designation" class="form-label">Désignation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="designation" name="designation" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label for="categorie_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="categorie_id" name="categorie_id" required>
                            <option value="">— Choisir —</option>
                            @foreach ($categories as $categorie)
                                <option value="{{ $categorie->id }}" data-unite="{{ $categorie->unite_par_defaut }}">{{ $categorie->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="unite_mesure" class="form-label">Unité de mesure <span class="text-danger">*</span></label>
                        <select class="form-select" id="unite_mesure" name="unite_mesure" required>
                            <option value="">— Choisir —</option>
                            @foreach ($unitesMesure as $code => $libelle)
                                <option value="{{ $code }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock_minimum" class="form-label">Stock minimum</label>
                        <input type="number" class="form-control" id="stock_minimum" name="stock_minimum" min="0" step="1">
                        <div class="form-text">Seuil d'alerte de stock bas.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@include('referentiel::partials.table-assets')

@push('js')
<script>
    window.articlesQueryParams = (params) => ({ ...params, categorie_id: $('#filtre-categorie').val() });
</script>
<script type="module" src="{{ asset('js/modules/referentiel/articles/index.js') }}"></script>
@endpush
