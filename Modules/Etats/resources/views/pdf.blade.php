@extends('core::pdf.layout')

@section('titre', $etat->titre)
@section('sous-titre', 'État arrêté au '.now()->format('d/m/Y'))
@section('pied', $etat->titre)

@section('contenu')
    @if ($etat->filtres)
        <div class="filtres">
            @foreach ($etat->filtres as $libelle => $valeur)
                <b>{{ $libelle }} :</b> {{ $valeur }}@unless ($loop->last) &nbsp;·&nbsp; @endunless
            @endforeach
        </div>
    @endif

    @if ($etat->entete)
        <table class="infos"><tr>
            @foreach ($etat->entete as $libelle => $valeur)
                @unless ($loop->first)<td class="espace"></td>@endunless
                <td class="bloc"><div class="etiquette">{{ $libelle }}</div><div class="valeur">{{ $valeur }}</div></td>
            @endforeach
        </tr></table>
    @endif

    <table class="lignes">
        <thead>
            <tr>
                @foreach ($etat->colonnes as $cle => $libelle)
                    <th class="{{ in_array($cle, $etat->numeriques) ? 'num' : '' }}">{{ $libelle }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($etat->groupes as $groupe)
                @if ($groupe['titre'])
                    <tr class="groupe"><th colspan="{{ count($etat->colonnes) }}">{{ $groupe['titre'] }}</th></tr>
                @endif
                @foreach ($groupe['lignes'] as $ligne)
                    <tr class="{{ $ligne['_classe'] ?? '' }}">
                        @foreach ($etat->colonnes as $cle => $libelle)
                            <td class="{{ in_array($cle, $etat->numeriques) ? 'num' : '' }}">{{ $ligne[$cle] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
                @if ($groupe['total'] ?? null)
                    <tr class="total">
                        @foreach ($etat->colonnes as $cle => $libelle)
                            <td class="{{ in_array($cle, $etat->numeriques) ? 'num' : '' }}">{{ $groupe['total'][$cle] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ count($etat->colonnes) }}" class="centre muet">Aucun résultat</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($etat->pied)
        <div class="resume">
            @foreach ($etat->pied as $libelle => $valeur)
                <b>{{ $libelle }} :</b> {{ $valeur }}@unless ($loop->last) &nbsp;·&nbsp; @endunless
            @endforeach
        </div>
    @endif
@endsection
