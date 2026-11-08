/**
 * parametres/entete.js
 * En-tête officiel des documents PDF : hiérarchie réordonnable, armoiries et logo envoyés
 * à la volée, aperçu mis à jour pendant la saisie.
 */
import { echapper, envoyerJson, notifierSucces, notifierErreur, confirmerPuisEnvoyer } from '../common.js';

$(function () {
    const $form = $('#form-entete');
    const $liste = $('#liste-niveaux');
    const $apercu = $('#entete-apercu');
    const zone = (nom) => $apercu.find(`[data-zone="${nom}"]`);
    const images = {
        entete_armoiries: $('[data-image="entete_armoiries"] .image-entete img'),
        entete_logo: $('[data-image="entete_logo"] .image-entete img'),
    };

    // ---- Hiérarchie ----
    function niveaux() {
        return $liste.find('.niveau-ligne input').map((_, el) => el.value.trim()).get().filter(Boolean);
    }

    function renumeroter() {
        const $lignes = $liste.find('.niveau-ligne');
        $lignes.each(function (i) {
            $(this).find('.numero').text(i + 1);
            $(this).find('.monter').prop('disabled', i === 0);
            $(this).find('.descendre').prop('disabled', i === $lignes.length - 1);
        });
    }

    $('#btn-ajouter-niveau').on('click', function () {
        const $ligne = $($('#modele-niveau').html());
        $liste.append($ligne);
        renumeroter();
        $ligne.find('input').trigger('focus');
    });

    $liste.on('click', '.retirer', function () {
        $(this).closest('.niveau-ligne').remove();
        renumeroter();
        majApercu();
    });
    $liste.on('click', '.monter', function () {
        const $l = $(this).closest('.niveau-ligne');
        $l.prev().before($l);
        renumeroter();
        majApercu();
    });
    $liste.on('click', '.descendre', function () {
        const $l = $(this).closest('.niveau-ligne');
        $l.next().after($l);
        renumeroter();
        majApercu();
    });

    // ---- Aperçu ----
    const valeur = (nom) => $form.find(`[name="${nom}"]`).val()?.trim() ?? '';
    const img = ($source, classe) => ($source.hasClass('d-none') ? '' : `<img src="${$source.attr('src')}" alt="" class="${classe}">`);

    function majApercu() {
        const armoiries = img(images.entete_armoiries, 'armoiries');
        const logo = img(images.entete_logo, 'logo');
        const posArmoiries = valeur('entete_armoiries_position');
        const posLogo = valeur('entete_logo_position');

        zone('hierarchie').html(niveaux().map((n) => `<div class="niveau">${echapper(n)}</div>`).join('<div class="tiret"></div>'));
        zone('logo-gauche').html(posLogo === 'GAUCHE' ? logo : '');
        zone('centre').html((posArmoiries === 'CENTRE' ? armoiries : '') + (posLogo === 'CENTRE' ? logo : ''));
        zone('armoiries-droite').html(posArmoiries === 'DROITE' ? armoiries : '');
        zone('etat').text(valeur('entete_etat'));
        zone('devise').html(valeur('entete_devise') ? `<div class="tiret"></div><div class="devise">${echapper(valeur('entete_devise'))}</div>` : '');

        const libelle = $('#libelle-TRANSFERT').val()?.trim() || $apercu.data('libelle-defaut');
        zone('libelle').text(libelle);
        let format = valeur('entete_reference') || 'N° {numero}';
        if (!format.includes('{numero}')) format += ' {numero}';
        zone('reference').text(format.replace('{numero}', 'TRF-20261108-001').replace('{annee}', new Date().getFullYear()));

        const lieu = valeur('entete_lieu');
        const date = new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        zone('lieu').text(lieu ? `Fait à ${lieu}, le ${date}` : `Fait le ${date}`);
        zone('pied').text(valeur('entete_pied'));
    }

    $form.on('input change', 'input, select', majApercu);

    // ---- Images (enregistrées dès le choix du fichier) ----
    $('[data-image]').each(function () {
        const $bloc = $(this);
        const $img = $bloc.find('.image-entete img');
        const $vide = $bloc.find('.image-entete span');
        const $retirer = $bloc.find('.retirer-image');
        const afficher = (src) => {
            $img.attr('src', src || '').toggleClass('d-none', !src);
            $vide.toggleClass('d-none', !!src);
            $retirer.toggleClass('d-none', !src);
            majApercu();
        };

        $bloc.find('input[type=file]').on('change', function () {
            const fichier = this.files[0];
            this.value = '';
            if (!fichier) return;
            const donnees = new FormData();
            donnees.append('fichier', fichier);
            $.ajax({ url: $bloc.data('url'), method: 'POST', data: donnees, processData: false, contentType: false })
                .done((r) => { afficher(r.image); notifierSucces(r.message); })
                .fail((xhr) => {
                    const erreur = xhr.responseJSON?.errors?.fichier?.[0];
                    erreur ? Swal.fire({ icon: 'error', title: 'Image refusée', text: erreur }) : notifierErreur(xhr);
                });
        });

        $retirer.on('click', function () {
            confirmerPuisEnvoyer('Retirer cette image de l\'en-tête ?', $bloc.data('url-supprimer'), 'DELETE', {}, { confirmer: 'Retirer' })
                .done((r) => { afficher(null); notifierSucces(r.message); });
        });
    });

    // ---- Enregistrement ----
    $form.on('submit', function (e) {
        e.preventDefault();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-erreur]').text('');

        const libelles = {};
        $('.libelle-document').each(function () { libelles[$(this).data('code')] = this.value.trim(); });

        const donnees = {
            entete_hierarchie: niveaux(),
            entete_libelles: libelles,
        };
        $form.find('[name]').each(function () { donnees[this.name] = this.value; });

        envoyerJson($form.data('url'), 'PUT', donnees)
            .done((r) => notifierSucces(r.message))
            .fail((xhr) => {
                $.each(xhr.responseJSON?.errors ?? {}, (champ, messages) => {
                    const base = champ.split('.')[0];
                    const $champ = $form.find(`[name="${base}"]`);
                    if ($champ.length) {
                        $champ.addClass('is-invalid').siblings('.invalid-feedback').text(messages[0]);
                    } else {
                        $form.find(`[data-erreur="${base}"]`).text(messages[0]);
                    }
                });
            });
    });

    renumeroter();
    majApercu();
});
