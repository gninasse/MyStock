@extends('errors::layout')
@section('code', '401')
@section('titre', 'Session requise')
@section('message', 'Vous devez être connecté pour accéder à cette page.')
@section('actions')
    <a class="btn btn-principal" href="{{ route('login') }}">Se connecter</a>
@endsection
