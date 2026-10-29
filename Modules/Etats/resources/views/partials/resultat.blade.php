{{-- Rendu écran d'un état ($etat), $repliable : groupes dépliables (stock par catégorie) --}}
@php
    $classes = ['alerte-egal' => 'table-warning', 'alerte-sous' => 'table-danger'];
    $nbColonnes = count($etat->colonnes);
@endphp

@if ($tropDeLignes ?? false)
    <div class="alert alert-warning no-print">
        L'état est limité à {{ config('etats.lignes_max') }} lignes : affinez les filtres pour voir l'ensemble.
    </div>
@endif

<div class="card" id="zone-etat">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <div>
            <h3 class="card-title float-none mb-0">{{ $etat->titre }}</h3>
            <div class="small text-muted">
                @foreach ($etat->filtres as $libelle => $valeur)
                    {{ $libelle }} : {{ $valeur }}@unless ($loop->last) | @endunless
                @endforeach
            </div>
        </div>
        @can('etats.export')
        <div class="ms-auto no-print">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
            <button type="button" class="btn btn-secondary btn-sm" id="btn-imprimer"
                    data-titre="{{ $etat->titre }}" data-filtres="{{ json_encode($etat->filtres) }}">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
        @endcan
    </div>

    @if ($etat->entete)
        <div class="card-body border-bottom">
            <dl class="row mb-0">
                @foreach ($etat->entete as $libelle => $valeur)
                    <dt class="col-sm-3">{{ $libelle }}</dt><dd class="col-sm-9">{{ $valeur }}</dd>
                @endforeach
            </dl>
        </div>
    @endif

    <div class="card-body p-0 table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    @foreach ($etat->colonnes as $cle => $libelle)
                        <th class="{{ in_array($cle, $etat->numeriques) ? 'text-end' : '' }}">{{ $libelle }}</th>
                    @endforeach
                </tr>
            </thead>
            @forelse ($etat->groupes as $index => $groupe)
                <tbody>
                    @if ($groupe['titre'])
                        <tr class="table-light {{ ($repliable ?? false) ? 'groupe-repliable' : '' }}" data-groupe="{{ $index }}" @if ($repliable ?? false) style="cursor: pointer" @endif>
                            <th colspan="{{ $nbColonnes }}">
                                @if ($repliable ?? false)<i class="fas fa-chevron-right me-1 no-print"></i>@endif
                                {{ $groupe['titre'] }}
                            </th>
                        </tr>
                    @endif
                    @forelse ($groupe['lignes'] as $ligne)
                        <tr class="{{ $classes[$ligne['_classe'] ?? ''] ?? '' }} {{ ($repliable ?? false) ? 'd-none ligne-groupe-'.$index : '' }}">
                            @foreach ($etat->colonnes as $cle => $libelle)
                                <td class="{{ in_array($cle, $etat->numeriques) ? 'text-end' : '' }}">{{ $ligne[$cle] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $nbColonnes }}" class="text-center text-muted">Aucune ligne</td></tr>
                    @endforelse
                    @if ($groupe['total'] ?? null)
                        <tr class="fw-bold">
                            @foreach ($etat->colonnes as $cle => $libelle)
                                <td class="{{ in_array($cle, $etat->numeriques) ? 'text-end' : '' }}">{{ $groupe['total'][$cle] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @endif
                </tbody>
            @empty
                <tbody><tr><td colspan="{{ $nbColonnes }}" class="text-center text-muted">Aucun résultat</td></tr></tbody>
            @endforelse
        </table>
    </div>

    @if ($etat->pied)
        <div class="card-footer">
            @foreach ($etat->pied as $libelle => $valeur)
                <strong>{{ $libelle }} : {{ $valeur }}</strong>
            @endforeach
        </div>
    @endif
</div>

@push('css')
<style>
    @media print {
        .app-sidebar, .app-header, .app-footer, .app-content-header, .no-print { display: none !important; }
        .app-main, .app-wrapper { margin: 0 !important; }
        #zone-etat .d-none { display: table-row !important; }
    }
</style>
@endpush

@push('js')
<script>
    $(function () {
        $('.groupe-repliable').on('click', function () {
            $(`.ligne-groupe-${$(this).data('groupe')}`).toggleClass('d-none');
            $(this).find('.fa-chevron-right, .fa-chevron-down').toggleClass('fa-chevron-right fa-chevron-down');
        });

        // L'impression est journalisée avant d'ouvrir l'aperçu (SFD 9.5)
        $('#btn-imprimer').on('click', function () {
            $.post(route('etats.impression'), { titre: $(this).data('titre'), filtres: $(this).data('filtres') })
                .always(() => window.print());
        });
    });
</script>
@endpush
