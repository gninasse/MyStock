@extends('core::pdf.layout')

@php
    $kpi = $stats['kpi'];
    $nombre = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pourcent = fn ($n) => $n === null ? '—' : number_format($n, 1, ',', ' ').' %';
    $variation = fn ($v) => $v === null ? 'pas de référence' : (($v > 0 ? '+' : '').number_format($v, 1, ',', ' ').' % vs période précédente');
    $totalServices = max(1, array_sum(array_column($stats['services'], 'quantite')));
    $t = $kpi['transferts'];
@endphp

@section('titre', "Rapport d'activité")
@section('sous-titre', $stats['periode']['libelle'].' · du '.$stats['periode']['du'].' au '.$stats['periode']['au'])
@section('pied', "Rapport d'activité")

@push('styles')
<style>
    .barre { height: 7px; background: #e3e7eb; border-radius: 4px; }
    .barre div { height: 7px; background: #1f4e79; border-radius: 4px; }
    table.deux { width: 100%; border-collapse: separate; border-spacing: 0; }
    table.deux > tbody > tr > td { vertical-align: top; width: 49%; }
</style>
@endpush

@section('contenu')
    <div class="filtres"><b>Magasins :</b> {{ implode(', ', $stats['magasins']) ?: '—' }} &nbsp;·&nbsp; <b>Période de comparaison :</b> {{ $stats['periode']['precedente'] }}</div>

    <table class="infos">
        <tr>
            <td class="bloc"><div class="etiquette">Quantités consommées</div><div class="valeur">{{ $nombre($kpi['consommation']['valeur']) }}</div><div class="detail">{{ $variation($kpi['consommation']['variation']) }}</div></td>
            <td class="espace"></td>
            <td class="bloc"><div class="etiquette">Bons de sortie</div><div class="valeur">{{ $nombre($kpi['bons']['valeur']) }}</div><div class="detail">{{ $variation($kpi['bons']['variation']) }}</div></td>
            <td class="espace"></td>
            <td class="bloc"><div class="etiquette">Services servis</div><div class="valeur">{{ $kpi['services'] }}</div><div class="detail">{{ $kpi['articles'] }} article(s) distribués</div></td>
        </tr>
        <tr><td colspan="5" style="height: 6px"></td></tr>
        <tr>
            <td class="bloc"><div class="etiquette">Transferts</div><div class="valeur">{{ $t['nombre'] }}</div><div class="detail">{{ $pourcent($t['conformite']) }} des lignes sans écart · {{ $t['en_transit'] }} en transit</div></td>
            <td class="espace"></td>
            <td class="bloc"><div class="etiquette">Alertes de stock</div><div class="valeur {{ $kpi['alertes']['rupture'] + $kpi['alertes']['sous'] ? 'negatif' : '' }}">{{ $kpi['alertes']['rupture'] + $kpi['alertes']['sous'] }}</div><div class="detail">{{ $kpi['alertes']['rupture'] }} en rupture · {{ $kpi['alertes']['sous'] }} sous le seuil</div></td>
            <td class="espace"></td>
            <td class="bloc"><div class="etiquette">Fiabilité d'inventaire</div><div class="valeur">{{ $pourcent($kpi['fiabilite']) }}</div><div class="detail">Lignes sans écart sur 12 mois</div></td>
        </tr>
    </table>

    <table class="deux" style="margin-top: 6px">
        <tr>
            <td>
                <h2 class="section">Articles les plus consommés</h2>
                <table class="lignes">
                    <thead><tr><th class="centre" style="width: 7%">#</th><th>Article</th><th class="num" style="width: 26%">Quantité</th></tr></thead>
                    <tbody>
                        @forelse ($stats['top_articles'] as $article)
                            <tr><td class="centre muet">{{ $loop->iteration }}</td><td>{{ $article['libelle'] }}</td><td class="num">{{ $nombre($article['quantite']) }} <span class="muet">{{ $article['unite'] }}</span></td></tr>
                        @empty
                            <tr><td colspan="3" class="centre muet">Aucune consommation</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 2%"></td>
            <td>
                <h2 class="section">Consommation par service</h2>
                <table class="lignes">
                    <thead><tr><th>Service</th><th class="num" style="width: 22%">Quantité</th><th style="width: 30%">Part</th></tr></thead>
                    <tbody>
                        @forelse ($stats['services'] as $service)
                            @php $part = $service['quantite'] * 100 / $totalServices; @endphp
                            <tr>
                                <td>{{ $service['libelle'] }}</td>
                                <td class="num">{{ $nombre($service['quantite']) }}</td>
                                <td><div class="barre"><div style="width: {{ round($part) }}%"></div></div><span class="muet">{{ number_format($part, 1, ',', ' ') }} %</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="centre muet">Aucune consommation</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h2 class="section">Santé du stock par magasin (à la date d'édition)</h2>
    <table class="lignes">
        <thead><tr><th>Magasin</th><th class="num">Rupture</th><th class="num">Sous le seuil</th><th class="num">Au seuil</th><th class="num">Normal</th></tr></thead>
        <tbody>
            @foreach ($stats['sante']['magasins'] as $i => $magasin)
                <tr>
                    <td>{{ $magasin }}</td>
                    <td class="num {{ $stats['sante']['rupture'][$i] ? 'negatif' : '' }}">{{ $stats['sante']['rupture'][$i] }}</td>
                    <td class="num">{{ $stats['sante']['sous'][$i] }}</td>
                    <td class="num">{{ $stats['sante']['seuil'][$i] }}</td>
                    <td class="num">{{ $stats['sante']['normal'][$i] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="section">Flux entre magasins</h2>
    <table class="lignes">
        <thead><tr><th>Depuis</th><th>Vers</th><th class="num">Transferts</th><th class="num">Quantité expédiée</th><th class="num">Écart de réception</th></tr></thead>
        <tbody>
            @forelse ($stats['flux'] as $flux)
                <tr><td>{{ $flux['source'] }}</td><td>{{ $flux['destination'] }}</td><td class="num">{{ $flux['transferts'] }}</td><td class="num">{{ $nombre($flux['quantite']) }}</td>
                    <td class="num {{ $flux['ecart'] < 0 ? 'negatif' : '' }}">{{ $flux['ecart'] ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="5" class="centre muet">Aucun transfert sur la période</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
