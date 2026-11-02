@extends('core::layouts.master')

@section('header', 'Couverture de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Couverture de stock</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2 align-items-end">
        @include('etats::partials.filtre-select', ['nom' => 'magasin_id', 'libelle' => 'Magasin', 'options' => $magasins])
        @include('etats::partials.filtre-select', ['nom' => 'categorie_id', 'libelle' => 'Catégorie', 'options' => $categories, 'tous' => 'Toutes', 'col' => 'col-md-2'])
        <div class="col-md-2">
            <label class="form-label small mb-0" for="jours">Période d'analyse</label>
            <select class="form-select form-select-sm" id="jours" name="jours">
                @foreach ([30, 60, 90, 180, 365] as $j)
                    <option value="{{ $j }}" @selected(($filtres['jours'] ?? 90) == $j)>{{ $j }} derniers jours</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="cible">Couverture visée (jours)</label>
            <input type="number" class="form-control form-control-sm" id="cible" name="cible" min="1" max="365" value="{{ $filtres['cible'] ?? 30 }}">
        </div>
        <div class="col-md-2">
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" id="a_risque" name="a_risque" value="1" @checked($filtres['a_risque'] ?? false)>
                <label class="form-check-label small" for="a_risque">Articles à risque uniquement</label>
            </div>
        </div>
        <div class="col text-end">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="small text-muted no-print">
    Couverture = stock ÷ sorties moyennes par jour (consommation + transferts expédiés, nets des réintégrations).
    En rouge : moins de 7 jours ; en orange : sous la couverture visée. « À commander » ramène le stock à la couverture visée.
</p>

@include('etats::partials.resultat')
@stop
