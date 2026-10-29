/**
 * inventaire.js
 * Saisie d'un inventaire physique (SFD 8) : écarts en temps réel puis validation.
 */
import { echapper, envoyerJson } from '../referentiel/common.js';

$(function () {
    const unites = $('#page-inventaire').data('unites');
    // article_id => { …article, stock_theorique, reelle (chaîne saisie) }
    let articles = new Map();

    const reelle = (a) => (a.reelle === '' ? 0 : parseInt(a.reelle, 10) || 0); // champ vide = 0
    const ecart = (a) => reelle(a) - a.stock_theorique;

    function badgeEcart(e) {
        if (e > 0) return `<span class="text-success fw-bold">+${e}</span>`;
        if (e < 0) return `<span class="text-danger fw-bold">${e}</span>`;
        return '<span class="text-muted">0</span>';
    }

    function visibles() {
        const categorie = $('#filtre-categorie').val();
        const texte = $('#filtre-recherche').val().trim().toLowerCase();
        const avecStock = $('#filtre-stock').is(':checked');

        return [...articles.values()].filter((a) =>
            (!categorie || String(a.categorie_id) === categorie) &&
            (!texte || a.code.toLowerCase().includes(texte) || a.designation.toLowerCase().includes(texte)) &&
            (!avecStock || a.stock_theorique > 0)
        );
    }

    function afficher() {
        const liste = visibles();
        $('#lignes-inventaire').html(liste.length
            ? liste.map((a) => `
                <tr data-id="${a.id}">
                    <td>${echapper(a.code)}</td>
                    <td>${echapper(a.designation)}</td>
                    <td>${echapper(unites[a.unite_mesure] ?? a.unite_mesure)}</td>
                    <td class="text-end">${a.stock_theorique}</td>
                    <td><input type="number" class="form-control form-control-sm qte-reelle" min="0" step="1" value="${echapper(a.reelle)}"></td>
                    <td class="text-end ecart">${badgeEcart(ecart(a))}</td>
                </tr>`).join('')
            : '<tr><td colspan="6" class="text-center text-muted">Aucun article</td></tr>');
        recapituler();
    }

    function recapituler() {
        const tous = [...articles.values()];
        const positifs = tous.filter((a) => ecart(a) > 0).length;
        const negatifs = tous.filter((a) => ecart(a) < 0).length;
        $('#recapitulatif').html(`📊 <strong>Récapitulatif :</strong> Articles sans écart : ${tous.length - positifs - negatifs}
            | Avec écart positif : <span class="text-success">${positifs}</span>
            | Avec écart négatif : <span class="text-danger">${negatifs}</span>`);
        $('#commentaire-requis').toggleClass('d-none', positifs + negatifs === 0);
        return { positifs, negatifs };
    }

    $('#lignes-inventaire').on('input', '.qte-reelle', function () {
        const $tr = $(this).closest('tr');
        const article = articles.get($tr.data('id'));
        article.reelle = this.value;
        $tr.find('.ecart').html(badgeEcart(ecart(article)));
        recapituler();
    });

    $('#filtre-categorie, #filtre-stock').on('change', afficher);
    $('#filtre-recherche').on('input', afficher);

    $('#magasin_id').on('change', function () {
        const magasinId = this.value;
        $('#zone-comptage').toggleClass('d-none', !magasinId);
        articles = new Map();
        if (!magasinId) return;

        $.get(route('stock.inventaires.articles', magasinId)).done(({ data }) => {
            // Pré-remplissage : quantité réelle = quantité théorique (SFD 8.2)
            data.forEach((a) => articles.set(a.id, { ...a, reelle: String(Math.max(a.stock_theorique, 0)) }));
            afficher();
        });
    });

    $('#btn-valider').on('click', () => {
        const { positifs, negatifs } = recapituler();
        $('#erreurs').addClass('d-none');

        if (positifs + negatifs > 0 && !$('#commentaire').val().trim()) {
            afficherErreurs(['Un commentaire est obligatoire pour justifier les écarts constatés.']);
            $('#commentaire').addClass('is-invalid').trigger('focus');
            return;
        }

        const lignesEcart = [...articles.values()].filter((a) => ecart(a) !== 0)
            .map((a) => `<li>${echapper(a.designation)} : ${a.stock_theorique} → ${reelle(a)} (${badgeEcart(ecart(a))})</li>`).join('');

        Swal.fire({
            icon: 'question',
            title: 'Valider l\'inventaire ?',
            html: positifs + negatifs
                ? `<p>${positifs + negatifs} ajustement(s) seront générés :</p><ul class="text-start">${lignesEcart}</ul>`
                : '<p>Aucun écart : le stock ne sera pas modifié.</p>',
            showCancelButton: true,
            confirmButtonText: 'Valider l\'inventaire',
            cancelButtonText: 'Revenir à la saisie'
        }).then((choix) => {
            if (!choix.isConfirmed) return;

            const $bouton = $('#btn-valider').prop('disabled', true);
            envoyerJson(route('stock.inventaires.store'), 'POST', {
                magasin_id: $('#magasin_id').val(),
                date_inventaire: $('#date_inventaire').val(),
                commentaire: $('#commentaire').val(),
                comptages: [...articles.values()].map((a) => ({ article_id: a.id, quantite_reelle: reelle(a) }))
            })
                .done((response) => {
                    Swal.fire({ icon: 'success', title: 'Succès', text: response.message })
                        .then(() => (window.location = route('stock.inventaires.show', response.data.id)));
                })
                .fail((xhr) => {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) afficherErreurs(Object.values(xhr.responseJSON.errors).flat());
                })
                .always(() => $bouton.prop('disabled', false));
        });
    });

    $('#commentaire').on('input', function () {
        $(this).removeClass('is-invalid');
    });

    function afficherErreurs(messages) {
        $('#erreurs').html(`<ul class="mb-0">${[...new Set(messages)].map((m) => `<li>${echapper(m)}</li>`).join('')}</ul>`).removeClass('d-none');
        $('html, body').animate({ scrollTop: 0 }, 200);
    }
});
