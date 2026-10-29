@extends('core::layouts.master')

@section('header', 'Articles fréquents')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Référentiels</a></li>
    <li class="breadcrumb-item active" aria-current="page">Articles fréquents</li>
@endsection

@section('content')
<div class="card" id="page-frequents" data-maximum="{{ $maximum }}">
    <div class="card-header d-flex align-items-center gap-2">
        <label for="magasin" class="mb-0 fw-bold">Magasin :</label>
        <select id="magasin" class="form-select" style="max-width: 400px">
            <option value="">— Choisir un magasin —</option>
            @foreach ($magasins as $magasin)
                <option value="{{ $magasin->id }}">{{ $magasin->libelle }} ({{ $magasin->code }})</option>
            @endforeach
        </select>
        @if ($magasins->isEmpty())
            <span class="text-muted small">Vous n'êtes rattaché à aucun magasin.</span>
        @endif
    </div>
    <div class="card-body d-none" id="zone-parametrage">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Articles disponibles</h6>
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="recherche" class="form-control" placeholder="Rechercher un article…">
                </div>
                <select id="filtre-categorie" class="form-select mb-2">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $categorie)
                        <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                    @endforeach
                </select>
                <ul class="list-group" id="articles-disponibles" style="max-height: 420px; overflow-y: auto"></ul>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">
                    Articles fréquents (<span id="compteur">0</span>/{{ $maximum }})
                    <small class="text-muted fw-normal">— glisser pour réorganiser</small>
                </h6>
                <ol class="list-group list-group-numbered" id="articles-frequents" style="max-height: 470px; overflow-y: auto"></ol>
            </div>
        </div>
    </div>
    <div class="card-footer d-none text-end" id="zone-actions">
        <button class="btn btn-secondary" id="btn-annuler">Annuler</button>
        <button class="btn btn-primary" id="btn-enregistrer"><i class="fas fa-save"></i> Enregistrer</button>
    </div>
</div>
@stop

@push('js')
<script src="{{ asset('plugins/sortablejs/Sortable.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/referentiel/articles-frequents/index.js') }}"></script>
@endpush
