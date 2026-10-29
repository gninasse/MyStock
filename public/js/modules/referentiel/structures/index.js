/**
 * structures/index.js
 * Arborescence Direction → Service → Unité.
 */
import { echapper, FormulaireModal, confirmerPuisEnvoyer, notifierSucces } from '../common.js';

$(function () {
    const $page = $('#page-structures');
    const droits = {
        creer: $page.data('peut-creer') === 1,
        modifier: $page.data('peut-modifier') === 1,
        supprimer: $page.data('peut-supprimer') === 1
    };
    const libelles = { direction: 'une Direction', service: 'un Service', unite: 'une Unité' };
    const enfant = { direction: 'service', service: 'unite' };
    const icones = { direction: 'fa-folder', service: 'fa-folder-open', unite: 'fa-file' };
    const index = {};

    let niveauCourant = 'direction';
    const formulaire = new FormulaireModal({
        modal: '#structureModal',
        form: '#structureForm',
        titreAjout: 'Ajouter',
        titreModif: 'Modifier',
        get urlStore() { return route('referentiel.structures.store', niveauCourant); },
        urlUpdate: (id) => route('referentiel.structures.update', [niveauCourant, id]),
        apresEnregistrement: () => charger()
    });

    function actions(niveau, noeud) {
        let html = '';
        if (droits.creer && enfant[niveau]) {
            html += `<button class="btn btn-link btn-sm p-0 ms-2 btn-ajouter" data-niveau="${enfant[niveau]}" data-parent="${noeud.id}" title="Ajouter ${libelles[enfant[niveau]]}"><i class="fas fa-plus text-success"></i></button>`;
        }
        if (droits.modifier) {
            html += `<button class="btn btn-link btn-sm p-0 ms-2 btn-modifier" data-niveau="${niveau}" data-id="${noeud.id}" title="Modifier"><i class="fas fa-edit"></i></button>`;
        }
        if (droits.supprimer) {
            html += `<button class="btn btn-link btn-sm p-0 ms-2 btn-supprimer" data-niveau="${niveau}" data-id="${noeud.id}" title="Supprimer"><i class="fas fa-trash text-danger"></i></button>`;
        }
        return html;
    }

    function noeud(niveau, n, enfants = '') {
        index[`${niveau}-${n.id}`] = n;
        const inactif = n.actif ? '' : ' <span class="badge text-bg-secondary">Inactif</span>';
        return `<li class="mb-1">
                    <i class="fas ${icones[niveau]} text-warning me-1"></i>
                    <strong>${echapper(n.libelle)}</strong> <small class="text-muted">(${echapper(n.code)})</small>${inactif}
                    ${actions(niveau, n)}
                    ${enfants ? `<ul class="list-unstyled ms-4 mt-1">${enfants}</ul>` : ''}
                </li>`;
    }

    function charger() {
        $.get(route('referentiel.structures.arbre')).done(({ data }) => {
            const html = data.map((d) => noeud('direction', d,
                d.services.map((s) => noeud('service', s,
                    s.unites.map((u) => noeud('unite', u)).join('')
                )).join('')
            )).join('');
            $('#arbre-structures').html(html || '<li class="text-muted">Aucune structure. Commencez par ajouter une Direction.</li>');
        });
    }

    $(document).on('click', '.btn-ajouter', function () {
        niveauCourant = $(this).data('niveau');
        const parentId = $(this).data('parent');
        const parentNiveau = { service: 'direction', unite: 'service' }[niveauCourant];
        formulaire.ouvrirAjout(parentNiveau ? { [`${parentNiveau}_id`]: parentId, actif: true } : { actif: true });
        $('#structureModal .modal-title').text(`Ajouter ${libelles[niveauCourant]}`);
        $('#structure-parent').text(parentNiveau ? `Rattaché(e) à : ${index[`${parentNiveau}-${parentId}`].libelle}` : '');
    });

    $(document).on('click', '.btn-modifier', function () {
        niveauCourant = $(this).data('niveau');
        formulaire.ouvrirModification(index[`${niveauCourant}-${$(this).data('id')}`]);
        $('#structure-parent').text('');
    });

    $(document).on('click', '.btn-supprimer', function () {
        const niveau = $(this).data('niveau');
        const n = index[`${niveau}-${$(this).data('id')}`];
        confirmerPuisEnvoyer(`Supprimer « ${n.libelle} » ?`, route('referentiel.structures.destroy', [niveau, n.id]), 'DELETE')
            .done((response) => {
                notifierSucces(response.message);
                charger();
            });
    });

    charger();
});
