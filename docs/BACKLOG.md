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
- [x] 3. **Réception des transferts** : écran « À réceptionner » (badge menu),
      formulaire de réception (quantité reçue par ligne, écart calculé, motif obligatoire
      si écart), refus complet avec motif, bouton « Réceptionner » sur la fiche.
      ⚠ Vérification navigateur à refaire (extension Chrome déconnectée).
- [x] 4. **Retours** : bouton « Retourner au magasin source » sur un transfert reçu,
      formulaire de transfert inverse pré-rempli (lié par `document_origine_id`),
      quantités limitées au reçu moins les retours déjà expédiés (contrôle à l'expédition).
- [x] 5. **États et tableau de bord** : état « Transferts » (en transit / reçus / annulés,
      écarts en rouge, export Excel/PDF, permission `etats.transferts`), provenance et
      destination dans le journal et la fiche de stock, bloc « Transferts en transit » et
      raccourci « Nouveau transfert » au tableau de bord.
- [ ] 6. **Lots et péremption (données)** : lots par article (numéro, date de péremption),
      stock par lot, saisie du lot sur les entrées quand la gestion des lots est active.
- [ ] 7. **Lots et péremption (sorties, transferts, alertes)** : proposition FEFO à la sortie
      et au transfert, lot suivi de bout en bout, alerte et état « Péremptions proches ».
- [ ] 8. **Finitions** : libellés lisibles du journal d'audit, README et guide utilisateur
      à jour, revue de code finale.
