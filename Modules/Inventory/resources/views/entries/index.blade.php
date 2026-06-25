@extends('layouts.lumiere')
@section('title', 'Entrées de stock')

@section('content')
<div class="lumiere-card">

    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-arrow-down me-2"></i>Historique des Entrées de stock</span>
        <a href="{{ route('inventory.entries.create') }}" class="btn btn-light ms-auto">
            <i class="fas fa-plus"></i> Nouvelle entrée
        </a>
    </div>

    <div class="lumiere-card-body">
        @if($entries->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-arrow-down fa-3x mb-3 opacity-30"></i>
                <p class="mb-0">Aucune entrée de stock enregistrée.</p>
                <a href="{{ route('inventory.entries.create') }}" class="btn btn-primary mt-3">Saisir une entrée</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table spec-table w-100">
                    <thead><tr>
                        <th>Référence</th><th>Magasin</th>
                        <th>Date de création</th><th>Saisi par</th>
                        <th>Statut</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                        @foreach($entries as $entry)
                        <tr>
                            <td><strong>{{ $entry->reference }}</strong></td>
                            <td>{{ $entry->store?->name }}</td>
                            <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $entry->user?->name ?? '—' }}</td>
                            <td>
                                @if($entry->status === 'validated')
                                    <span class="pill pill-green">Validé</span>
                                @elseif($entry->status === 'cancelled')
                                    <span class="pill pill-red">Annulé</span>
                                @else
                                    <span class="pill pill-orange">Brouillon</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('inventory.entries.show', $entry->id) }}" class="btn btn-sm btn-outline-primary" style="min-width:auto; height:32px; width:32px;" title="Détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($entry->status === 'draft')
                                    <button class="btn btn-sm btn-success ms-1 validate-entry" data-id="{{ $entry->id }}" style="min-width:auto; height:32px; width:32px;" title="Valider">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.validate-entry').on('click', function() {
        const id = $(this).data('id');
        if (typeof Lumiere !== 'undefined' && Lumiere.confirm) {
            Lumiere.confirm({
                title: 'Valider l\'entrée de stock',
                message: 'Voulez-vous vraiment valider cette entrée ? Les stocks seront mis à jour définitivement.',
                confirmText: 'Valider',
                type: 'success',
                onConfirm: () => {
                    $.post(`/inventory/entries/${id}/validate`)
                     .done(r => {
                         Lumiere.showToast(r.message, 'success');
                         setTimeout(() => location.reload(), 1500);
                     });
                }
            });
        } else {
            if (confirm('Voulez-vous vraiment valider cette entrée ? Les stocks seront mis à jour définitivement.')) {
                $.post(`/inventory/entries/${id}/validate`)
                 .done(r => {
                     alert(r.message);
                     location.reload();
                 });
            }
        }
    });
});
</script>
@endpush
