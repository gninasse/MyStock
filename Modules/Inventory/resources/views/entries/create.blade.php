@extends('layouts.lumiere')
@section('title', 'Nouvelle entrée de stock')

@section('content')
<div class="lumiere-card">
    <div class="lumiere-card-header d-flex align-items-center">
        <span><i class="fas fa-arrow-down me-2"></i>Nouvelle entrée de stock</span>
        <span class="ms-auto small" id="entry-ref"></span>
    </div>

    <div class="lumiere-card-body">
        <!-- Wizard Steps -->
        <x-wizard-steps
            :steps="['Magasin', 'Articles', 'Validation']"
            :current="0"
            id="entry-wizard"
        />

        <!-- Step 1 : Sélection magasin -->
        <div id="step-1" class="wizard-panel">
            <h3 class="mb-4">Sélectionnez le magasin</h3>
            <div class="row g-3" id="stores-grid">
                @foreach($stores as $store)
                <div class="col-md-4">
                    <div class="mini-card store-card h-100 p-3"
                         style="cursor:pointer; border: 1px solid var(--border-standard); border-radius: var(--radius);"
                         data-id="{{ $store->id }}">
                        <div class="mc-label fw-bold text-muted small text-uppercase">{{ $store->code }}</div>
                        <div class="mc-title fw-bold fs-5 my-1">{{ $store->name }}</div>
                        <div class="mc-desc text-secondary small">
                            <i class="fas fa-user-tie me-1"></i>{{ $store->manager_name ?? '—' }}
                        </div>
                        <div class="mc-desc text-secondary small">
                            <i class="fas fa-map-pin me-1"></i>{{ $store->location ?? '—' }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Step 2 : Sélection articles -->
        <div id="step-2" class="wizard-panel d-none">
            <h3 class="mb-3">Articles fréquents</h3>
            <div id="frequent-items-grid" class="row g-2 mb-4"></div>

            <h3 class="mb-2">Recherche d'article</h3>
            <div class="d-flex gap-2 mb-3">
                <div class="position-relative flex-grow-1">
                    <input type="text" id="article-search" class="form-control"
                           placeholder="Saisir code ou désignation de l'article...">
                    <div id="autocomplete-dropdown" class="position-absolute bg-white border rounded shadow w-100 d-none"
                         style="top:100%; z-index:999; max-height:250px; overflow-y:auto"></div>
                </div>
                <button class="btn btn-outline" id="btn-quick-article">
                    <i class="fas fa-plus"></i> Nouvel article
                </button>
            </div>

            <h3 class="mb-2 mt-4">Articles sélectionnés</h3>
            <div class="table-responsive">
                <table class="table spec-table w-100">
                    <thead><tr>
                        <th>Article</th><th>Catégorie</th>
                        <th>Unité</th><th width="140">Quantité</th>
                        <th width="60"></th>
                    </tr></thead>
                    <tbody id="lines-tbody">
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun article sélectionné</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Step 3 : Validation -->
        <div id="step-3" class="wizard-panel d-none">
            <h3 class="mb-3">Récapitulatif de l'entrée</h3>
            <div id="recap-content"></div>
            <div class="mt-4">
                <label class="form-label fw-bold">Commentaire / Notes</label>
                <textarea id="entry-comment" class="form-control" rows="3" placeholder="Ajouter des notes concernant cette entrée..."></textarea>
            </div>
        </div>

    </div>
    
    <div class="lumiere-card-footer d-flex align-items-center justify-content-between">
        <button class="btn btn-secondary me-auto d-none" id="btn-prev">
            <i class="fas fa-arrow-left"></i> Précédent
        </button>
        <button class="btn btn-primary ms-auto" id="btn-next" disabled>
            Suivant <i class="fas fa-arrow-right"></i>
        </button>
        <button class="btn btn-success d-none" id="btn-validate">
            <i class="fas fa-check-circle"></i> Valider l'entrée
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
class StockEntryWizard {
    constructor() {
        this.step      = 1;
        this.storeId   = null;
        this.draftId   = null;
        this.lines     = [];         // [{article_id, code, name, category, unit, quantity}]
        this.searchTO  = null;

        this.bindEvents();
        window.entryWizardInstance = this;
    }

    bindEvents() {
        /* Sélection magasin */
        $(document).on('click', '.store-card', e => {
            $('.store-card').css({ 'border': '1px solid var(--border-standard)', 'background': '' });
            const $card = $(e.currentTarget);
            $card.css({ 'border': '2px solid var(--primary)', 'background': 'var(--surface-container-low)' });
            this.storeId = $card.data('id');
            $('#btn-next').prop('disabled', false);
        });

        /* Navigation */
        $('#btn-next').on('click', () => this.goNext());
        $('#btn-prev').on('click', () => this.goPrev());
        $('#btn-validate').on('click', () => this.confirmValidate());

        /* Autocomplete */
        $('#article-search').on('input', e => {
            clearTimeout(this.searchTO);
            const q = e.target.value.trim();
            if (q.length < 2) { 
                $('#autocomplete-dropdown').addClass('d-none'); 
                return; 
            }
            this.searchTO = setTimeout(() => this.searchArticles(q), 300);
        });

        /* Fermer l'autocomplétion en cliquant ailleurs */
        $(document).on('click', e => {
            if (!$(e.target).closest('#article-search, #autocomplete-dropdown').length) {
                $('#autocomplete-dropdown').addClass('d-none');
            }
        });

        /* Article rapide */
        $('#btn-quick-article').on('click', () => this.openQuickArticleModal());
    }

    goNext() {
        if (this.step === 1) {
            this.loadStep2();
        } else if (this.step === 2) {
            this.loadStep3();
        }
    }

    goPrev() {
        this.showStep(this.step - 1);
    }

    showStep(n) {
        this.step = n;
        $(`[id^=step-]`).addClass('d-none');
        $(`#step-${n}`).removeClass('d-none');
        
        $('#btn-prev').toggleClass('d-none', n === 1);
        $('#btn-next').toggleClass('d-none', n === 3);
        $('#btn-validate').toggleClass('d-none', n !== 3);
        
        // Mettre à jour le wizard visuel
        $('.wizard-step').each(function(i) {
            $(this).toggleClass('active', i + 1 === n)
                   .toggleClass('completed', i + 1 < n);
            // Mettre à jour l'icône de validation si complété
            const stepNum = $(this).find('.step-number');
            if (i + 1 < n) {
                stepNum.html('<i class="fas fa-check"></i>');
            } else {
                stepNum.text(i + 1);
            }
        });
    }

    loadStep2() {
        $.get(`/inventory/entries/store/${this.storeId}/info`)
         .done(r => {
            this.renderFrequentItems(r.frequent_items);
            this.showStep(2);
            $('#btn-next').prop('disabled', false);
         });
    }

    renderFrequentItems(items) {
        const html = items.map(fi => `
            <div class="col-md-4">
                <div class="mini-card frequent-item-card p-2 text-center"
                     style="cursor:pointer; border: 1px solid var(--border-standard); border-radius: var(--radius); background: var(--surface-container-lowest);"
                     data-article='${JSON.stringify(fi.article)}'>
                    <div class="mc-label fw-bold text-primary small">${fi.article.code}</div>
                    <div class="mc-title small fw-semibold my-1">${fi.article.designation}</div>
                    <div class="mc-desc text-muted" style="font-size: 11px;">${fi.article.category?.name || '—'} · ${fi.article.unit}</div>
                </div>
            </div>
        `).join('');
        
        $('#frequent-items-grid').html(html || '<p class="text-muted small ps-2">Aucun article fréquent configuré.</p>');

        // Bind frequent items click
        $('.frequent-item-card').off('click').on('click', e => {
            const art = $(e.currentTarget).data('article');
            this.addLine(art);
        });
    }

    addLine(article, qty = 1) {
        if (this.lines.find(l => l.article_id === article.id)) {
            if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
                Lumiere.showToast('Article déjà dans la liste', 'warning');
            } else {
                alert('Article déjà dans la liste');
            }
            return;
        }
        this.lines.push({ 
            article_id: article.id, 
            code: article.code, 
            name: article.designation,
            category: article.category?.name, 
            unit: article.unit, 
            quantity: qty 
        });
        this.renderLines();
    }

    renderLines() {
        if (!this.lines.length) {
            $('#lines-tbody').html('<tr><td colspan="5" class="text-center text-muted py-4">Aucun article sélectionné</td></tr>');
            return;
        }

        const rows = this.lines.map((l, i) => `
            <tr>
                <td><strong>${l.code}</strong><br><small class="text-muted">${l.name}</small></td>
                <td>${l.category ?? '—'}</td>
                <td>${l.unit}</td>
                <td>
                    <input type="number" class="form-control line-qty w-100" min="0.01" step="0.01"
                           value="${l.quantity}" data-index="${i}">
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger remove-line" style="min-width:auto; height:32px; width:32px;" data-index="${i}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        $('#lines-tbody').html(rows);

        // Bind quantity change
        $('.line-qty').off('change').on('change', e => {
            const idx = $(e.target).data('index');
            const val = parseFloat(e.target.value);
            this.lines[idx].quantity = isNaN(val) ? 0 : val;
        });

        // Bind remove action
        $('.remove-line').off('click').on('click', e => {
            const idx = $(e.currentTarget).data('index');
            this.lines.splice(idx, 1);
            this.renderLines();
        });
    }

    loadStep3() {
        if (!this.lines.length) { 
            if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
                Lumiere.showToast('Ajoutez au moins un article', 'warning'); 
            } else {
                alert('Ajoutez au moins un article');
            }
            return; 
        }
        
        // Sauvegarder le brouillon
        $.post('/inventory/entries/draft', {
            draft_id: this.draftId, 
            store_id: this.storeId,
            lines: this.lines, 
            comment: $('#entry-comment').val()
        }).done(r => {
            this.draftId = r.draft_id;
            $('#entry-ref').text(`Réf: ${r.reference ?? '...'}`);
            this.renderRecap();
            this.showStep(3);
        });
    }

    renderRecap() {
        const rows = this.lines.map(l => `
            <tr>
                <td>${l.code}</td>
                <td>${l.name}</td>
                <td>${l.unit}</td>
                <td><strong class="text-primary">${l.quantity}</strong></td>
            </tr>
        `).join('');
        
        $('#recap-content').html(`
            <table class="table spec-table w-100">
                <thead><tr><th>Code</th><th>Article</th><th>Unité</th><th>Quantité</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        `);
    }

    searchArticles(q) {
        $.get(`/inventory/articles/search?q=${q}`).done(results => {
            const html = results.map(a => `
                <div class="autocomplete-item px-3 py-2 border-bottom autocomplete-row"
                     style="cursor:pointer;"
                     data-article='${JSON.stringify(a)}'>
                    <strong class="text-primary">${a.code}</strong> · ${a.designation}
                    <small class="text-muted d-block" style="font-size: 11px;">${a.category?.name || '—'} · ${a.unit}</small>
                </div>
            `).join('');
            
            $('#autocomplete-dropdown').html(html || '<p class="p-3 text-muted mb-0 small">Aucun article trouvé</p>').removeClass('d-none');
            
            // Bind item click
            $('.autocomplete-row').off('click').on('click', e => {
                const art = $(e.currentTarget).data('article');
                this.addLine(art);
                $('#autocomplete-dropdown').addClass('d-none');
                $('#article-search').val('');
            });
        });
    }

    confirmValidate() {
        if (typeof Lumiere !== 'undefined' && Lumiere.confirm) {
            Lumiere.confirm({
                title: 'Valider l\'entrée de stock',
                message: `Vous allez valider <strong>${this.lines.length} article(s)</strong>. Les stocks seront mis à jour. Continuer ?`,
                confirmText: 'Valider',
                type: 'success',
                onConfirm: () => {
                    this.executeValidate();
                }
            });
        } else {
            if (confirm(`Vous allez valider ${this.lines.length} article(s). Les stocks seront mis à jour. Continuer ?`)) {
                this.executeValidate();
            }
        }
    }

    executeValidate() {
        $.post(`/inventory/entries/${this.draftId}/validate`)
         .done(r => {
             if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
                 Lumiere.showToast(r.message, 'success');
             } else {
                 alert(r.message);
             }
             setTimeout(() => location.replace('/inventory/entries'), 1500);
         });
    }

    openQuickArticleModal() {
        if (typeof Lumiere !== 'undefined' && Lumiere.openModal) {
            Lumiere.openModal('/inventory/articles/create?quick=1', {
                title: 'Créer un article rapidement', icon: 'box', size: 'lg'
            });
        } else {
            alert('Service modale non disponible');
        }
    }
}

// Démarrer le wizard au chargement de la page
$(document).ready(() => { 
    new StockEntryWizard(); 
});
</script>
@endpush
