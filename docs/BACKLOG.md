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
- [x] 6. **Lots et péremption (données)** : tables `lots`, `stocks_lots`, `lignes_lots`
      (ventilation de chaque ligne par lot). Entrées : n° de lot + péremption par ligne
      (paramètre `lots_actifs`). Sorties, transferts, ajustements négatifs : FEFO automatique
      puis stock sans lot. Annulations et transferts conservent les lots de bout en bout
      (réception et écart répartis sur les lots expédiés). Lots affichés sur la fiche document.
- [x] 7. **Lots et péremption (sorties, transferts, alertes)** : aperçu FEFO sous chaque
      ligne de sortie/transfert (lots qui seront consommés), état « Péremptions » (périmés
      en rouge, proches en orange, horizon paramétrable `peremption_alerte_jours`, export),
      alerte au tableau de bord et entrée de menu quand `lots_actifs` est activé.
- [x] 8. **Finitions** : libellés lisibles du journal d'audit (`lang/fr/activites.php`),
      README et guide utilisateur (`docs/GUIDE.md`), revue de code finale. Corrigé suite à
      la revue : verrou + contrôle du statut avant modification/suppression d'un brouillon
      (un transfert expédié ne peut plus être modifié ni supprimé par un onglet resté
      ouvert), verrou du transfert d'origine pendant le contrôle d'un retour, retour refusé
      redevenu retournable, plus de `FOR UPDATE` sur la jointure FEFO, création de lot
      concurrente sans erreur 500, quantités plafonnées à 1 000 000, contrôle du magasin
      à l'annulation d'un mouvement.

## Reste à faire hors boucle

- Vérification dans le navigateur des écrans des tâches 2 à 8 (extension Chrome
  déconnectée pendant toute la boucle ; les écrans sont couverts par les tests HTTP).

## Lot 5 — Imprimés, aperçu PDF, états et statistiques

- [x] 9. **Aperçu PDF en modale** : composant global (iframe, télécharger, imprimer,
      nouvel onglet) déclenché par `data-apercu-pdf` ; PDF servis en ligne (`inline`),
      téléchargement par `?telecharger=1` ; les exports PDF des états passent par la modale.
- [x] 10. **Imprimés des documents** : bon d'entrée, de sortie, de transfert (signatures
      expéditeur / transporteur / réceptionnaire), de réception, de réintégration,
      d'ajustement ; filigrane BROUILLON / ANNULÉ ; impression journalisée.
- [ ] 11. **Imprimés d'inventaire** : fiche de comptage vierge (par magasin, catégorie)
      et procès-verbal d'inventaire (écarts, signatures).
- [ ] 12. **Nouveaux états** : consommation par service, couverture de stock et risque
      de rupture, articles dormants, écarts d'inventaire (exports Excel / PDF).
- [ ] 13. **Statistiques** : page de pilotage (indicateurs, évolution mensuelle des
      mouvements, articles les plus consommés, consommation par service, stock par
      catégorie, performance des transferts) filtrable par période et magasin.
