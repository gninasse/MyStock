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

## États

Stock par magasin, stock par catégorie, journal des mouvements, fiche de stock, transferts,
péremptions. Chaque état s'imprime et s'exporte en Excel ou en PDF ; les exports sont
journalisés.

## Journal d'audit

*Administration > Journal d'audit* : toutes les actions (validations, annulations,
transferts, inventaires, paramètres, connexions…) avec l'utilisateur, la date et l'adresse IP.
