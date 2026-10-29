/**
 * documents.js
 * Liste des documents de stock : filtres, consultation, brouillons, annulation.
 */
import { echapper, confirmerPuisEnvoyer, notifierSucces } from '../referentiel/common.js';

const TYPES = {
    ENTREE: '<span class="badge text-bg-success">Entrée</span>',
    SORTIE: '<span class="badge text-bg-danger">Sortie</span>',
    AJUSTEMENT: '<span class="badge text-bg-info">Ajustement</span>'
};
const STATUTS = {
    BROUILLON: '<span class="badge text-bg-secondary">Brouillon</span>',
    VALIDE: '<span class="badge text-bg-primary">Validé</span>',
    ANNULE: '<span class="badge text-bg-dark">Annulé</span>'
};

window.documentsQueryParams = (params) => ({ ...params, ...Object.fromEntries(new FormData($('#filtres')[0])) });
window.referenceFormatter = (value) => (value ? echapper(value) : '<em class="text-muted">Brouillon</em>');
window.typeFormatter = (value) => TYPES[value] ?? value;
window.statutDocumentFormatter = (value) => STATUTS[value] ?? value;

window.actionsFormatter = (id, row) => {
    let html = `<a class="btn btn-sm btn-outline-secondary" href="${route('stock.documents.show', id)}" title="Consulter"><i class="fas fa-eye"></i></a>`;
    if (row.modifiable) {
        html += ` <a class="btn btn-sm btn-outline-primary" href="${route('stock.documents.edit', id)}" title="Modifier"><i class="fas fa-edit"></i></a>`;
        html += ` <button class="btn btn-sm btn-outline-danger btn-supprimer" title="Supprimer"><i class="fas fa-trash"></i></button>`;
    }
    if (row.annulable) {
        html += ` <button class="btn btn-sm btn-outline-warning btn-annuler" title="Annuler le mouvement"><i class="fas fa-undo"></i></button>`;
    }
    return html;
};

window.actionsEvents = {
    'click .btn-supprimer': (e, value) => {
        confirmerPuisEnvoyer('Supprimer ce brouillon ?', route('stock.documents.destroy', value), 'DELETE')
            .done((response) => {
                notifierSucces(response.message);
                $('#documents-table').bootstrapTable('refresh');
            });
    },
    'click .btn-annuler': (e, value, row) => {
        confirmerPuisEnvoyer(`Annuler le mouvement ${row.reference} ? Un mouvement inverse sera généré. Cette action est irréversible.`,
            route('stock.documents.annuler', value), 'POST')
            .done((response) => {
                notifierSucces(response.message);
                $('#documents-table').bootstrapTable('refresh');
            });
    }
};

$(function () {
    $('#filtres').on('change', 'input, select', () => $('#documents-table').bootstrapTable('refresh', { pageNumber: 1 }));
});
