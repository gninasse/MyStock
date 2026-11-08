# Guide d'utilisation de MyStock

## Rôles

| Rôle | Peut faire |
| --- | --- |
| Administrateur | Tout, y compris utilisateurs, paramètres, annulations et journal d'audit |
| Gestionnaire | Entrées, sorties, transferts, réceptions, inventaires et états de ses magasins |
| Opérateur | Entrées et réception des transferts de ses magasins, états de stock |
| Consultant | Consultation des états de tous les magasins |

Un utilisateur ne voit que les magasins auxquels il est rattaché (ou dont il est responsable),
sauf s'il a la permission « Accéder à tous les magasins ».

## Entrées et sorties

*Mouvements > Nouvelle entrée / Nouvelle sortie.* Choisir le magasin, cocher les articles
fréquents ou rechercher un article, saisir les quantités. **Enregistrer brouillon** garde la
saisie sans toucher au stock ; **Valider** attribue la référence (ENT-/SOR-AAAAMMJJ-NNN) et met
le stock à jour. Une sortie exige une Direction et un Service, et un stock suffisant.

Un mouvement validé ne se modifie pas : l'administrateur l'**annule**, ce qui crée le mouvement
inverse.

## Transferts entre magasins

Un transfert est toujours une **sortie du magasin source** suivie d'une **réception par le
magasin destinataire**. Entre les deux, la marchandise est **en transit** : elle a quitté la
source mais n'est pas encore dans le stock du destinataire.

1. *Mouvements > Nouveau transfert* : magasin source, magasin destinataire, articles.
   **Expédier le transfert** débite la source (référence TRF-…).
2. Le destinataire voit le transfert dans *Mouvements > À réceptionner* (badge) et sur le
   tableau de bord. Il saisit les quantités réellement reçues :
   - **tout reçu** : le stock du destinataire est crédité (document REC-…) ;
   - **reçu en partie** : un motif est obligatoire. La quantité manquante est réintégrée à la
     source (document RIN-…) ou comptée comme perte, selon le paramétrage ;
   - **Refuser le transfert** : rien n'entre chez le destinataire, motif obligatoire.
3. Tant qu'il n'est pas reçu, l'expéditeur peut **annuler le transfert** : tout est réintégré
   à la source.
4. Après réception, le destinataire peut **retourner** tout ou partie de la marchandise :
   bouton « Retourner au magasin source » sur la fiche du transfert. Le retour est un
   nouveau transfert en sens inverse, limité à ce qui a été reçu.

Selon le paramètre *Déroulement d'un transfert*, l'expédition peut créditer directement le
destinataire (une étape), toujours ou seulement si l'expéditeur a lui-même accès au magasin
destinataire et le droit de réceptionner.

## Lots et péremption

À activer dans *Administration > Paramètres*. À l'entrée, chaque ligne peut recevoir un
numéro de lot et une date de péremption. Les sorties et transferts consomment
automatiquement les lots qui périment en premier (**FEFO**) ; l'écran de saisie montre les
lots qui vont sortir. Les lots suivent les transferts, les retours et les annulations.

*États > Péremptions* liste les lots périmés (rouge) ou proches de la péremption (orange) ;
le tableau de bord signale leur nombre.

## Inventaires

*Inventaire > Saisir un inventaire* : saisir les quantités comptées. Chaque écart crée un
ajustement (AJU-…) ; un commentaire est obligatoire s'il y a des écarts.

- **Fiche de comptage** : après le choix du magasin (et éventuellement d'une catégorie),
  le bouton « Fiche de comptage » imprime la liste des articles à compter, sans le stock
  théorique (comptage à l'aveugle) ; l'option « Avec le stock théorique » l'ajoute.
- **Procès-verbal** : sur un inventaire validé, « Imprimer le procès-verbal » donne la
  fiabilité du stock, les excédents, les manquants, le détail des écarts et les signatures.

## Imprimés et aperçu PDF

Tout document PDF s'ouvre dans une fenêtre d'aperçu : **Imprimer**, **Télécharger** ou
ouvrir dans un nouvel onglet. Sur la fiche d'un mouvement (ou depuis la liste des
documents, icône imprimante), « Imprimer le bon » produit selon le type : bon d'entrée, de
sortie, de transfert (signatures expéditeur, transporteur, destinataire), de réception, de
réintégration ou d'ajustement. Un brouillon porte le filigrane BROUILLON, un mouvement
annulé le filigrane ANNULÉ. Chaque impression est journalisée.

### En-tête officiel des documents

Tous les PDF (bons, fiche de comptage, procès-verbal, états, rapport d'activité) portent le
même en-tête administratif, réglé par l'administrateur dans *Administration > En-tête des
documents* :

- **à gauche**, la hiérarchie administrative, du niveau le plus élevé au plus bas (ajouter,
  retirer, monter ou descendre un niveau) ;
- **à droite**, le nom de l'État et la devise (« BURKINA FASO — La Patrie ou la Mort, nous
  Vaincrons ») ;
- **les armoiries et le logo** de la structure (PNG ou JPEG, 1 Mo au plus), au centre ou
  au-dessus de leur colonne. L'image est enregistrée dès qu'elle est choisie ;
- **le libellé du document**, centré et encadré sous l'en-tête, suivi du numéro. Chaque
  libellé (« Bon de transfert », « Procès-verbal d'inventaire »…) peut être renommé ; le
  format du numéro accepte `{numero}` et `{annee}`, par exemple `N° {numero}/CHU-YO/DG` ;
- **« Fait à Ouagadougou, le … »** au-dessus des signatures (date du mouvement ou de
  l'inventaire ; en pointillés sur la fiche de comptage, remplie à la main) ;
- **le pied de page** : adresse, boîte postale, téléphone, puis l'auteur et la date d'édition
  et la pagination.

L'aperçu à droite de l'écran suit la saisie ; « Aperçu PDF » montre un bon d'exemple avec
l'en-tête enregistré. Chaque modification est journalisée.

## États

Stock par magasin, stock par catégorie, journal des mouvements, fiche de stock, transferts,
péremptions. Chaque état s'exporte en Excel ou en PDF (aperçu en modale) ; les exports sont
journalisés.

## Analyses

| Analyse | À quoi elle sert |
| --- | --- |
| Consommation par service | Qui consomme quoi sur une période ; services classés du plus au moins consommateur, part de chaque article. Sorties annulées exclues. |
| Couverture et rupture | Nombre de jours de stock au rythme des sorties récentes, date de rupture estimée et quantité à commander pour atteindre la couverture visée. |
| Articles dormants | Articles en stock sans sortie depuis N jours : surstock, produits à redistribuer ou à réformer. |
| Écarts d'inventaire | Écarts constatés par article et taux d'écart ; fiabilité globale du stock. |

## Statistiques

*Statistiques* : indicateurs de la période (quantités consommées, bons de sortie, services
servis, transferts et conformité des réceptions, alertes de stock, lots à surveiller ou
fiabilité d'inventaire) comparés à la période précédente de même durée ; graphiques de
l'activité (entrées, consommation, transferts), de la consommation par service, des
articles les plus consommés et de la santé du stock par magasin ; flux entre magasins.
Le bouton « Rapport d'activité » produit la synthèse imprimable en PDF.

## Journal d'audit

*Administration > Journal d'audit* : toutes les actions (validations, annulations,
transferts, inventaires, paramètres, connexions…) avec l'utilisateur, la date et l'adresse IP.
