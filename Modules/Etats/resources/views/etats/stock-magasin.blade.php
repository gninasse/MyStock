@extends('core::layouts.master')

@section('header', 'État de stock par magasin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock par magasin</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-3 align-items-start">
        <div class="col-md-4">@include('etats::partials.selecteur-magasins')</div>
        <div class="col-md-3">
            <label class="form-label small mb-0" for="categorie_id">Catégorie</label>
            <select class="form-select form-select-sm" id="categorie_id" name="categorie_id">
                <option value="">Toutes</option>
                @foreach ($categories as $categorie)
                    <option value="{{ $categorie->id }}" @selected(($filtres['categorie_id'] ?? null) == $categorie->id)>{{ $categorie->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="sans_stock" name="sans_stock" value="1" @checked($filtres['sans_stock'])>
                <label class="form-check-label" for="sans_stock">Afficher les articles sans stock</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="alerte" name="alerte" value="1" @checked($filtres['alerte'])>
                <label class="form-check-label" for="alerte">Articles en alerte uniquement</label>
            </div>
        </div>
        <div class="col-md-2 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

@include('etats::partials.resultat')
@stop
