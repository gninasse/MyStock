@extends('core::layouts.master')

@section('header', 'Articles dormants')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Articles dormants</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2 align-items-end">
        @include('etats::partials.filtre-select', ['nom' => 'magasin_id', 'libelle' => 'Magasin', 'options' => $magasins])
        @include('etats::partials.filtre-select', ['nom' => 'categorie_id', 'libelle' => 'Catégorie', 'options' => $categories, 'tous' => 'Toutes'])
        <div class="col-md-2">
            <label class="form-label small mb-0" for="jours">Sans sortie depuis (jours)</label>
            <input type="number" class="form-control form-control-sm" id="jours" name="jours" min="7" max="730" value="{{ $filtres['jours'] ?? 90 }}">
        </div>
        <div class="col text-end">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="small text-muted no-print">Articles en stock sans sortie ni transfert depuis la durée choisie. En rouge : jamais sortis ou inactifs depuis plus du double de la durée.</p>

@include('etats::partials.resultat')
@stop
