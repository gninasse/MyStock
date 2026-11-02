{{--
    Gabarit commun des imprimés et états PDF (DomPDF).
    Sections : titre, sous-titre (référence), statut (pastille), contenu.
    Variables facultatives : $filigrane (BROUILLON, ANNULÉ…), $paysage (bool).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('titre')</title>
    <style>
        @page { margin: 92px 34px 52px 34px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.6px; color: #1f2933; line-height: 1.35; }

        /* En-tête et pied répétés sur chaque page */
        .entete { position: fixed; top: -74px; left: 0; right: 0; height: 60px; }
        .entete table { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; padding: 0; }
        .entete .logo { width: 40px; }
        .entete .logo img { width: 32px; height: 32px; }
        .entete .organisation { font-size: 11px; font-weight: bold; color: #1f2933; }
        .entete .application { font-size: 7.5px; color: #6b7682; letter-spacing: .4px; text-transform: uppercase; }
        .entete .document { text-align: right; }
        .entete .document .titre { font-size: 14px; font-weight: bold; color: #1f2933; }
        .entete .document .reference { font-size: 9px; color: #3b4651; margin-top: 2px; }
        .entete .filet { height: 3px; background: #1f4e79; margin-top: 8px; }
        .pied { position: fixed; bottom: -38px; left: 0; right: 90px; height: 20px; font-size: 7px; color: #737b85; border-top: .6px solid #d5dbe1; padding-top: 5px; }

        .pastille { display: inline-block; padding: 1.5px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; color: #fff; background: #6b7682; }
        .pastille.VALIDE { background: #1f7a4d; } .pastille.EN_TRANSIT { background: #c48a00; }
        .pastille.BROUILLON { background: #6b7682; } .pastille.ANNULE { background: #3b4651; }

        .filigrane { position: fixed; top: 300px; left: 0; right: 0; text-align: center; font-size: 82px; font-weight: bold;
                     color: #c0392b; opacity: .09; transform: rotate(-28deg); letter-spacing: 6px; }

        h2.section { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: #1f4e79; margin: 14px 0 5px; padding-bottom: 3px; border-bottom: .6px solid #d5dbe1; }

        /* Blocs d'informations en colonnes */
        table.infos { width: 100%; border-collapse: separate; border-spacing: 0; }
        table.infos td.bloc { vertical-align: top; background: #f4f6f8; border-radius: 4px; padding: 7px 9px; }
        table.infos td.espace, table.signatures td.espace { width: 6px; padding: 0; border: none; background: none; }
        .bloc .etiquette { font-size: 6.8px; text-transform: uppercase; letter-spacing: .5px; color: #6b7682; }
        .bloc .valeur { font-size: 9.5px; font-weight: bold; color: #1f2933; margin-bottom: 4px; }
        .bloc .detail { font-size: 7.8px; color: #3b4651; }

        dl.meta { margin: 0; } dl.meta dt { float: left; width: 30%; color: #6b7682; } dl.meta dd { margin: 0 0 2px 30%; }

        /* Tableaux de lignes */
        table.lignes { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.lignes thead th { background: #1f4e79; color: #fff; font-size: 7.6px; font-weight: bold; text-align: left; padding: 5px 5px; }
        table.lignes td { padding: 4.5px 5px; border-bottom: .6px solid #e3e7eb; vertical-align: top; }
        table.lignes tbody tr:nth-child(even) td { background: #f8f9fb; }
        table.lignes .num { text-align: right; white-space: nowrap; }
        table.lignes .centre { text-align: center; }
        table.lignes .muet { color: #737b85; font-size: 7.4px; }
        table.lignes tfoot td { font-weight: bold; border-top: 1px solid #1f4e79; border-bottom: none; background: #fff; }
        table.lignes tr.groupe th { background: #e8edf2; color: #1f2933; text-align: left; padding: 4px 5px; font-size: 8px; }
        table.lignes tr.total td { font-weight: bold; background: #eef1f4; }
        tr.alerte-egal td { background: #fff4d6 !important; }
        tr.alerte-sous td { background: #fbe1e3 !important; }
        .negatif { color: #b42318; font-weight: bold; }
        .positif { color: #1f7a4d; font-weight: bold; }

        /* Signatures */
        table.signatures { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 22px; page-break-inside: avoid; }
        table.signatures td.case { vertical-align: top; border: .6px solid #c9d0d7; border-radius: 4px; height: 78px; padding: 6px 8px; }
        table.signatures .role { font-size: 7.6px; font-weight: bold; text-transform: uppercase; letter-spacing: .4px; color: #1f4e79; }
        table.signatures .ligne { font-size: 7.2px; color: #737b85; margin-top: 3px; }

        .note { margin-top: 10px; padding: 6px 9px; border-left: 3px solid #c48a00; background: #fff8e6; font-size: 8px; }
        .resume { margin-top: 8px; text-align: right; font-size: 8px; color: #3b4651; }
        .filtres { font-size: 7.8px; color: #3b4651; margin-bottom: 6px; }
        .filtres b { color: #1f2933; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="entete">
        <table>
            <tr>
                <td class="logo"><img src="{{ public_path('adminlte/assets/img/AdminLTELogo.png') }}" alt=""></td>
                <td>
                    <div class="organisation">{{ \Modules\Referentiel\Services\Parametres::get('organisation_nom') }}</div>
                    <div class="application">{{ config('app.name') }} · Gestion des stocks</div>
                </td>
                <td class="document">
                    <div class="titre">@yield('titre')</div>
                    <div class="reference">@yield('sous-titre')</div>
                </td>
            </tr>
        </table>
        <div class="filet"></div>
    </div>

    <div class="pied">
        Édité le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()?->full_name ?? '—' }} · @yield('pied', config('app.name'))
    </div>

    @if (! empty($filigrane))
        <div class="filigrane">{{ $filigrane }}</div>
    @endif

    @yield('contenu')
</body>
</html>
