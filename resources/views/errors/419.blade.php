@extends('errors::layout')
@section('code', '419')
@section('titre', 'Session expirée')
@section('message', 'La page est restée ouverte trop longtemps. Rechargez-la, puis recommencez votre saisie.')
@section('actions')
    <button type="button" class="btn" onclick="history.back()">Page précédente</button>
    <a class="btn btn-principal" href="{{ route('login') }}">Se reconnecter</a>
@endsection
