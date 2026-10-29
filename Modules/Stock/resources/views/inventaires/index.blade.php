@extends('core::layouts.master')

@section('header', 'Inventaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Inventaire</a></li>
    <li class="breadcrumb-item active" aria-current="page">Liste des inventaires</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mb-0">Liste des inventaires</h3>
        <a href="{{ route('stock.inventaires.create') }}" class="btn btn-primary btn-sm ms-auto"><i class="fas fa-clipboard-check"></i> Saisir un inventaire</a>
    </div>
    <div class="card-body">
        <table id="inventaires-table"
               data-toggle="table"
               data-url="{{ route('stock.inventaires.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-page-size="25"
               data-show-refresh="true">
            <thead>
                <tr>
                    <th data-field="date_inventaire">Date</th>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="lignes_count">Articles comptés</th>
                    <th data-field="ecarts_count">Écarts</th>
                    <th data-field="reference_ajustement" data-formatter="ajustementFormatter">Ajustement</th>
                    <th data-field="utilisateur">Utilisateur</th>
                    <th data-field="id" data-formatter="consulterFormatter" class="text-end"></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@include('referentiel::partials.table-assets')

@push('js')
<script>
    window.ajustementFormatter = (value) => value ?? '<span class="text-muted">Aucun écart</span>';
    window.consulterFormatter = (id) => `<a class="btn btn-sm btn-outline-secondary" href="${route('stock.inventaires.show', id)}" title="Consulter"><i class="fas fa-eye"></i></a>`;
</script>
@endpush
