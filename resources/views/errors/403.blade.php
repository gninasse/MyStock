@extends('errors::layout')
@section('code', '403')
@section('titre', 'Accès refusé')
@section('message')
    @php($detail = $exception->getMessage())
    {{ $detail && $detail !== 'User does not have the right permissions.' && $detail !== 'This action is unauthorized.' ? $detail : 'Vous n\'avez pas les droits nécessaires pour cette page ou cette action.' }}
    Si vous pensez qu'il s'agit d'une erreur, adressez-vous à l'administrateur de l'application.
@endsection
