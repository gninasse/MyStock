@extends('core::layouts.master')

@section('header', 'Paramètres')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active" aria-current="page">Paramètres</li>
@endsection

@section('content')
@can('referentiel.entete.gerer')
<div class="alert alert-light border d-flex align-items-center gap-3 mb-3">
    <i class="bi bi-file-earmark-richtext fs-4 text-primary"></i>
    <div class="flex-grow-1">L'en-tête officiel des documents PDF (hiérarchie, armoiries, devise, libellés) se règle sur un écran dédié.</div>
    <a href="{{ route('referentiel.parametres.entete') }}" class="btn btn-sm btn-outline-primary">En-tête des documents</a>
</div>
@endcan
<form id="form-parametres" data-url="{{ route('referentiel.parametres.update') }}">
    @foreach ($groupes as $groupe => $definitions)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title fw-bold">{{ $groupe }}</h3></div>
            <div class="card-body">
                @foreach ($definitions as $cle => $d)
                    <div class="row mb-3">
                        <label for="{{ $cle }}" class="col-md-4 col-form-label">{{ $d['libelle'] }}</label>
                        <div class="col-md-6">
                            @switch($d['type'])
                                @case('booleen')
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="{{ $cle }}" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $cle }}" name="{{ $cle }}" value="1" @checked($valeurs[$cle])>
                                    </div>
                                    @break
                                @case('choix')
                                    <select class="form-select" id="{{ $cle }}" name="{{ $cle }}">
                                        @foreach ($d['choix'] as $valeur => $libelle)
                                            <option value="{{ $valeur }}" @selected($valeurs[$cle] === $valeur)>{{ $libelle }}</option>
                                        @endforeach
                                    </select>
                                    @break
                                @case('entier')
                                    <input type="number" class="form-control" id="{{ $cle }}" name="{{ $cle }}" value="{{ $valeurs[$cle] }}"
                                           min="{{ $d['min'] ?? 0 }}" max="{{ $d['max'] ?? '' }}" style="max-width: 160px">
                                    @break
                                @default
                                    <input type="text" class="form-control" id="{{ $cle }}" name="{{ $cle }}" value="{{ $valeurs[$cle] }}" maxlength="{{ $d['max'] ?? 255 }}">
                            @endswitch
                            <div class="invalid-feedback"></div>
                            @if (! empty($d['aide']))
                                <div class="form-text">{{ $d['aide'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="text-end mb-4">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
    </div>
</form>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/referentiel/parametres/index.js') }}"></script>
@endpush
