@extends('core::layouts.master')

@section('header', 'Magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Référentiels</a></li>
    <li class="breadcrumb-item active" aria-current="page">Magasins</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des magasins</h3>
    </div>
    <div class="card-body">
        <div id="toolbar">
            @can('referentiel.magasins.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter un magasin">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('referentiel.magasins.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('referentiel.responsables.manage')
            <button id="btn-responsables" class="btn btn-secondary" disabled data-bs-toggle="tooltip" title="Gérer les responsables">
                <i class="fas fa-user-tie"></i>
            </button>
            @endcan
            @can('referentiel.magasins.toggle-status')
            <button id="btn-toggle" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Activer / Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
        </div>
        <table id="magasins-table"
               data-toggle="table"
               data-url="{{ route('referentiel.magasins.data') }}"
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
                    <th data-field="localisation" data-sortable="true">Localisation</th>
                    <th data-field="responsables_actuels">Responsables actuels</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="magasinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="magasinForm" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un magasin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="code" name="code" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="localisation" class="form-label">Localisation</label>
                        <input type="text" class="form-control" id="localisation" name="localisation" maxlength="200">
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
<script type="module" src="{{ asset('js/modules/referentiel/magasins/index.js') }}"></script>
@endpush
