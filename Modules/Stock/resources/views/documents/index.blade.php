@extends('core::layouts.master')

@section('header', 'Documents de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Mouvements</a></li>
    <li class="breadcrumb-item active" aria-current="page">Liste des documents</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <h3 class="card-title mb-0">Liste des documents</h3>
        <div class="ms-auto">
            @can('stock.entrees.saisir')
            <a href="{{ route('stock.saisie.create', 'entrees') }}" class="btn btn-success btn-sm"><i class="fas fa-arrow-down"></i> Nouvelle entrée</a>
            @endcan
            @can('stock.sorties.saisir')
            <a href="{{ route('stock.saisie.create', 'sorties') }}" class="btn btn-danger btn-sm"><i class="fas fa-arrow-up"></i> Nouvelle sortie</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <form id="filtres" class="row g-2 mb-3">
            <div class="col-md-2">
                <label class="form-label small mb-0" for="date_du">Du</label>
                <input type="date" class="form-control form-control-sm" id="date_du" name="date_du" value="{{ now()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0" for="date_au">Au</label>
                <input type="date" class="form-control form-control-sm" id="date_au" name="date_au" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0" for="type">Type</label>
                <select class="form-select form-select-sm" id="type" name="type">
                    <option value="">Tous</option>
                    <option value="ENTREE">Entrée</option>
                    <option value="SORTIE">Sortie</option>
                    <option value="AJUSTEMENT">Ajustement</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0" for="magasin_id">Magasin</label>
                <select class="form-select form-select-sm" id="magasin_id" name="magasin_id">
                    <option value="">Tous</option>
                    @foreach ($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0" for="statut">Statut</label>
                <select class="form-select form-select-sm" id="statut" name="statut">
                    <option value="">Tous</option>
                    <option value="BROUILLON">Brouillon</option>
                    <option value="VALIDE">Validé</option>
                    <option value="ANNULE">Annulé</option>
                </select>
            </div>
        </form>

        <table id="documents-table"
               data-toggle="table"
               data-url="{{ route('stock.documents.data') }}"
               data-query-params="documentsQueryParams"
               data-pagination="true"
               data-side-pagination="server"
               data-page-size="25"
               data-page-list="[25, 50, 100]"
               data-search="true"
               data-search-align="left"
               data-show-refresh="true">
            <thead>
                <tr>
                    <th data-field="reference" data-sortable="true" data-formatter="referenceFormatter">N° référence</th>
                    <th data-field="date_mouvement" data-sortable="true">Date</th>
                    <th data-field="type" data-sortable="true" data-formatter="typeFormatter">Type</th>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="beneficiaire">Bénéficiaire</th>
                    <th data-field="lignes_count">Lignes</th>
                    <th data-field="statut" data-sortable="true" data-formatter="statutDocumentFormatter">Statut</th>
                    <th data-field="utilisateur">Utilisateur</th>
                    <th data-field="id" data-formatter="actionsFormatter" data-events="actionsEvents" class="text-end">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@include('referentiel::partials.table-assets')

@push('js')
<script type="module" src="{{ asset('js/modules/stock/documents.js') }}"></script>
@endpush
