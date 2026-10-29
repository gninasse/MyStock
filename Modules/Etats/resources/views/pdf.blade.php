<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $etat->titre }}</title>
    <style>
        @page { margin: 70px 30px 40px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        header { position: fixed; top: -55px; left: 0; right: 0; height: 45px; border-bottom: 1px solid #999; }
        header img { height: 32px; float: left; margin-right: 8px; }
        header .titre { font-size: 14px; font-weight: bold; }
        header .edition { position: absolute; right: 0; top: 0; text-align: right; font-size: 8px; color: #555; }
        .filtres { margin-bottom: 8px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 4px; }
        thead th { background: #dde3ea; }
        .groupe th { background: #f1f3f5; text-align: left; }
        .total td { font-weight: bold; }
        .num { text-align: right; }
        .alerte-egal td { background: #ffe5b4; }
        .alerte-sous td { background: #f8d7da; }
        dl { margin: 0 0 8px 0; } dt { font-weight: bold; display: inline; } dd { display: inline; margin: 0 12px 0 4px; }
    </style>
</head>
<body>
    <header>
        <img src="{{ public_path('adminlte/assets/img/AdminLTELogo.png') }}" alt="">
        <div class="titre">{{ $etat->titre }}</div>
        <div>{{ config('app.name') }} — CHU-YO</div>
        <div class="edition">Édité le {{ now()->format('d/m/Y à H:i') }}<br>par {{ auth()->user()?->full_name }}</div>
    </header>

    <div class="filtres">
        @foreach ($etat->filtres as $libelle => $valeur)
            {{ $libelle }} : {{ $valeur }}@unless ($loop->last) | @endunless
        @endforeach
    </div>

    @if ($etat->entete)
        <dl>
            @foreach ($etat->entete as $libelle => $valeur)
                <dt>{{ $libelle }} :</dt><dd>{{ $valeur }}</dd>
            @endforeach
        </dl>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($etat->colonnes as $cle => $libelle)
                    <th class="{{ in_array($cle, $etat->numeriques) ? 'num' : '' }}">{{ $libelle }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($etat->groupes as $groupe)
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
            @endforeach
        </tbody>
    </table>

    @foreach ($etat->pied as $libelle => $valeur)
        <p><strong>{{ $libelle }} : {{ $valeur }}</strong></p>
    @endforeach
</body>
</html>
