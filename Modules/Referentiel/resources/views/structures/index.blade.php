@extends('core::layouts.master')

@section('header', 'Structures organisationnelles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Référentiels</a></li>
    <li class="breadcrumb-item active" aria-current="page">Structures</li>
@endsection

@section('content')
<div class="card" id="page-structures"
     data-peut-creer="{{ auth()->user()->can('referentiel.structures.store') ? 1 : 0 }}"
     data-peut-modifier="{{ auth()->user()->can('referentiel.structures.update') ? 1 : 0 }}"
     data-peut-supprimer="{{ auth()->user()->can('referentiel.structures.destroy') ? 1 : 0 }}">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Direction → Service → Unité</h3>
        @can('referentiel.structures.store')
        <button class="btn btn-primary btn-sm ms-auto btn-ajouter" data-niveau="direction">
            <i class="fas fa-plus"></i> Ajouter une Direction
        </button>
        @endcan
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            L'Unité est optionnelle : un Service sans Unité est bénéficiaire direct des sorties.
        </p>
        <ul class="list-unstyled mb-0" id="arbre-structures"></ul>
    </div>
</div>

<div class="modal fade" id="structureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="structureForm" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="direction_id">
                    <input type="hidden" name="service_id">
                    <p class="text-muted small" id="structure-parent"></p>
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="code" name="code" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="libelle" name="libelle" maxlength="100" required>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="actif" value="0">
                        <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" checked>
                        <label class="form-check-label" for="actif">Actif</label>
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

@push('js')
<script type="module" src="{{ asset('js/modules/referentiel/structures/index.js') }}"></script>
@endpush
