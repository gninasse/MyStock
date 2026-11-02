@extends('core::layouts.master')

@section('header', "Écarts d'inventaire")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Écarts d&rsquo;inventaire</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2 align-items-end">
        @include('etats::partials.filtre-periode', ['defautDu' => now()->subYear()->startOfMonth()->toDateString()])
        @include('etats::partials.filtre-select', ['nom' => 'magasin_id', 'libelle' => 'Magasin', 'options' => $magasins])
        @include('etats::partials.filtre-select', ['nom' => 'categorie_id', 'libelle' => 'Catégorie', 'options' => $categories, 'tous' => 'Toutes'])
        <div class="col text-end">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="small text-muted no-print">Écarts constatés lors des inventaires validés. En rouge : manquants ; en orange : excédents.</p>

@include('etats::partials.resultat')
@stop
