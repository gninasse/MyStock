@extends('layouts.lumiere')
@section('title', 'Articles')

@section('content')
<div class="lumiere-card">

    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-box me-2"></i>Gestion des Articles</span>
        <button class="btn btn-light ms-auto" id="btn-add-article">
            <i class="fas fa-plus"></i> Ajouter un article
        </button>
    </div>

    <div class="lumiere-card-body">
        <table id="articles-table" class="table spec-table w-100">
            <thead><tr>
                <th>Code</th><th>Désignation</th>
                <th>Catégorie</th><th>Unité</th>
                <th>Stock Min</th><th>Statut</th><th>Actions</th>
            </tr></thead>
        </table>
    </div>
</div>

<!-- Modale Ajout/Édition -->
<div class="modal fade" id="articleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>
                    <span id="modal-title-text">Nouvel article</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="article-form">
                    <input type="hidden" id="article-id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Désignation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Choisir une catégorie...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unité (ex: Pièce, Kg, Litre...) <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" placeholder="Pièce" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock minimum de sécurité <span class="text-danger">*</span></label>
                            <input type="number" name="min_stock" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" id="btn-save-article">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const articlesUrl = `{{ route('inventory.articles.index') }}`;

const table = $('#articles-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${articlesUrl}/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'designation' },
        { data: 'category.name', defaultContent: '—' },
        { data: 'unit' },
        { data: 'min_stock' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: id => `
            <button class="btn btn-sm btn-outline-primary edit-article" data-id="${id}">
                <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-danger del-article ms-1" data-id="${id}">
                <i class="fas fa-trash"></i>
            </button>
        ` },
    ]
});

/* Ajouter */
$('#btn-add-article').on('click', () => {
    $('#article-form')[0].reset();
    $('#article-id').val('');
    $('#modal-title-text').text('Nouvel article');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('articleModal')).show();
});

/* Enregistrer */
$('#btn-save-article').on('click', () => {
    const id   = $('#article-id').val();
    const url  = id ? `${articlesUrl}/${id}` : articlesUrl;
    const data = Object.fromEntries(new FormData(document.getElementById('article-form')));
    
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
         bootstrap.Modal.getInstance(document.getElementById('articleModal')).hide(); 
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
$(document).on('click', '.edit-article', function() {
    const id = $(this).data('id');
    $.get(`${articlesUrl}/${id}/edit`)
     .done(article => {
         $('#article-id').val(article.id);
         $('[name="code"]').val(article.code);
         $('[name="designation"]').val(article.designation);
         $('[name="category_id"]').val(article.category_id);
         $('[name="unit"]').val(article.unit);
         $('[name="min_stock"]').val(article.min_stock);
         $('[name="description"]').val(article.description);
         $('#modal-title-text').text('Modifier l\'article');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('articleModal')).show();
     });
});

/* Supprimer */
$(document).on('click', '.del-article', function() {
    const id = $(this).data('id');
    if (confirm('Voulez-vous vraiment supprimer cet article ?')) {
        $.ajax({
            url: `${articlesUrl}/${id}`,
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
