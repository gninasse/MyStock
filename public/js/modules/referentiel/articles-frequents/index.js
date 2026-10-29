/**
 * articles-frequents/index.js
 * Paramétrage manuel des articles fréquents d'un magasin.
 */
import { echapper, envoyer, notifierSucces } from '../common.js';

$(function () {
    const maximum = $('#page-frequents').data('maximum');
    const $frequents = $('#articles-frequents');
    const $disponibles = $('#articles-disponibles');
    let magasinId = null;
    let minuterie = null;

    new Sortable($frequents[0], { animation: 150 });

    const libelle = (a) => `${echapper(a.designation)} <small class="text-muted">(${echapper(a.code)})</small>`;

    function majCompteur() {
        $('#compteur').text($frequents.children().length);
    }

    function idsFrequents() {
        return $frequents.children().map((i, li) => $(li).data('id')).get();
    }

    function ajouterFrequent(article, rafraichir = true) {
        if (idsFrequents().includes(article.id)) return;
        if ($frequents.children().length >= maximum) {
            Swal.fire({ icon: 'warning', title: 'Limite atteinte', text: `Maximum ${maximum} articles fréquents par magasin.` });
            return;
        }
        $frequents.append(`
            <li class="list-group-item d-flex align-items-center" data-id="${article.id}" style="cursor: move">
                <span class="ms-2 flex-grow-1">${libelle(article)}</span>
                <button class="btn btn-sm btn-link text-danger btn-retirer" title="Retirer"><i class="fas fa-times"></i></button>
            </li>`);
        majCompteur();
        if (rafraichir) rechercher();
    }

    function rechercher() {
        $.get(route('referentiel.articles.recherche'), {
            q: $('#recherche').val(),
            categorie_id: $('#filtre-categorie').val()
        }).done(({ data }) => {
            const dejaPris = idsFrequents();
            const libres = data.filter((a) => !dejaPris.includes(a.id));
            $disponibles.html(libres.length
                ? libres.map((a) => `
                    <li class="list-group-item d-flex align-items-center">
                        <span class="flex-grow-1">${libelle(a)}</span>
                        <button class="btn btn-sm btn-outline-primary btn-ajouter" data-article='${echapper(JSON.stringify(a))}' title="Ajouter"><i class="fas fa-plus"></i></button>
                    </li>`).join('')
                : '<li class="list-group-item text-muted">Aucun article</li>');
        });
    }

    function chargerMagasin() {
        magasinId = $('#magasin').val();
        $('#zone-parametrage, #zone-actions').toggleClass('d-none', !magasinId);
        if (!magasinId) return;

        $.get(route('referentiel.articles-frequents.show', magasinId)).done(({ data }) => {
            $frequents.empty();
            data.forEach((a) => ajouterFrequent(a, false));
            majCompteur();
            rechercher();
        });
    }

    $('#magasin').on('change', chargerMagasin);
    $('#btn-annuler').on('click', chargerMagasin);

    $('#recherche').on('input', () => {
        clearTimeout(minuterie);
        minuterie = setTimeout(rechercher, 300);
    });
    $('#filtre-categorie').on('change', rechercher);

    $disponibles.on('click', '.btn-ajouter', function () {
        ajouterFrequent($(this).data('article'));
    });

    $frequents.on('click', '.btn-retirer', function () {
        $(this).closest('li').remove();
        majCompteur();
        rechercher();
    });

    $('#btn-enregistrer').on('click', () => {
        envoyer(route('referentiel.articles-frequents.update', magasinId), 'PUT', { article_ids: idsFrequents() })
            .done((response) => notifierSucces(response.message))
            .fail((xhr) => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: Object.values(xhr.responseJSON.errors)[0][0] });
                }
            });
    });
});
