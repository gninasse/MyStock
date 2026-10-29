/**
 * magasins/index.js
 * Liste des magasins : création, modification, désactivation.
 */
import { TableSelection, FormulaireModal, confirmerPuisEnvoyer, notifierSucces } from '../common.js';

$(function () {
    const table = new TableSelection('#magasins-table', ['#btn-edit', '#btn-responsables', '#btn-toggle']);

    const formulaire = new FormulaireModal({
        modal: '#magasinModal',
        form: '#magasinForm',
        titreAjout: 'Ajouter un magasin',
        titreModif: 'Modifier le magasin',
        urlStore: route('referentiel.magasins.store'),
        urlUpdate: (id) => route('referentiel.magasins.update', id),
        apresEnregistrement: () => table.refresh()
    });

    $('#btn-add').on('click', () => formulaire.ouvrirAjout());

    $('#btn-edit').on('click', () => {
        const ligne = table.selection();
        if (ligne) formulaire.ouvrirModification(ligne);
    });

    $('#btn-responsables').on('click', () => {
        const ligne = table.selection();
        if (ligne) window.location = route('referentiel.magasins.responsables.index', ligne.id);
    });

    $('#btn-toggle').on('click', () => {
        const ligne = table.selection();
        if (!ligne) return;
        const action = ligne.actif ? 'Désactiver' : 'Activer';
        confirmerPuisEnvoyer(`${action} le magasin « ${ligne.libelle} » ?`, route('referentiel.magasins.toggle-status', ligne.id), 'POST')
            .done((response) => {
                notifierSucces(response.message);
                table.refresh();
            });
    });
});
