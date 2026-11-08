{{--
    Gabarit commun des imprimés et états PDF (DomPDF).
    En-tête administratif officiel (paramétrable dans Administration > En-tête des documents) :
    hiérarchie à gauche, armoiries/logo au centre, État et devise à droite, puis le libellé
    du document centré. Sections : titre (libellé), reference (N°), sous-titre, pied, contenu.
    Variables facultatives : $filigrane (BROUILLON, ANNULÉ…).
--}}
@php
    $enteteOfficiel = app(\Modules\Referentiel\Services\EnteteDocuments::class)->donnees();
    $armoiriesAuCentre = $enteteOfficiel['armoiries'] && $enteteOfficiel['armoiries_position'] === 'CENTRE';
    $logoAuCentre = $enteteOfficiel['logo'] && $enteteOfficiel['logo_position'] === 'CENTRE';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('titre')</title>
    <style>
        @page { margin: 30px 34px 56px 34px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.6px; color: #1f2933; line-height: 1.35; }

        /* En-tête administratif (première page) */
        table.entete-officielle { width: 100%; border-collapse: collapse; }
        table.entete-officielle td { vertical-align: top; padding: 0; text-align: center; color: #000; }
        table.entete-officielle td.hierarchie { width: 42%; }
        table.entete-officielle td.centre { width: 16%; vertical-align: middle; }
        table.entete-officielle td.etat { width: 42%; }
        .entete-officielle .niveau { font-size: 7.6px; font-weight: bold; text-transform: uppercase; line-height: 1.3; }
        .entete-officielle .tiret { width: 34px; margin: 2.5px auto; border-top: .8px solid #000; }
        .entete-officielle .nom-etat { font-size: 10.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; }
        .entete-officielle .devise { font-size: 7.8px; font-style: italic; }
        .entete-officielle img.armoiries { height: 52px; margin-bottom: 4px; }
        .entete-officielle img.logo { height: 44px; margin-bottom: 4px; }
        .entete-officielle td.centre img { margin: 0 3px; }

        /* Libellé du document, centré */
        .titre-document { text-align: center; margin: 14px 0 12px; }
        .titre-document table.cadre { margin: 0 auto; border-collapse: collapse; }
        .titre-document table.cadre td { border-top: 1.4px solid #000; border-bottom: 1.4px solid #000; padding: 4px 18px; }
        .titre-document .libelle { font-size: 13.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #000; }
        .titre-document .reference { font-size: 9.5px; font-weight: bold; color: #000; margin-top: 5px; }
        .titre-document .sous-titre { font-size: 8.4px; color: #3b4651; margin-top: 2px; }

        .fait-a { text-align: right; margin-top: 18px; font-size: 8.6px; color: #000; page-break-after: avoid; }

        .pied { position: fixed; bottom: -40px; left: 90px; right: 90px; height: 24px; font-size: 6.8px; color: #5c6670; border-top: .6px solid #c9d0d7; padding-top: 4px; text-align: center; }
        .pied .mentions { color: #1f2933; }

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
    <div class="pied">
        @if ($enteteOfficiel['pied'])<div class="mentions">{{ $enteteOfficiel['pied'] }}</div>@endif
        Édité le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()?->full_name ?? '—' }} · {{ \Modules\Referentiel\Services\Parametres::get('organisation_nom') }} · {{ config('app.name') }}@hasSection('pied') · @yield('pied')@endif
    </div>

    <table class="entete-officielle">
        <tr>
            <td class="hierarchie">
                @if ($enteteOfficiel['logo'] && $enteteOfficiel['logo_position'] === 'GAUCHE')
                    <img class="logo" src="{{ $enteteOfficiel['logo'] }}" alt=""><br>
                @endif
                @foreach ($enteteOfficiel['hierarchie'] as $niveau)
                    <div class="niveau">{{ $niveau }}</div>
                    @unless ($loop->last)<div class="tiret"></div>@endunless
                @endforeach
            </td>
            <td class="centre">
                @if ($armoiriesAuCentre)<img class="armoiries" src="{{ $enteteOfficiel['armoiries'] }}" alt="">@endif
                @if ($logoAuCentre)<img class="logo" src="{{ $enteteOfficiel['logo'] }}" alt="">@endif
            </td>
            <td class="etat">
                @if ($enteteOfficiel['armoiries'] && $enteteOfficiel['armoiries_position'] === 'DROITE')
                    <img class="armoiries" src="{{ $enteteOfficiel['armoiries'] }}" alt=""><br>
                @endif
                <div class="nom-etat">{{ $enteteOfficiel['etat'] }}</div>
                @if ($enteteOfficiel['devise'])
                    <div class="tiret"></div>
                    <div class="devise">{{ $enteteOfficiel['devise'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="titre-document">
        <table class="cadre"><tr><td><div class="libelle">@yield('titre')</div></td></tr></table>
        @hasSection('reference')<div class="reference">@yield('reference')</div>@endif
        @hasSection('sous-titre')<div class="sous-titre">@yield('sous-titre')</div>@endif
    </div>

    @if (! empty($filigrane))
        <div class="filigrane">{{ $filigrane }}</div>
    @endif

    @yield('contenu')
</body>
</html>
