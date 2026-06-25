@extends('layouts.lumiere')
@section('title', 'Structure Organisationnelle')

@section('content')
<div class="lumiere-card">

    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-sitemap me-2"></i>Structure Organisationnelle</span>
    </div>

    <div class="lumiere-card-body">
        <!-- Nav Tabs -->
        <ul class="nav nav-tabs mb-4" id="orgTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="directions-tab" data-bs-toggle="tab" data-bs-target="#directions-pane" type="button" role="tab"><i class="fas fa-building me-2"></i>Directions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-pane" type="button" role="tab"><i class="fas fa-briefcase me-2"></i>Services</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="units-tab" data-bs-toggle="tab" data-bs-target="#units-pane" type="button" role="tab"><i class="fas fa-users me-2"></i>Unités</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="orgTabsContent">
            <!-- Pane Directions -->
            <div class="tab-pane fade show active" id="directions-pane" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary btn-sm text-white" id="btn-add-direction">
                        <i class="fas fa-plus"></i> Ajouter une direction
                    </button>
                </div>
                <table id="directions-table" class="table spec-table w-100">
                    <thead><tr>
                        <th>Code</th><th>Libellé</th><th>Statut</th><th>Actions</th>
                    </tr></thead>
                </table>
            </div>

            <!-- Pane Services -->
            <div class="tab-pane fade" id="services-pane" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary btn-sm text-white" id="btn-add-service">
                        <i class="fas fa-plus"></i> Ajouter un service
                    </button>
                </div>
                <table id="services-table" class="table spec-table w-100">
                    <thead><tr>
                        <th>Code</th><th>Libellé</th><th>Direction Parente</th><th>Statut</th><th>Actions</th>
                    </tr></thead>
                </table>
            </div>

            <!-- Pane Unités -->
            <div class="tab-pane fade" id="units-pane" role="tabpanel">
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-primary btn-sm text-white" id="btn-add-unit">
                        <i class="fas fa-plus"></i> Ajouter une unité
                    </button>
                </div>
                <table id="units-table" class="table spec-table w-100">
                    <thead><tr>
                        <th>Code</th><th>Libellé</th><th>Service Parent</th><th>Direction</th><th>Statut</th><th>Actions</th>
                    </tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Direction -->
