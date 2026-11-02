@extends('core::layouts.master')

@section('header', 'Consommation par service')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Consommation par service</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2 align-items-end">
        @include('etats::partials.filtre-periode', ['defautDu' => now()->subMonthsNoOverflow(2)->startOfMonth()->toDateString()])
        @include('etats::partials.filtre-select', ['nom' => 'magasin_id', 'libelle' => 'Magasin', 'options' => $magasins, 'col' => 'col-md-2'])
        @include('etats::partials.filtre-select', ['nom' => 'direction_id', 'libelle' => 'Direction', 'options' => $directions, 'tous' => 'Toutes', 'col' => 'col-md-2'])
        @include('etats::partials.filtre-select', ['nom' => 'categorie_id', 'libelle' => 'Catégorie', 'options' => $categories, 'tous' => 'Toutes', 'col' => 'col-md-2'])
        <div class="col text-end">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="small text-muted no-print">Sorties validées vers les services, hors sorties annulées. Services classés du plus au moins consommateur.</p>

@include('etats::partials.resultat')
@stop
