@extends('layouts.lumiere')
@section('title', 'Magasins')

@section('content')
<div class="lumiere-card">

    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-warehouse me-2"></i>Gestion des Magasins</span>
        <button class="btn btn-light ms-auto" id="btn-add-store">
            <i class="fas fa-plus"></i> Ajouter un magasin
        </button>
    </div>

    <div class="lumiere-card-body">
        <table id="stores-table" class="table spec-table w-100">
            <thead><tr>
                <th>Code</th><th>Libellé</th>
                <th>Responsable</th><th>Localisation</th>
                <th>Statut</th><th>Actions</th>
            </tr></thead>
        </table>
    </div>
</div>

<!-- Modale Ajout/Édition -->
<div class="modal fade" id="storeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-warehouse me-2"></i>
                    <span id="modal-title-text">Nouveau magasin</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="store-form">
                    <input type="hidden" id="store-id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsable</label>
                            <input type="text" name="manager_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Localisation</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" id="btn-save-store">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale Articles Fréquents -->
<div class="modal fade" id="frequentItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list-ul me-2"></i>
                    Articles fréquents : <span id="frequent-store-name" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="frequent-store-id">
                
                <!-- Recherche articles -->
                <div class="mb-4 position-relative">
                    <label class="form-label fw-bold">Rechercher un article à ajouter</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="article-search-input" class="form-control" placeholder="Rechercher par code ou désignation...">
                    </div>
                    <div id="search-results-dropdown" class="dropdown-menu w-100 shadow" style="display: none; max-height: 250px; overflow-y: auto; z-index: 1050;"></div>
                </div>

                <!-- Table des Articles Sélectionnés -->
                <div class="table-responsive">
                    <table class="table align-middle" id="selected-articles-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 20%">Code</th>
                                <th style="width: 45%">Désignation</th>
                                <th style="width: 20%">Catégorie</th>
                                <th style="width: 15%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="frequent-articles-tbody">
                            <!-- Articles chargés dynamiquement -->
                        </tbody>
                    </table>
                    <div id="no-frequent-articles" class="text-center py-4 text-muted">
                        Aucun article fréquent configuré pour ce magasin.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button class="btn btn-primary" id="btn-save-frequent">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const storesUrl = `{{ route('inventory.stores.index') }}`;

const table = $('#stores-table').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: `${storesUrl}/data`, type: 'GET' },
    columns: [
        { data: 'code' },
        { data: 'name' },
        { data: 'manager_name', defaultContent: '—' },
        { data: 'location', defaultContent: '—' },
        { data: 'is_active', render: d => d
            ? '<span class="pill pill-green">Actif</span>'
            : '<span class="pill pill-gray">Inactif</span>' },
        { data: 'id', render: (id, type, row) => `
            <button class="btn btn-sm btn-outline-success configure-frequent" data-id="${id}" data-name="${row.name}" title="Articles fréquents">
                <i class="fas fa-list-ul"></i>
            </button>
            <button class="btn btn-sm btn-outline-primary edit-store ms-1" data-id="${id}">
                <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-danger del-store ms-1" data-id="${id}">
                <i class="fas fa-trash"></i>
            </button>
        ` },
    ]
});

/* Ajouter */
$('#btn-add-store').on('click', () => {
    $('#store-form')[0].reset();
    $('#store-id').val('');
    $('#modal-title-text').text('Nouveau magasin');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    new bootstrap.Modal(document.getElementById('storeModal')).show();
});

