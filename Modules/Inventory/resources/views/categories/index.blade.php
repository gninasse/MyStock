@extends('layouts.lumiere')
@section('title', 'Catégories')

@section('content')
<div class="lumiere-card">

    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-tags me-2"></i>Gestion des Catégories</span>
        <button class="btn btn-light ms-auto" id="btn-add-category">
            <i class="fas fa-plus"></i> Ajouter une catégorie
        </button>
    </div>

    <div class="lumiere-card-body">
        <table id="categories-table" class="table spec-table w-100">
            <thead><tr>
                <th>Code</th><th>Nom</th>
                <th>Statut</th><th>Actions</th>
            </tr></thead>
        </table>
    </div>
</div>

<!-- Modale Ajout/Édition -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tags me-2"></i>
                    <span id="modal-title-text">Nouvelle catégorie</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="category-form">
                    <input type="hidden" id="category-id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" id="btn-save-category">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const categoriesUrl = `{{ route('inventory.categories.index') }}`;

const table = $('#categories-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${categoriesUrl}/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: id => `
            <button class="btn btn-sm btn-outline-primary edit-category" data-id="${id}">
                <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-danger del-category ms-1" data-id="${id}">
                <i class="fas fa-trash"></i>
            </button>
        ` },
    ]
});

/* Ajouter */
$('#btn-add-category').on('click', () => {
    $('#category-form')[0].reset();
    $('#category-id').val('');
    $('#modal-title-text').text('Nouvelle catégorie');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
});

/* Enregistrer */
$('#btn-save-category').on('click', () => {
    const id   = $('#category-id').val();
    const url  = id ? `${categoriesUrl}/${id}` : categoriesUrl;
    const data = Object.fromEntries(new FormData(document.getElementById('category-form')));
    
    data._token = '{{ csrf_token() }}';
    if (id) {
        data._method = 'PUT';
    }

    $.ajax({ url, method: 'POST', data })
     .done(r => { 
         if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
             Lumiere.showToast(r.message); 
         } else {
             alert(r.message);
         }
         table.ajax.reload(); 
         bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide(); 
     })
     .fail(xhr => {
         const errors = xhr.responseJSON?.errors;
         if (errors) {
             $('.is-invalid').removeClass('is-invalid');
             $('.invalid-feedback').remove();
             Object.entries(errors).forEach(([k,v]) =>
                 $(`[name="${k}"]`).addClass('is-invalid').after(`<div class="invalid-feedback">${v[0]}</div>`)
             );
         }
     });
});

/* Éditer */
$(document).on('click', '.edit-category', function() {
    const id = $(this).data('id');
    $.get(`${categoriesUrl}/${id}/edit`)
     .done(category => {
         $('#category-id').val(category.id);
         $('[name="code"]').val(category.code);
         $('[name="name"]').val(category.name);
         $('#modal-title-text').text('Modifier la catégorie');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('categoryModal')).show();
     });
});

/* Supprimer */
$(document).on('click', '.del-category', function() {
    const id = $(this).data('id');
    if (confirm('Voulez-vous vraiment supprimer cette catégorie ?')) {
        $.ajax({
            url: `${categoriesUrl}/${id}`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' }
        }).done(r => {
            if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
                Lumiere.showToast(r.message); 
            } else {
                alert(r.message);
            }
            table.ajax.reload();
        }).fail(xhr => {
            const msg = xhr.responseJSON?.message || 'Erreur lors de la suppression';
            if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
                Lumiere.showToast(msg, 'danger'); 
            } else {
                alert(msg);
            }
        });
    }
});
</script>
@endpush
