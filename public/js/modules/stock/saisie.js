/**
 * saisie.js
 * Saisie d'une entrée ou d'une sortie de stock (SFD 7.3 et 7.4).
 */
import { echapper, envoyer, FormulaireModal } from '../referentiel/common.js';

$(function () {
    const $page = $('#page-saisie');
    const doc = $page.data('document');
    const unites = $page.data('unites');
    const estSortie = doc.type === 'SORTIE';
    const peutCreerArticle = $page.data('peut-creer-article') === 1;

    // article_id => { id, code, designation, unite_mesure, quantite, stock }
    const lignes = new Map();
    let frequents = [];
    let minuterie = null;

    $page.data('lignes').forEach((l) => lignes.set(l.article_id, { ...l, id: l.article_id, stock: null }));

    const unite = (code) => unites[code] ?? code ?? '';
    const avecStockSeulement = () => estSortie && $('#avec-stock').is(':checked');

    // ---- Stocks disponibles (sorties) ----
    function chargerStocks(ids) {
        const magasinId = $('#magasin_id').val();
        if (!estSortie || !magasinId || !ids.length) return $.Deferred().resolve({}).promise();
        return $.get(route('stock.saisie.stocks', magasinId), { article_ids: ids }).then((r) => r.data);
    }

    function majStocksLignes() {
        chargerStocks([...lignes.keys()]).done((stocks) => {
            $.each(stocks, (id, stock) => {
                if (lignes.has(Number(id))) lignes.get(Number(id)).stock = stock;
            });
            afficherLignes();
        });
    }

    // ---- Lignes du document ----
    function definirQuantite(article, quantite) {
        if (quantite > 0) {
            const existante = lignes.get(article.id);
            lignes.set(article.id, { ...article, ...(existante || {}), quantite });
        } else {
            lignes.delete(article.id);
        }
    }

    function afficherLignes() {
        $('#nb-lignes').text(lignes.size);
        if (!lignes.size) {
            $('#lignes').html(`<tr><td colspan="5" class="text-center text-muted">Aucun article</td></tr>`);
            return;
        }

        $('#lignes').html([...lignes.values()].map((l) => {
            const insuffisant = estSortie && l.stock !== null && l.quantite > l.stock;
            return `
                <tr data-id="${l.id}" class="${insuffisant ? 'table-danger' : ''}">
                    <td>${echapper(l.designation)} <small class="text-muted">(${echapper(l.code)})</small></td>
                    <td><input type="number" class="form-control form-control-sm qte-ligne" min="1" step="1" value="${l.quantite}"></td>
                    <td>${echapper(unite(l.unite_mesure))}</td>
                    ${estSortie ? `<td>${l.stock ?? '…'}</td>` : ''}
                    <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger btn-retirer" title="Retirer"><i class="fas fa-trash"></i></button></td>
                </tr>`;
        }).join(''));
    }

    $('#lignes').on('change', '.qte-ligne', function () {
        const id = $(this).closest('tr').data('id');
        definirQuantite(lignes.get(id), parseInt(this.value, 10) || 0);
        afficherLignes();
        afficherFrequents();
    });

    $('#lignes').on('click', '.btn-retirer', function () {
        lignes.delete($(this).closest('tr').data('id'));
        afficherLignes();
        afficherFrequents();
    });

    // ---- Articles fréquents du magasin ----
    function chargerFrequents() {
        const magasinId = $('#magasin_id').val();
        $('#zone-articles').toggleClass('d-none', !magasinId);
        if (!magasinId) return;

        $.get(route('stock.saisie.articles-frequents', magasinId)).done(({ data }) => {
            frequents = data;
            afficherFrequents();
            majStocksLignes();
        });
    }

    function afficherFrequents() {
        const visibles = frequents.filter((a) => !avecStockSeulement() || a.stock > 0 || lignes.has(a.id));
        if (!visibles.length) {
            $('#articles-frequents').html('<tr><td class="text-muted p-3">Aucun article fréquent pour ce magasin.</td></tr>');
            return;
        }

        $('#articles-frequents').html(visibles.map((a) => {
            const ligne = lignes.get(a.id);
            const epuise = estSortie && a.stock <= 0;
            return `
                <tr data-id="${a.id}" class="${epuise ? 'text-muted' : ''}">
                    <td style="width: 40px"><input type="checkbox" class="form-check-input chk-frequent" ${ligne ? 'checked' : ''} ${epuise ? 'disabled' : ''}></td>
                    <td>${echapper(a.designation)}</td>
                    ${estSortie ? `<td>Stock : ${a.stock}</td>` : ''}
                    <td style="width: 140px"><input type="number" class="form-control form-control-sm qte-frequent" min="1" step="1" value="${ligne ? ligne.quantite : ''}" ${epuise ? 'disabled' : ''}></td>
                    <td>${echapper(unite(a.unite_mesure))}</td>
                </tr>`;
        }).join(''));
    }

    $('#articles-frequents').on('change', '.chk-frequent, .qte-frequent', function () {
        const $tr = $(this).closest('tr');
        const article = frequents.find((a) => a.id === $tr.data('id'));
        const $qte = $tr.find('.qte-frequent');

        if ($(this).is('.chk-frequent')) {
            if (this.checked && !$qte.val()) $qte.val(1);
            definirQuantite(article, this.checked ? parseInt($qte.val(), 10) || 1 : 0);
        } else {
            definirQuantite(article, parseInt($qte.val(), 10) || 0);
        }
        afficherFrequents();
        afficherLignes();
    });

    $('#avec-stock').on('change', afficherFrequents);

    $('#magasin_id').on('change', () => {
        lignes.forEach((l) => (l.stock = null));
        chargerFrequents();
    });

    // ---- Recherche d'un autre article ----
    function rechercher() {
        const q = $('#recherche-article').val().trim();
        const $res = $('#resultats-recherche');
        if (q.length < 2) {
            $res.addClass('d-none').empty();
            return;
        }

        $.get(route('referentiel.articles.recherche'), { q, categorie_id: $('#recherche-categorie').val() })
            .then(({ data }) => chargerStocks(data.map((a) => a.id)).then((stocks) => data.map((a) => ({ ...a, stock: stocks[a.id] ?? null }))))
            .done((articles) => {
                const visibles = articles.filter((a) => !avecStockSeulement() || a.stock > 0);
                let html = visibles.map((a) => `
                    <button type="button" class="list-group-item list-group-item-action resultat" data-article="${echapper(JSON.stringify(a))}">
                        ${echapper(a.designation)} <small class="text-muted">(${echapper(a.code)})</small>
                        ${estSortie ? `<span class="badge text-bg-light float-end">Stock : ${a.stock}</span>` : ''}
                    </button>`).join('');

                if (!visibles.length) {
                    html = `<div class="list-group-item text-muted">⚠️ Aucun résultat pour « ${echapper(q)} ».</div>`;
                    if (peutCreerArticle) {
                        html += `<button type="button" class="list-group-item list-group-item-action text-primary" id="btn-creer-article"><i class="fas fa-plus"></i> Créer cet article</button>`;
                    }
                }
                $res.html(html).removeClass('d-none');
            });
    }

    function ajouterArticle(article) {
        if (!lignes.has(article.id)) definirQuantite(article, 1);
        $('#recherche-article').val('');
        $('#resultats-recherche').addClass('d-none').empty();
        afficherLignes();
        afficherFrequents();
        $(`#lignes tr[data-id="${article.id}"] .qte-ligne`).trigger('focus').trigger('select');
    }

    $('#recherche-article').on('input', () => {
        clearTimeout(minuterie);
        minuterie = setTimeout(rechercher, 300);
    });
    $('#recherche-categorie').on('change', rechercher);
    $('#resultats-recherche').on('click', '.resultat', function () {
        ajouterArticle($(this).data('article'));
    });
    $(document).on('click', (e) => {
        if (!$(e.target).closest('#resultats-recherche, #recherche-article').length) $('#resultats-recherche').addClass('d-none');
    });

    // ---- Création rapide d'article (entrées) ----
    if (peutCreerArticle) {
        const formulaireArticle = new FormulaireModal({
            modal: '#articleRapideModal',
            form: '#articleRapideForm',
            titreAjout: 'Création rapide d\'article',
            urlStore: route('referentiel.articles.store'),
            apresEnregistrement: ({ data }) => ajouterArticle({ ...data, stock: null })
        });

        $('#resultats-recherche').on('click', '#btn-creer-article', () => {
            const categorie = $('#recherche-categorie').val();
            formulaireArticle.ouvrirAjout({
                designation: $('#recherche-article').val().trim(),
                categorie_id: categorie,
                unite_mesure: $('#recherche-categorie option:selected').data('unite') || ''
            });
        });

        $('#ar_categorie').on('change', function () {
            const defaut = $(this).find(':selected').data('unite');
            if (defaut && !$('#ar_unite').val()) $('#ar_unite').val(defaut);
        });
    }

    // ---- Bénéficiaire (sorties) ----
    if (estSortie) {
        let directions = [];
        const options = (items, vide) => `<option value="">${vide}</option>` +
            items.map((i) => `<option value="${i.id}">${echapper(i.libelle)}</option>`).join('');
        const direction = () => directions.find((d) => d.id === Number($('#direction_id').val()));
        const service = () => direction()?.services.find((s) => s.id === Number($('#service_id').val()));

        function majBeneficiaire() {
            const u = service()?.unites.find((x) => x.id === Number($('#unite_id').val()));
            $('#beneficiaire-final').text(u?.libelle || service()?.libelle || '—');
        }

        $('#direction_id').on('change', () => {
            const services = direction()?.services ?? [];
            $('#service_id').html(options(services, '— Choisir —')).prop('disabled', !services.length);
            $('#unite_id').html(options([], '— Aucune —')).prop('disabled', true);
            majBeneficiaire();
        });

        $('#service_id').on('change', () => {
            const unitesService = service()?.unites ?? [];
            $('#unite_id').html(options(unitesService, '— Aucune —')).prop('disabled', !unitesService.length);
            majBeneficiaire();
        });

        $('#unite_id').on('change', majBeneficiaire);

        $.get(route('stock.saisie.beneficiaires')).done(({ data }) => {
            directions = data;
            $('#direction_id').html(options(directions, '— Choisir —'));
            if (doc.direction_id) $('#direction_id').val(doc.direction_id).trigger('change');
            if (doc.service_id) $('#service_id').val(doc.service_id).trigger('change');
            if (doc.unite_id) $('#unite_id').val(doc.unite_id).trigger('change');
        });
    }

    // ---- Enregistrement ----
    function afficherErreurs(erreurs) {
        const messages = [...new Set(Object.values(erreurs).flat())];
        $('#erreurs').html(`<ul class="mb-0">${messages.map((m) => `<li>${echapper(m)}</li>`).join('')}</ul>`).removeClass('d-none');
        $('html, body').animate({ scrollTop: 0 }, 200);
    }

    $('[data-action]').on('click', function () {
        const action = $(this).data('action');
        const donnees = {
            action,
            date_mouvement: $('#date_mouvement').val(),
            magasin_id: $('#magasin_id').val(),
            commentaire: $('#commentaire').val(),
            lignes: [...lignes.values()].map((l) => ({ article_id: l.id, quantite: l.quantite }))
        };
        if (estSortie) {
            Object.assign(donnees, {
                direction_id: $('#direction_id').val(),
                service_id: $('#service_id').val(),
                unite_id: $('#unite_id').val()
            });
        }

        const url = doc.id ? route('stock.documents.update', doc.id) : route('stock.saisie.store', doc.segment);
        const $boutons = $('[data-action]').prop('disabled', true);
        $('#erreurs').addClass('d-none');

        envoyer(url, doc.id ? 'PUT' : 'POST', donnees)
            .done((response) => {
                const { id, statut } = response.data;
                Swal.fire({ icon: 'success', title: 'Succès', text: response.message }).then(() => {
                    window.location = statut === 'BROUILLON' ? route('stock.documents.edit', id) : route('stock.documents.show', id);
                });
            })
            .fail((xhr) => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) afficherErreurs(xhr.responseJSON.errors);
            })
            .always(() => $boutons.prop('disabled', false));
    });

    afficherLignes();
    if ($('#magasin_id').val()) chargerFrequents();
});
