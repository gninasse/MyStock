@extends('core::layouts.master')

@php
    $kpi = $stats['kpi'];
    $nombre = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pourcent = fn ($n) => $n === null ? '—' : number_format($n, 1, ',', ' ').' %';
    $variation = function ($v) {
        if ($v === null) return '<span class="text-body-secondary">pas de référence sur la période précédente</span>';
        $icone = $v > 0 ? 'bi-arrow-up-right' : ($v < 0 ? 'bi-arrow-down-right' : 'bi-arrow-right');
        return '<span class="kpi-variation"><i class="bi '.$icone.'"></i> '.($v > 0 ? '+' : '').number_format($v, 1, ',', ' ').' %</span> <span class="text-body-secondary">vs période précédente</span>';
    };
    $transferts = $kpi['transferts'];
    $alertes = $kpi['alertes'];
@endphp

@section('title', 'Statistiques')
@section('header', 'Statistiques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('etats.tableau-de-bord') }}">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Statistiques</li>
@endsection

@section('content')
<form method="GET" class="stats-filtres d-flex flex-wrap align-items-center gap-2 mb-2">
    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Période">
        @foreach ($periodes as $cle => $libelle)
            <input type="radio" class="btn-check" name="periode" id="periode-{{ $cle }}" value="{{ $cle }}" @checked($stats['periode']['cle'] === $cle) onchange="this.form.submit()">
            <label class="btn btn-outline-primary" for="periode-{{ $cle }}">{{ $libelle }}</label>
        @endforeach
    </div>
    <select class="form-select form-select-sm w-auto" name="magasin_id" aria-label="Magasin" onchange="this.form.submit()">
        <option value="">Tous mes magasins</option>
        @foreach ($magasins as $magasin)
            <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
        @endforeach
    </select>
    @can('etats.export')
        <button type="button" class="btn btn-sm btn-danger ms-auto"
                data-apercu-pdf="{{ route('etats.statistiques.rapport', array_filter($filtres)) }}" data-titre="Rapport d'activité — {{ $stats['periode']['libelle'] }}">
            <i class="fas fa-file-pdf"></i> Rapport d'activité
        </button>
    @endcan
</form>
<p class="text-body-secondary small mb-3">
    Du {{ $stats['periode']['du'] }} au {{ $stats['periode']['au'] }} · comparé au {{ $stats['periode']['precedente'] }}
    · {{ count($stats['magasins']) > 1 ? count($stats['magasins']).' magasins' : ($stats['magasins'][0] ?? 'aucun magasin') }}
</p>

<div class="row g-3 mb-3">
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100"><div class="card-body">
            <div class="kpi-libelle"><i class="bi bi-box-arrow-up-right"></i> Quantités consommées</div>
            <div class="kpi-valeur">{{ $nombre($kpi['consommation']['valeur']) }}</div>
            <div class="kpi-pied">{!! $variation($kpi['consommation']['variation']) !!}</div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100"><div class="card-body">
            <div class="kpi-libelle"><i class="bi bi-receipt"></i> Bons de sortie</div>
            <div class="kpi-valeur">{{ $nombre($kpi['bons']['valeur']) }}</div>
            <div class="kpi-pied">{!! $variation($kpi['bons']['variation']) !!}</div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100"><div class="card-body">
            <div class="kpi-libelle"><i class="bi bi-people"></i> Services servis</div>
            <div class="kpi-valeur">{{ $kpi['services'] }}</div>
            <div class="kpi-pied text-body-secondary">{{ $kpi['articles'] }} article(s) différents distribués</div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100"><div class="card-body">
            <div class="kpi-libelle"><i class="bi bi-truck"></i> Transferts</div>
            <div class="kpi-valeur">{{ $transferts['nombre'] }}</div>
            <div class="kpi-pied text-body-secondary">
                {{ $pourcent($transferts['conformite']) }} des lignes reçues sans écart
                @if ($transferts['delai_heures'] !== null)· réception en {{ match (true) { $transferts['delai_heures'] < 1 => 'moins d\'1 h', $transferts['delai_heures'] < 48 => number_format($transferts['delai_heures'], 1, ',', ' ').' h', default => number_format($transferts['delai_heures'] / 24, 1, ',', ' ').' j' } }} en moyenne @endif
            </div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100 {{ $alertes['rupture'] + $alertes['sous'] ? 'kpi-alerte' : '' }}"><div class="card-body">
            <div class="kpi-libelle"><i class="bi bi-exclamation-triangle"></i> Alertes de stock</div>
            <div class="kpi-valeur">{{ $alertes['rupture'] + $alertes['sous'] }}</div>
            <div class="kpi-pied text-body-secondary">{{ $alertes['rupture'] }} en rupture · {{ $alertes['sous'] }} sous le seuil · {{ $alertes['seuil'] }} au seuil</div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi card h-100"><div class="card-body">
            @if ($kpi['peremptions'] !== null && $kpi['peremptions'] > 0)
                <div class="kpi-libelle"><i class="bi bi-hourglass-split"></i> Lots à surveiller</div>
                <div class="kpi-valeur">{{ $kpi['peremptions'] }}</div>
                <div class="kpi-pied text-body-secondary">périmés ou proches de la péremption · fiabilité d'inventaire {{ $pourcent($kpi['fiabilite']) }}</div>
            @else
                <div class="kpi-libelle"><i class="bi bi-clipboard-check"></i> Fiabilité d'inventaire</div>
                <div class="kpi-valeur">{{ $pourcent($kpi['fiabilite']) }}</div>
                <div class="kpi-pied text-body-secondary">lignes comptées sans écart sur 12 mois</div>
            @endif
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header border-0 pb-0"><h3 class="card-title fw-semibold float-none mb-0">Activité du stock</h3><div class="small text-body-secondary">Entrées, consommation des services et transferts expédiés (quantités)</div></div>
            <div class="card-body pt-1"><div id="graphe-evolution" class="graphe"></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header border-0 pb-0"><h3 class="card-title fw-semibold float-none mb-0">Consommation par service</h3><div class="small text-body-secondary">Répartition des quantités consommées</div></div>
            <div class="card-body pt-1"><div id="graphe-services" class="graphe"></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header border-0 pb-0"><h3 class="card-title fw-semibold float-none mb-0">Articles les plus consommés</h3><div class="small text-body-secondary">Top 10 sur la période</div></div>
            <div class="card-body pt-1"><div id="graphe-articles" class="graphe"></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header border-0 pb-0"><h3 class="card-title fw-semibold float-none mb-0">Santé du stock par magasin</h3><div class="small text-body-secondary">Articles suivis selon leur niveau par rapport au stock minimum (aujourd'hui)</div></div>
            <div class="card-body pt-1"><div id="graphe-sante" class="graphe"></div></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header border-0"><h3 class="card-title fw-semibold">Flux entre magasins</h3></div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Depuis</th><th></th><th>Vers</th><th class="text-end">Transferts</th><th class="text-end">Quantité expédiée</th><th class="text-end">Écart de réception</th></tr></thead>
            <tbody>
                @forelse ($stats['flux'] as $flux)
                    <tr>
                        <td>{{ $flux['source'] }}</td>
                        <td class="text-body-secondary"><i class="bi bi-arrow-right"></i></td>
                        <td>{{ $flux['destination'] }}</td>
                        <td class="text-end">{{ $flux['transferts'] }}</td>
                        <td class="text-end">{{ $nombre($flux['quantite']) }}</td>
                        <td class="text-end">
                            @if ($flux['ecart'] < 0)
                                <span class="badge text-bg-danger-subtle text-danger-emphasis">{{ $flux['ecart'] }} ({{ number_format($flux['ecart'] * 100 / max(1, $flux['quantite']), 1, ',', ' ') }} %)</span>
                            @else
                                <span class="badge text-bg-success-subtle text-success-emphasis">Aucun</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">Aucun transfert sur la période</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php($graphes = ['evolution' => $stats['evolution'], 'services' => $stats['services'], 'articles' => $stats['top_articles'], 'sante' => $stats['sante']])
<script type="application/json" id="donnees-stats">{!! json_encode($graphes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@stop

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/apexcharts/apexcharts.css') }}">
<style>
    .kpi { border: 1px solid var(--bs-border-color-translucent); box-shadow: none; }
    .kpi .card-body { padding: 1rem 1.1rem; }
    .kpi-libelle { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: var(--bs-secondary-color); font-weight: 600; }
    .kpi-libelle .bi { margin-right: .3rem; }
    .kpi-valeur { font-size: 1.9rem; font-weight: 700; line-height: 1.15; margin: .35rem 0 .25rem; font-variant-numeric: tabular-nums; }
    .kpi-pied { font-size: .78rem; line-height: 1.35; }
    .kpi-variation { font-weight: 600; color: var(--bs-primary); }
    .kpi-alerte { border-color: rgba(var(--bs-danger-rgb), .35); background: rgba(var(--bs-danger-rgb), .04); }
    .kpi-alerte .kpi-valeur { color: var(--bs-danger); }
    .graphe { min-height: 320px; }
    .stats-filtres .btn-group .btn { white-space: nowrap; }
</style>
@endpush

@push('js')
<script src="{{ asset('plugins/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('js/modules/etats/statistiques.js') }}"></script>
@endpush
