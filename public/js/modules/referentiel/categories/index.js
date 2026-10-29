/**
 * categories/index.js
 * Liste plate des catégories.
 */
import { TableSelection, FormulaireModal, confirmerPuisEnvoyer, notifierSucces } from '../common.js';

$(function () {
    const table = new TableSelection('#categories-table', ['#btn-edit', '#btn-delete']);

    const formulaire = new FormulaireModal({
        modal: '#categorieModal',
        form: '#categorieForm',
        titreAjout: 'Ajouter une catégorie',
        titreModif: 'Modifier la catégorie',
        urlStore: route('referentiel.categories.store'),
        urlUpdate: (id) => route('referentiel.categories.update', id),
        apresEnregistrement: () => table.refresh()
    });

    $('#btn-add').on('click', () => formulaire.ouvrirAjout());

    $('#btn-edit').on('click', () => {
        const ligne = table.selection();
        if (ligne) formulaire.ouvrirModification(ligne);
    });

    $('#btn-delete').on('click', () => {
        const ligne = table.selection();
        if (!ligne) return;
        confirmerPuisEnvoyer(`Supprimer la catégorie « ${ligne.libelle} » ?`, route('referentiel.categories.destroy', ligne.id), 'DELETE')
            .done((response) => {
                notifierSucces(response.message);
                table.refresh();
            });
    });
});
