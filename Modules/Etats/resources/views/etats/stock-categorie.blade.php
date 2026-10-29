@extends('core::layouts.master')

@section('header', 'État de stock par catégorie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock par catégorie</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <div class="card-body row g-3 align-items-start">
        <div class="col-md-5">@include('etats::partials.selecteur-magasins')</div>
        <div class="col-md-7 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i> Afficher</button>
        </div>
    </div>
</form>

<p class="text-muted small no-print">Cliquez sur une catégorie pour afficher le détail de ses articles.</p>
@include('etats::partials.resultat', ['repliable' => true])
@stop