<div class="modal fade" id="directionModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-building me-2"></i><span id="direction-modal-title">Nouvelle Direction</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="direction-form">
                    <input type="hidden" id="direction-id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Ex: DG, DAF" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Direction Générale" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button class="btn btn-primary text-white" id="btn-save-direction"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Service -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-briefcase me-2"></i><span id="service-modal-title">Nouveau Service</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="service-form">
                    <input type="hidden" id="service-id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Direction Parente <span class="text-danger">*</span></label>
                            <select name="direction_id" id="service-direction-select" class="form-select" required>
                                <option value="">Choisir une direction...</option>
                                @foreach($directions as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Ex: SRV-INF" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Service Informatique" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button class="btn btn-primary text-white" id="btn-save-service"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Unité -->
<div class="modal fade" id="unitModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users me-2"></i><span id="unit-modal-title">Nouvelle Unité</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="unit-form">
                    <input type="hidden" id="unit-id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Service Parent <span class="text-danger">*</span></label>
                            <select name="service_id" id="unit-service-select" class="form-select" required>
                                <option value="">Choisir un service...</option>
                                @foreach($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->direction?->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Ex: UNT-RES" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Unité Réseau" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Annuler</button>
                <button class="btn btn-primary text-white" id="btn-save-unit"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const baseOrgUrl = `{{ url('organizations') }}`;

// ── Directions Datatable
const directionsTable = $('#directions-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${baseOrgUrl}/directions/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: id => `
            <button class="btn btn-sm btn-outline-primary edit-direction" data-id="${id}"><i class="fas fa-pen"></i></button>
            <button class="btn btn-sm btn-danger del-direction ms-1" data-id="${id}"><i class="fas fa-trash"></i></button>
        ` }
    ]
});

// ── Services Datatable
const servicesTable = $('#services-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${baseOrgUrl}/services/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'direction.name', defaultContent: '—' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: id => `
            <button class="btn btn-sm btn-outline-primary edit-service" data-id="${id}"><i class="fas fa-pen"></i></button>
            <button class="btn btn-sm btn-danger del-service ms-1" data-id="${id}"><i class="fas fa-trash"></i></button>
        ` }
    ]
});

// ── Unités Datatable
const unitsTable = $('#units-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${baseOrgUrl}/units/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'service.name', defaultContent: '—' },
        { data: 'service.direction.code', defaultContent: '—' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: id => `
            <button class="btn btn-sm btn-outline-primary edit-unit" data-id="${id}"><i class="fas fa-pen"></i></button>
            <button class="btn btn-sm btn-danger del-unit ms-1" data-id="${id}"><i class="fas fa-trash"></i></button>
        ` }
    ]
});

// Helper to refresh dropdown options dynamically when modifications are made
function refreshDropdowns() {
    $.get(baseOrgUrl, function(data) {
        // Find direction dropdown in service modal
        const dirDropdown = $(data).find('#service-direction-select').html();
        $('#service-direction-select').html(dirDropdown);

        // Find service dropdown in unit modal
        const srvDropdown = $(data).find('#unit-service-select').html();
        $('#unit-service-select').html(srvDropdown);
    });
}

// ── CRUD Directions logic
$('#btn-add-direction').on('click', () => {
    $('#direction-form')[0].reset();
    $('#direction-id').val('');
    $('#direction-modal-title').text('Nouvelle Direction');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('directionModal')).show();
});

$('#btn-save-direction').on('click', () => {
    const id = $('#direction-id').val();
    const url = id ? `${baseOrgUrl}/directions/${id}` : `${baseOrgUrl}/directions`;
    const data = Object.fromEntries(new FormData(document.getElementById('direction-form')));
    data._token = '{{ csrf_token() }}';
    if (id) data._method = 'PUT';

    $.ajax({ url, method: 'POST', data })
     .done(r => {
         Lumiere.showToast(r.message);
         directionsTable.ajax.reload();
         refreshDropdowns();
         bootstrap.Modal.getInstance(document.getElementById('directionModal')).hide();
     })
     .fail(xhr => {
         const errors = xhr.responseJSON?.errors;
         if (errors) {
             $('.is-invalid').removeClass('is-invalid');
             $('.invalid-feedback').remove();
             Object.entries(errors).forEach(([k,v]) =>
                 $(`#direction-form [name="${k}"]`).addClass('is-invalid').after(`<div class="invalid-feedback">${v[0]}</div>`)
             );
         }
     });
});

$(document).on('click', '.edit-direction', function() {
    const id = $(this).data('id');
    $.get(`${baseOrgUrl}/directions/${id}/edit`)
     .done(dir => {
         $('#direction-id').val(dir.id);
         $(`#direction-form [name="code"]`).val(dir.code);
         $(`#direction-form [name="name"]`).val(dir.name);
         $('#direction-modal-title').text('Modifier la Direction');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('directionModal')).show();
     });
});

$(document).on('click', '.del-direction', function() {
    const id = $(this).data('id');
    Lumiere.confirm({
        title: 'Suppression',
        message: 'Voulez-vous vraiment supprimer cette direction ?',
        onConfirm: () => {
            $.ajax({
                url: `${baseOrgUrl}/directions/${id}`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' }
            }).done(r => {
                Lumiere.showToast(r.message);
                directionsTable.ajax.reload();
                refreshDropdowns();
            }).fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Erreur lors de la suppression';
                Lumiere.showToast(msg, 'danger');
            });
        }
    });
});

// ── CRUD Services logic
$('#btn-add-service').on('click', () => {
    $('#service-form')[0].reset();
    $('#service-id').val('');
    $('#service-modal-title').text('Nouveau Service');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('serviceModal')).show();
});

