@extends('core::layouts.master')

@section('header', 'Responsables — '.$magasin->libelle.' ('.$magasin->code.')')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('referentiel.magasins.index') }}">Magasins</a></li>
    <li class="breadcrumb-item active" aria-current="page">Responsables</li>
@endsection

@section('content')
<div id="page-responsables" data-magasin-id="{{ $magasin->id }}">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Responsables actuels</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr><th>Utilisateur</th><th>Rôle</th><th>Depuis le</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody id="responsables-actuels"></tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Historique complet</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Utilisateur</th><th>Rôle</th><th>Période</th><th>Statut</th></tr>
                        </thead>
                        <tbody id="responsables-historique"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Ajouter un responsable</h3></div>
                <form id="responsableForm" class="card-body" novalidate>
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Utilisateur <span class="text-danger">*</span></label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">— Choisir —</option>
                            @foreach ($utilisateurs as $utilisateur)
                                <option value="{{ $utilisateur->id }}">{{ $utilisateur->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Rôle <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role_responsable" id="role_principal" value="PRINCIPAL" checked>
                            <label class="form-check-label" for="role_principal">Principal</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="role_responsable" id="role_adjoint" value="ADJOINT">
                            <label class="form-check-label" for="role_adjoint">Adjoint</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" value="{{ now()->toDateString() }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Ajouter</button>
                </form>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Utilisateurs rattachés</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Les utilisateurs rattachés voient ce magasin, sans en être responsables.</p>
                    <form id="rattachementForm" class="input-group mb-3">
                        <select class="form-select" name="user_id" required>
                            <option value="">— Choisir —</option>
                            @foreach ($utilisateurs as $utilisateur)
                                <option value="{{ $utilisateur->id }}">{{ $utilisateur->full_name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary" title="Rattacher"><i class="fas fa-link"></i></button>
                    </form>
                    <ul class="list-group" id="utilisateurs-rattaches"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/referentiel/magasins/responsables.js') }}"></script>
@endpush
