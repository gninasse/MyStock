@extends('core::layouts.master')

@section('header', 'Journal des mouvements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Journal des mouvements</li>
@endsection

@section('content')
@php $typesChoisis = $filtres['types'] ?? array_keys($types); @endphp
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2">
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_du">Du</label>
            <input type="date" class="form-control form-control-sm @error('date_du') is-invalid @enderror" id="date_du" name="date_du" value="{{ $filtres['date_du'] ?? now()->startOfMonth()->toDateString() }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_au">Au</label>
            <input type="date" class="form-control form-control-sm @error('date_au') is-invalid @enderror" id="date_au" name="date_au" value="{{ $filtres['date_au'] ?? now()->toDateString() }}">
            @error('date_au')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-0 d-block">Type de mouvement</label>
            @foreach ($types as $code => $libelle)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="type_{{ $code }}" name="types[]" value="{{ $code }}" @checked(in_array($code, $typesChoisis))>
                    <label class="form-check-label small" for="type_{{ $code }}">{{ $libelle }}</label>
                </div>
            @endforeach
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="magasin_id">Magasin</label>
            <select class="form-select form-select-sm" id="magasin_id" name="magasin_id">
                <option value="">Tous</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-0" for="user_id">Utilisateur</label>
            <select class="form-select form-select-sm" id="user_id" name="user_id">
                <option value="">Tous</option>
                @foreach ($utilisateurs as $utilisateur)
                    <option value="{{ $utilisateur->id }}" @selected(($filtres['user_id'] ?? null) == $utilisateur->id)>{{ $utilisateur->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-0" for="article">Article</label>
            <input type="text" class="form-control form-control-sm" id="article" name="article" value="{{ $filtres['article'] ?? '' }}" placeholder="Code ou désignation">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-0" for="beneficiaire">Bénéficiaire</label>
            <input type="text" class="form-control form-control-sm" id="beneficiaire" name="beneficiaire" value="{{ $filtres['beneficiaire'] ?? '' }}" placeholder="Service ou Unité">
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

@include('etats::partials.resultat')
@stop
