@extends('core::layouts.master')

@section('header', 'Transferts entre magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Transferts</li>
@endsection

@section('content')
@php $statut = $filtres['statut'] ?? 'EN_TRANSIT'; @endphp
<form method="GET" class="card mb-3 no-print">
    <input type="hidden" name="envoye" value="1">
    <div class="card-body row g-2">
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_du">Expédiés du</label>
            <input type="date" class="form-control form-control-sm" id="date_du" name="date_du" value="{{ $filtres['date_du'] ?? now()->subMonths(3)->toDateString() }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_au">Au</label>
            <input type="date" class="form-control form-control-sm @error('date_au') is-invalid @enderror" id="date_au" name="date_au" value="{{ $filtres['date_au'] ?? now()->toDateString() }}">
            @error('date_au')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="statut">Statut</label>
            <select class="form-select form-select-sm" id="statut" name="statut">
                <option value="EN_TRANSIT" @selected($statut === 'EN_TRANSIT')>En transit</option>
                <option value="VALIDE" @selected($statut === 'VALIDE')>Reçus</option>
                <option value="ANNULE" @selected($statut === 'ANNULE')>Annulés</option>
                <option value="" @selected($statut === '')>Tous</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-0" for="magasin_id">Magasin (source ou destinataire)</label>
            <select class="form-select form-select-sm" id="magasin_id" name="magasin_id">
                <option value="">Tous</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

@include('etats::partials.resultat')
@stop
