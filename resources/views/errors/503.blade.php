@extends('errors::layout')
@section('code', '503')
@section('titre', 'Maintenance en cours')
@section('message', 'MyStock est momentanément indisponible pour maintenance. Merci de réessayer dans quelques minutes.')
@section('actions')
    <button type="button" class="btn btn-principal" onclick="location.reload()">Réessayer</button>
@endsection
