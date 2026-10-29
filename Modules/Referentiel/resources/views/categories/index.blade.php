@extends('core::layouts.master')

@section('header', 'Catégories')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Référentiels</a></li>
    <li class="breadcrumb-item active" aria-current="page">Catégories</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des catégories</h3>
    </div>
    <div class="card-body">
        <div id="toolbar">
            @can('referentiel.categories.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter une catégorie">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('referentiel.categories.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('referentiel.categories.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        <table id="categories-table"
               data-toggle="table"
               data-url="{{ route('referentiel.categories.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-page-size="20"
               data-page-list="[20, 50, 100]"
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
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="unite_par_defaut" data-sortable="true" data-formatter="uniteFormatter">Unité par défaut</th>
                    <th data-field="articles_count" data-sortable="true">Articles</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="categorieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="categorieForm" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="code" name="code" maxlength="10" placeholder="INFO, FOUR, ENTR…" required>
                    </div>
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="unite_par_defaut" class="form-label">Unité par défaut</label>
                        <select class="form-select" id="unite_par_defaut" name="unite_par_defaut">
                            <option value="">— Aucune —</option>
                            @foreach ($unitesMesure as $code => $libelle)
                                <option value="{{ $code }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
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
    window.unitesMesure = @json($unitesMesure);
    window.uniteFormatter = (value) => (value ? window.unitesMesure[value] ?? value : '-');
</script>
<script type="module" src="{{ asset('js/modules/referentiel/categories/index.js') }}"></script>
@endpush
