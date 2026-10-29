/**
 * common.js
 * Outils partagés par les écrans du module Référentiel.
 */

export function echapper(valeur) {
    return $('<div>').text(valeur ?? '').html();
}

window.statutFormatter = function (value) {
    return value
        ? '<span class="badge text-bg-success">Actif</span>'
        : '<span class="badge text-bg-secondary">Inactif</span>';
};

export function notifierSucces(message) {
    Swal.fire({ icon: 'success', title: 'Succès', text: message, timer: 2000 });
}

export function notifierErreur(xhr) {
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: xhr.responseJSON?.message || 'Une erreur est survenue'
    });
}

/**
 * Envoie une requête AJAX ; les erreurs autres que 422 sont affichées automatiquement.
 */
export function envoyer(url, method, data = {}) {
    return $.ajax({ url, method, data }).fail((xhr) => {
        if (xhr.status !== 422 || !xhr.responseJSON?.errors) notifierErreur(xhr);
    });
}

export function confirmerPuisEnvoyer(question, url, method, data = {}) {
    return Swal.fire({
        icon: 'question',
        title: 'Confirmation',
        text: question,
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler'
    }).then((choix) => (choix.isConfirmed ? envoyer(url, method, data) : $.Deferred().reject()));
}

/**
 * Tableau Bootstrap Table à sélection unique.
 * boutons : sélecteurs activés seulement quand une ligne est sélectionnée.
 */
export class TableSelection {
    constructor(selector, boutons = []) {
        this.$table = $(selector);
        this.boutons = boutons;
        this.$table.on('check.bs.table uncheck.bs.table load-success.bs.table', () => this.majBoutons());
    }

    selection() {
        return this.$table.bootstrapTable('getSelections')[0] || null;
    }

    refresh() {
        this.$table.bootstrapTable('refresh');
    }

    majBoutons() {
        const ligne = this.selection();
        this.boutons.forEach((b) => $(b).prop('disabled', !ligne));
        this.$table.trigger('selection-changee', [ligne]);
    }
}

/**
 * Formulaire en modal pour créer / modifier une entité.
 * options : { modal, form, titreAjout, titreModif, urlStore, urlUpdate(id), apresEnregistrement }
 */
export class FormulaireModal {
    constructor(options) {
        this.o = options;
        this.$modal = $(options.modal);
        this.$form = $(options.form);
        this.id = null;

        $('input, select, textarea', this.$form).on('input change', function () {
            $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
        });

        this.$form.on('submit', (e) => {
            e.preventDefault();
            this.enregistrer();
        });
    }

    ouvrirAjout(valeurs = {}) {
        this.reinitialiser();
        this.id = null;
        this.$modal.find('.modal-title').text(this.o.titreAjout);
        this.remplir(valeurs);
        this.$modal.modal('show');
    }

    ouvrirModification(donnees) {
        this.reinitialiser();
        this.id = donnees.id;
        this.$modal.find('.modal-title').text(this.o.titreModif);
        this.remplir(donnees);
        this.$modal.modal('show');
    }

    remplir(donnees) {
        $.each(donnees, (champ, valeur) => {
            const $champ = $(`[name="${champ}"]`, this.$form);
            if ($champ.is(':checkbox')) $champ.prop('checked', !!valeur);
            else if ($champ.length) $champ.val(valeur ?? '');
        });
    }

    enregistrer() {
        const url = this.id ? this.o.urlUpdate(this.id) : this.o.urlStore;
        const $bouton = $('[type="submit"]', this.$form);

        $bouton.prop('disabled', true);
        envoyer(url, this.id ? 'PUT' : 'POST', this.$form.serialize())
            .done((response) => {
                this.$modal.modal('hide');
                notifierSucces(response.message);
                this.o.apresEnregistrement?.(response);
            })
            .fail((xhr) => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) this.afficherErreurs(xhr.responseJSON.errors);
            })
            .always(() => $bouton.prop('disabled', false));
    }

    afficherErreurs(erreurs) {
        this.effacerErreurs();
        $.each(erreurs, (champ, messages) => {
            const $champ = $(`[name="${champ}"]`, this.$form);
            $champ.addClass('is-invalid').after(`<div class="invalid-feedback d-block">${echapper(messages[0])}</div>`);
        });
    }

    effacerErreurs() {
        $('.is-invalid', this.$form).removeClass('is-invalid');
        $('.invalid-feedback', this.$form).remove();
    }

    reinitialiser() {
        this.$form[0].reset();
        this.effacerErreurs();
    }
}