/* Enregistrer */
$('#btn-save-store').on('click', () => {
    const id   = $('#store-id').val();
    const url  = id ? `${storesUrl}/${id}` : storesUrl;
    const data = Object.fromEntries(new FormData(document.getElementById('store-form')));
    
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
         bootstrap.Modal.getInstance(document.getElementById('storeModal')).hide(); 
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
$(document).on('click', '.edit-store', function() {
    const id = $(this).data('id');
    $.get(`${storesUrl}/${id}/edit`)
     .done(store => {
         $('#store-id').val(store.id);
         $('[name="code"]').val(store.code);
         $('[name="name"]').val(store.name);
         $('[name="manager_name"]').val(store.manager_name);
         $('[name="phone"]').val(store.phone);
         $('[name="location"]').val(store.location);
         $('#modal-title-text').text('Modifier le magasin');
         $('.is-invalid').removeClass('is-invalid');
         $('.invalid-feedback').remove();
         new bootstrap.Modal(document.getElementById('storeModal')).show();
     });
});

/* Supprimer */
$(document).on('click', '.del-store', function() {
    const id = $(this).data('id');
    if (confirm('Voulez-vous vraiment supprimer ce magasin ?')) {
        $.ajax({
            url: `${storesUrl}/${id}`,
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

/* Configuration des articles fréquents */
$(document).on('click', '.configure-frequent', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    
    $('#frequent-store-id').val(id);
    $('#frequent-store-name').text(name);
    $('#frequent-articles-tbody').empty();
    $('#article-search-input').val('');
    $('#search-results-dropdown').hide();
    
    // Charger les articles fréquents actuels
    $.get(`${storesUrl}/${id}/frequent`)
     .done(items => {
         if (items.length > 0) {
             $('#no-frequent-articles').hide();
             items.forEach(item => {
                 if (item.article) {
                     addArticleRow(item.article_id, item.article.code, item.article.designation, item.article.category?.name || '—');
                 }
             });
         } else {
             $('#no-frequent-articles').show();
         }
         new bootstrap.Modal(document.getElementById('frequentItemsModal')).show();
     });
});

function addArticleRow(id, code, designation, category) {
    // Vérifier si l'article est déjà présent
    if ($(`#frequent-articles-tbody tr[data-article-id="${id}"]`).length > 0) {
        if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
            Lumiere.showToast('Cet article est déjà dans la liste', 'warning');
        } else {
            alert('Cet article est déjà dans la liste');
        }
        return;
    }
    
    const row = `
        <tr data-article-id="${id}" class="frequent-article-row">
            <td><code class="text-primary">${code}</code></td>
            <td class="fw-semibold">${designation}</td>
            <td><span class="badge bg-light text-dark border">${category}</span></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light move-up-article" title="Monter"><i class="fas fa-arrow-up text-secondary"></i></button>
                <button type="button" class="btn btn-sm btn-light move-down-article ms-1" title="Descendre"><i class="fas fa-arrow-down text-secondary"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-article ms-1" title="Retirer"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `;
    
    $('#frequent-articles-tbody').append(row);
    $('#no-frequent-articles').hide();
}

// Recherche articles autocomplete
let searchTimeout = null;
$('#article-search-input').on('input', function() {
    const q = $(this).val();
    clearTimeout(searchTimeout);
    
    if (q.length < 2) {
        $('#search-results-dropdown').hide();
        return;
    }
    
    searchTimeout = setTimeout(() => {
        $.get(`{{ route('inventory.articles.search') }}`, { q })
         .done(articles => {
             const dropdown = $('#search-results-dropdown');
             dropdown.empty();
             
             if (articles.length === 0) {
                 dropdown.append('<div class="dropdown-item text-muted">Aucun article trouvé</div>');
             } else {
                 articles.forEach(article => {
                     dropdown.append(`
                         <a href="#" class="dropdown-item py-2 add-searched-article" 
                            data-id="${article.id}" 
                            data-code="${article.code}" 
                            data-designation="${article.designation}" 
                            data-category="${article.category?.name || '—'}">
                             <strong>${article.code}</strong> - ${article.designation}
                         </a>
                     `);
                 });
             }
             dropdown.show();
         });
    }, 300);
});

// Ajouter à partir de l'autocomplete
$(document).on('click', '.add-searched-article', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    const code = $(this).data('code');
    const designation = $(this).data('designation');
    const category = $(this).data('category');
    
    addArticleRow(id, code, designation, category);
    
    $('#article-search-input').val('');
    $('#search-results-dropdown').hide();
});

// Fermer l'autocomplete en cliquant ailleurs
$(document).on('click', function(e) {
    if (!$(e.target).closest('#article-search-input, #search-results-dropdown').length) {
        $('#search-results-dropdown').hide();
    }
});

// Trier - Monter
$(document).on('click', '.move-up-article', function() {
    const row = $(this).closest('tr');
    row.prev().before(row);
});

// Trier - Descendre
$(document).on('click', '.move-down-article', function() {
    const row = $(this).closest('tr');
    row.next().after(row);
});

// Retirer
$(document).on('click', '.remove-article', function() {
    $(this).closest('tr').remove();
    if ($('#frequent-articles-tbody tr').length === 0) {
        $('#no-frequent-articles').show();
    }
});

// Enregistrer
$('#btn-save-frequent').on('click', function() {
    const storeId = $('#frequent-store-id').val();
    const articleIds = $('.frequent-article-row').map(function() {
        return $(this).data('article-id');
    }).get();
    
    $.ajax({
        url: `${storesUrl}/${storeId}/frequent`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            article_ids: articleIds
        }
    }).done(r => {
        if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
            Lumiere.showToast(r.message);
        } else {
            alert(r.message);
        }
        bootstrap.Modal.getInstance(document.getElementById('frequentItemsModal')).hide();
    }).fail(xhr => {
        const msg = xhr.responseJSON?.message || 'Erreur lors de la sauvegarde';
        if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
            Lumiere.showToast(msg, 'danger');
        } else {
            alert(msg);
        }
    });
});
</script>
@endpush