$('#btn-save-service').on('click', () => {
    const id = $('#service-id').val();
    const url = id ? `${baseOrgUrl}/services/${id}` : `${baseOrgUrl}/services`;
    const data = Object.fromEntries(new FormData(document.getElementById('service-form')));
    data._token = '{{ csrf_token() }}';
    if (id) data._method = 'PUT';

    $.ajax({ url, method: 'POST', data })
     .done(r => {
         Lumiere.showToast(r.message);
         servicesTable.ajax.reload();
         refreshDropdowns();
         bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
     })
     .fail(xhr => {
         const errors = xhr.responseJSON?.errors;
         if (errors) {
             $('.is-invalid').removeClass('is-invalid');
             $('.invalid-feedback').remove();
             Object.entries(errors).forEach(([k,v]) =>
                 $(`#service-form [name="${k}"]`).addClass('is-invalid').after(`<div class="invalid-feedback">${v[0]}</div>`)
             );
         }
     });
});

$(document).on('click', '.edit-service', function() {
    const id = $(this).data('id');
    $.get(`${baseOrgUrl}/services/${id}/edit`)
     .done(srv => {
         $('#service-id').val(srv.id);
         $(`#service-form [name="direction_id"]`).val(srv.direction_id);
         $(`#service-form [name="code"]`).val(srv.code);
         $(`#service-form [name="name"]`).val(srv.name);
         $('#service-modal-title').text('Modifier le Service');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('serviceModal')).show();
     });
});

$(document).on('click', '.del-service', function() {
    const id = $(this).data('id');
    Lumiere.confirm({
        title: 'Suppression',
        message: 'Voulez-vous vraiment supprimer ce service ?',
        onConfirm: () => {
            $.ajax({
                url: `${baseOrgUrl}/services/${id}`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' }
            }).done(r => {
                Lumiere.showToast(r.message);
                servicesTable.ajax.reload();
                refreshDropdowns();
            }).fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Erreur lors de la suppression';
                Lumiere.showToast(msg, 'danger');
            });
        }
    });
});

// ── CRUD Unités logic
$('#btn-add-unit').on('click', () => {
    $('#unit-form')[0].reset();
    $('#unit-id').val('');
    $('#unit-modal-title').text('Nouvelle Unité');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('unitModal')).show();
});

$('#btn-save-unit').on('click', () => {
    const id = $('#unit-id').val();
    const url = id ? `${baseOrgUrl}/units/${id}` : `${baseOrgUrl}/units`;
    const data = Object.fromEntries(new FormData(document.getElementById('unit-form')));
    data._token = '{{ csrf_token() }}';
    if (id) data._method = 'PUT';

    $.ajax({ url, method: 'POST', data })
     .done(r => {
         Lumiere.showToast(r.message);
         unitsTable.ajax.reload();
         bootstrap.Modal.getInstance(document.getElementById('unitModal')).hide();
     })
     .fail(xhr => {
         const errors = xhr.responseJSON?.errors;
         if (errors) {
             $('.is-invalid').removeClass('is-invalid');
             $('.invalid-feedback').remove();
             Object.entries(errors).forEach(([k,v]) =>
                 $(`#unit-form [name="${k}"]`).addClass('is-invalid').after(`<div class="invalid-feedback">${v[0]}</div>`)
             );
         }
     });
});

$(document).on('click', '.edit-unit', function() {
    const id = $(this).data('id');
    $.get(`${baseOrgUrl}/units/${id}/edit`)
     .done(unt => {
         $('#unit-id').val(unt.id);
         $(`#unit-form [name="service_id"]`).val(unt.service_id);
         $(`#unit-form [name="code"]`).val(unt.code);
         $(`#unit-form [name="name"]`).val(unt.name);
         $('#unit-modal-title').text('Modifier l\'Unité');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('unitModal')).show();
     });
});

$(document).on('click', '.del-unit', function() {
    const id = $(this).data('id');
    Lumiere.confirm({
        title: 'Suppression',
        message: 'Voulez-vous vraiment supprimer cette unité ?',
        onConfirm: () => {
            $.ajax({
                url: `${baseOrgUrl}/units/${id}`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', _method: 'DELETE' }
            }).done(r => {
                Lumiere.showToast(r.message);
                unitsTable.ajax.reload();
            }).fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Erreur lors de la suppression';
                Lumiere.showToast(msg, 'danger');
            });
        }
    });
});
</script>
@endpush
