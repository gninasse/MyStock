@extends('layouts.lumiere')
@section('title', 'Détails de l\'entrée ' . $entry->reference)

@section('content')
<div class="lumiere-card mb-4">
    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-eye me-2"></i>Détails de l'entrée : <strong>{{ $entry->reference }}</strong></span>
        <a href="{{ route('inventory.entries.index') }}" class="btn btn-light ms-auto">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="lumiere-card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="fw-bold text-muted small text-uppercase">Magasin</div>
                <div class="fs-5 fw-semibold">{{ $entry->store?->name }} ({{ $entry->store?->code }})</div>
            </div>
            <div class="col-md-3">
                <div class="fw-bold text-muted small text-uppercase">Date de saisie</div>
                <div class="fs-5 fw-semibold">{{ $entry->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="col-md-3">
                <div class="fw-bold text-muted small text-uppercase">Saisi par</div>
                <div class="fs-5 fw-semibold">{{ $entry->user?->name ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="fw-bold text-muted small text-uppercase">Statut</div>
                <div>
                    @if($entry->status === 'validated')
                        <span class="pill pill-green">Validé le {{ $entry->validated_at?->format('d/m/Y H:i') }}</span>
                    @elseif($entry->status === 'cancelled')
                        <span class="pill pill-red">Annulé</span>
                    @else
                        <span class="pill pill-orange">Brouillon</span>
                    @endif
                </div>
            </div>
        </div>

        @if($entry->comment)
            <div class="info-box info mb-4">
                <div class="info-box-icon"><i class="fas fa-info-circle"></i></div>
                <div class="info-box-body">
                    <strong>Commentaires / Notes</strong>
                    {{ $entry->comment }}
                </div>
            </div>
        @endif

        <h3 class="mb-3 border-bottom pb-2">Articles de cette entrée</h3>
        <div class="table-responsive">
            <table class="table spec-table w-100">
                <thead><tr>
                    <th>Code</th><th>Désignation</th>
                    <th>Catégorie</th><th>Unité</th>
                    <th width="140">Quantité</th>
                </tr></thead>
                <tbody>
                    @foreach($entry->lines as $line)
                    <tr>
                        <td><strong>{{ $line->article?->code }}</strong></td>
                        <td>{{ $line->article?->designation }}</td>
                        <td>{{ $line->article?->category?->name ?? '—' }}</td>
                        <td>{{ $line->article?->unit }}</td>
                        <td><strong class="text-primary fs-5">{{ $line->quantity }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
