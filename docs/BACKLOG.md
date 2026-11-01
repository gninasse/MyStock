# Backlog MyStock — évolutions après la v2.0

Évolutions inspirées de l'analyse du module pharmacie d'OpenClinic
(transfert = sortie source + réception destination, écarts tracés, retours, lots).
Une tâche par itération de la boucle de développement : implémenter, tester
(PHPUnit + navigateur), committer, cocher.

- [x] 1. **Paramètres de l'application** : table `parametres` + service en cache
      (`Parametres::get()`), écran « Administration > Paramètres » (permission
      `referentiel.parametres.gerer`), modifications journalisées. Premiers paramètres :
      nom de l'organisation (en-têtes PDF), ancienneté max d'un mouvement (remplace
      `stock.jours_retroactifs_max`). Chaque tâche suivante ajoute ses propres paramètres.
      Messages de validation en français (`lang/fr`, `APP_LOCALE=fr`).
- [x] 2. **Transferts entre magasins** : document TRANSFERT (TRF-…) magasin source →
      destination, statut « En transit » entre expédition et réception. Mode paramétrable
      (`transfert_mode`) : `DEUX_ETAPES`, `UNE_ETAPE`, `UNE_ETAPE_SI_ACCES` (une étape si
      l'expéditeur a la permission de réception et accès au magasin destinataire).
      Réception = document RECEPTION (REC-…) ; quantité non reçue réintégrée (REINTEGRATION,
      RIN-…) ou comptée en perte (`transfert_ecart`). Annulation avant réception =
      réintégration intégrale. Permissions `stock.transferts.saisir` / `.receptionner`.
      ⚠ Vérification navigateur à refaire (extension Chrome déconnectée pendant l'itération).
- [ ] 3. **Réception des transferts** : écran « À réceptionner » (badge menu),
      saisie de la quantité reçue par ligne, motif obligatoire si écart.
      Écart traité selon le paramètre : `RETOUR_SOURCE` (recrédité à la source, cas 2)
      ou `PERTE` (perte en transit tracée). Permission `stock.transferts.receptionner`.
- [ ] 4. **Retours** : depuis un transfert reçu, créer un transfert inverse pré-rempli
      lié à l'original (cas 3), limité aux quantités reçues.
- [ ] 5. **États et tableau de bord** : état « Transferts en transit », transferts dans
      le journal et la fiche de stock (provenance / destination), widget du tableau de bord.
- [ ] 6. **Lots et péremption (données)** : lots par article (numéro, date de péremption),
      stock par lot, saisie du lot sur les entrées quand la gestion des lots est active.
- [ ] 7. **Lots et péremption (sorties, transferts, alertes)** : proposition FEFO à la sortie
      et au transfert, lot suivi de bout en bout, alerte et état « Péremptions proches ».
- [ ] 8. **Finitions** : libellés lisibles du journal d'audit, README et guide utilisateur
      à jour, revue de code finale.
