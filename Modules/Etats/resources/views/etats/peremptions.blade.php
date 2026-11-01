@extends('core::layouts.master')

@section('header', 'Péremptions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Péremptions</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <div class="card-body row g-2">
        <div class="col-md-3">
            <label class="form-label small mb-0" for="jours">Périment dans les (jours)</label>
            <input type="number" class="form-control form-control-sm" id="jours" name="jours" min="0" max="730"
                   value="{{ $filtres['jours'] ?? \Modules\Referentiel\Services\Parametres::get('peremption_alerte_jours') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-0" for="magasin_id">Magasin</label>
            <select class="form-select form-select-sm" id="magasin_id" name="magasin_id">
                <option value="">Tous</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="small text-muted no-print">En rouge : lots périmés encore en stock. En orange : lots qui périment dans le délai choisi.</p>

@include('etats::partials.resultat')
@stop
