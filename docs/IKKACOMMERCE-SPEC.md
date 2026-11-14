# IkkaCommerce — Spécification fonctionnelle et technique

> Logiciel de gestion de **boutiques de vente en gros et au détail**, **paramétrable** selon le métier :
> stock, ventes, caisse, clients et crédit, achats, livraisons, trésorerie.
> Construit sur le socle MyStock et sur le moteur commercial commun avec IkkaPharma.
>
> Version du document : 0.1 — 14 novembre 2026 — statut : **brouillon à valider avec des boutiques pilotes**.
> Document voisin : [IKKAPHARMA-SPEC.md](IKKAPHARMA-SPEC.md) (officine).

---

## Sommaire

1. [Vision et périmètre](#1-vision-et-périmètre)
2. [Paramétrage : profils d'activité et options](#2-paramétrage--profils-dactivité-et-options)
3. [Acteurs et rôles](#3-acteurs-et-rôles)
4. [Produits, conditionnements et unités](#4-produits-conditionnements-et-unités)
5. [Prix et tarification](#5-prix-et-tarification)
6. [Circuits de vente](#6-circuits-de-vente)
7. [Vente en gros : devis, commandes, livraisons](#7-vente-en-gros--devis-commandes-livraisons)
8. [Caisse](#8-caisse)
9. [Clients, crédit et recouvrement](#9-clients-crédit-et-recouvrement)
10. [Emballages consignés](#10-emballages-consignés)
11. [Stock, dépôts et boutiques](#11-stock-dépôts-et-boutiques)
12. [Achats, fournisseurs et coût de revient](#12-achats-fournisseurs-et-coût-de-revient)
13. [Trésorerie et dépenses](#13-trésorerie-et-dépenses)
14. [Promotions et fidélité](#14-promotions-et-fidélité)
15. [Retours, avoirs et garanties](#15-retours-avoirs-et-garanties)
16. [Pilotage et rapports](#16-pilotage-et-rapports)
17. [Modèle de données](#17-modèle-de-données)
18. [Règles de gestion](#18-règles-de-gestion)
19. [Écrans](#19-écrans)
20. [Architecture technique](#20-architecture-technique)
21. [Socle commun avec MyStock et IkkaPharma](#21-socle-commun-avec-mystock-et-ikkapharma)
22. [Feuille de route](#22-feuille-de-route)
23. [Risques](#23-risques)
24. [Questions ouvertes](#24-questions-ouvertes)
25. [Glossaire](#25-glossaire)

Priorités : **[V1]** indispensable au démarrage, **[V2]** version suivante, **[V3]** plus tard.

---

## 1. Vision et périmètre

### 1.1 Objectif

Donner au commerçant, du petit détaillant au grossiste multi-dépôts, un outil unique qui :

- sait **ce qu'il a, où, et ce que ça lui a coûté** ;
- **vend vite**, au détail comme en gros, sur smartphone, tablette ou PC ;
- **applique le bon prix** au bon client et à la bonne quantité, sans laisser brader la marchandise ;
- **suit l'argent** : caisse, Mobile Money, banque, crédits clients, dettes fournisseurs, dépenses ;
- s'adapte **au métier** par paramétrage, sans développement spécifique.

### 1.2 Cibles

| Type de commerce | Particularités principales |
|---|---|
| Alimentation générale, supérette | Détail rapide, code-barres, péremption, vente au poids |
| Grossiste alimentaire (riz, sucre, huile, farine) | Sacs et cartons, prix de gros, crédit revendeurs, livraison |
| Dépôt de boissons | Casiers et bouteilles **consignés**, livraisons, tournées |
| Quincaillerie, matériaux (ciment, fer, tôles) | Vente au mètre et à la pièce, **enlèvement au dépôt sur bon**, livraisons partielles |
| Textile, pagnes, chaussures | **Variantes** (taille, couleur, motif), vente au mètre ou à la pièce |
| Téléphonie, électroménager, informatique | **Numéros de série / IMEI**, **garantie** |
| Cosmétique, produits d'hygiène | Lots, péremption, détail et gros |
| Distribution de gaz | Bouteilles consignées (échange plein / vide) |
| Librairie, fournitures scolaires | Rentrée scolaire, listes, gros pour écoles |

### 1.3 Hors périmètre

- Comptabilité générale complète, paie : **exports** pour le comptable.
- Boutique en ligne complète avec paiement en ligne (un catalogue partageable est prévu en V3).
- Production / transformation industrielle (seule la composition simple de kits est prévue, §4.6).

### 1.4 Contraintes de contexte

| Contrainte | Conséquence |
|---|---|
| Coupures d'électricité et d'internet | Installation locale possible, **vente sur smartphone hors ligne** avec synchronisation |
| Beaucoup de boutiques sans PC | Tout doit fonctionner sur smartphone Android |
| Marchandage courant | Prix affiché + **prix plancher** contrôlé par rôle |
| Crédit aux clients très répandu | Plafonds, échéances, relances, relevés : fonction centrale, pas accessoire |
| Paiements Mobile Money | Orange Money, Moov Money et portefeuilles paramétrables |
| FCFA sans subdivision | Montants en entiers |
| Propriétaire souvent absent de la boutique | **Tableau de bord sur téléphone**, alertes (clôture, écart, annulation) |

---

## 2. Paramétrage : profils d'activité et options

Le paramétrage est le cœur du produit. Il se fait à trois niveaux.

### 2.1 Profil d'activité (à l'installation)

Un assistant d'installation demande le métier, puis active un **jeu d'options par défaut**, des **familles de produits types**, des **unités** et des **modèles de documents**. Tout reste modifiable ensuite.

| Profil | Options activées par défaut |
|---|---|
| Alimentation détail | code-barres, péremption, vente au poids, caisse directe, promotions |
| Grossiste alimentaire | niveaux de prix, crédit client, devis / commandes, livraison, conditionnements multiples |
| Dépôt de boissons | emballages consignés, livraison, tournées, crédit, niveaux de prix |
| Quincaillerie / matériaux | vente au mètre, enlèvement sur bon, livraison partielle, crédit, devis |
| Textile / chaussures | variantes, vente au mètre, promotions |
| Téléphonie / électroménager | numéros de série, garantie, réparations (V3) |
| Cosmétique | lots, péremption, niveaux de prix |
| Gaz | emballages consignés (échange), livraison |
| Généraliste | options minimales, à compléter |

### 2.2 Options activables (fonctionnalités)

| Option | Effet |
|---|---|
| `lots` | Saisie du lot à la réception, sortie FEFO / FIFO |
| `peremption` | Date de péremption par lot, alertes, blocage |
| `series` | Numéro de série / IMEI par unité, obligatoire à la vente |
| `garantie` | Durée de garantie par produit, certificat imprimé |
| `variantes` | Déclinaisons par attributs (taille, couleur…) |
| `vente_decimale` | Vente au poids, au mètre, au litre (unité de base fine, §4.3) |
| `balance` | Lecture des codes-barres de balance à poids / prix intégré |
| `emballages` | Gestion des emballages consignés (§10) |
| `livraison` | Bons de livraison, chauffeurs, tournées (§7.5) |
| `credit_client` | Ventes à crédit, plafonds, échéances (§9) |
| `niveaux_prix` | Détail, demi-gros, gros, revendeur… (§5) |
| `circuit_separe` | Vendeur puis caisse (§6.2) |
| `enlevement_bon` | Paiement au bureau puis enlèvement au dépôt (§6.3) |
| `stock_negatif` | Vente autorisée sans stock suffisant (avec alerte) |
| `multi_boutiques` | Plusieurs points de vente et dépôts |
| `commerciaux` | Prise de commande terrain, commissions |
| `promotions`, `fidelite` | §14 |
| `tva` | Calcul et affichage de la TVA ; sinon prix nets (régimes simplifiés) |
| `certification_fiscale` | Branchement de la facture électronique certifiée (§20.6) |

### 2.3 Paramètres fins

- **Champs personnalisés** sur les produits, clients et documents (ex. « marque », « couleur du sac », « numéro de camion »).
- **Modèles de documents** : ticket, facture, devis, bon de livraison, bon d'enlèvement ; en-tête et pied de page, logo, mentions légales (repris des en-têtes PDF de MyStock).
- **Formats de numérotation** par type de document et par boutique.
- **Taxes** : taux, familles concernées, prix saisis TTC ou HT.
- **Libellés** des niveaux de prix, des modes de paiement, des motifs (retour, dépense, remise).
- **Rôles et permissions** (repris de MyStock), plafonds de remise par rôle.
- **Arrondis** : pas d'arrondi des prix et de la monnaie (5, 25, 50 FCFA).

---

## 3. Acteurs et rôles

| Rôle | Missions principales |
|---|---|
| **Propriétaire** | Tout voir, prix, paramètres, validations sensibles, tableau de bord à distance |
| **Gérant de boutique** | Ouverture / clôture, validations (remises, retours, annulations), stock de sa boutique |
| **Vendeur** | Crée les ventes ou tickets, négocie dans les limites de son rôle |
| **Caissier** | Encaisse, rend la monnaie, clôture sa session |
| **Magasinier** | Réceptions, préparation, enlèvements sur bon, inventaires, transferts |
| **Livreur / chauffeur** | Livraisons, retours d'emballages, encaissements à la livraison |
| **Commercial terrain** | Prend des commandes chez les clients, suit leurs dettes |
| **Comptable / recouvrement** | Crédit client, relances, dettes fournisseurs, exports |
| **Administrateur** | Utilisateurs, appareils, sauvegardes |

Un utilisateur peut cumuler les rôles : dans une petite boutique, le propriétaire fait tout.

---

## 4. Produits, conditionnements et unités

### 4.1 Fiche produit

- [V1] Référence, désignation, famille, marque, code(s)-barres, photo, description courte.
- [V1] **Unité de base** et **conditionnements** (§4.2).
- [V1] Prix (§5), coût moyen, seuil d'alerte et stock minimum.
- [V1] Options par produit selon les options activées : lot, péremption, série, garantie, variante, consigné.
- [V1] Champs personnalisés.
- [V1] **Génération de codes internes** (EAN-13 préfixe `2` ou Code 128) et **étiquettes** (prix, code-barres, QR).
- [V2] Import du catalogue depuis Excel.

### 4.2 Conditionnements (même principe qu'IkkaPharma)

Chaque produit a une **unité de base** ; **le stock est toujours un entier en unités de base**. Les conditionnements sont des multiples.

| Produit | Unité de base | Conditionnements |
|---|---|---|
| Huile 1 L | bouteille | carton de 12, palette de 60 cartons |
| Sucre en morceaux | boîte | carton de 24 boîtes |
| Riz | **gramme** (si vente au kilo) ou sac | sac de 50 kg = 50 000 g ; kilo = 1 000 g |
| Boisson 33 cl | bouteille | casier de 24, palette |
| Ciment | sac | camion (paramétrable) |
| Câble électrique | **centimètre** | rouleau de 100 m = 10 000 cm ; mètre = 100 cm |
| Pagne | **centimètre** ou pièce | pièce de 6 yards, demi-pièce, mètre |
| Savon | morceau | carton de 72 |

Pour chaque conditionnement : quantité en unités de base, code-barres, achetable / vendable, conditionnement par défaut à l'achat et à la vente, prix par niveau (§5).

### 4.3 Vente au poids, au mètre, au litre

- On choisit une unité de base **assez fine** (gramme, centimètre, millilitre) : le stock reste entier.
- La saisie se fait dans l'unité commerciale avec décimales (« 2,5 kg », « 3,75 m ») ; le logiciel convertit (2 500 g, 375 cm).
- [V2] **Balance** : lecture des étiquettes à code-barres de poids / prix intégré (EAN-13 préfixe `2` : code article + poids ou prix).
- Précision d'affichage paramétrable par produit.

### 4.4 Variantes

- [V1 si option] Attributs paramétrables (taille, couleur, pointure, motif) ; chaque combinaison est un **article stocké** avec son code-barres et, si besoin, son prix.
- Grille de saisie rapide à la réception (tailles × couleurs).
- Rapports au niveau du modèle et de la variante.

### 4.5 Numéros de série et garantie

- [V1 si option] Saisie ou scan du numéro de série / IMEI à la réception et **à la vente** (obligatoire).
- Historique d'un numéro : reçu de quel fournisseur, vendu à qui, quand, sous quelle garantie.
- [V1 si option] **Certificat de garantie** imprimé avec la facture.
- [V3] Suivi des réparations et retours SAV.

### 4.6 Kits

- [V2] Produit composé (ex. « kit scolaire », « pack boisson ») : sa vente fait sortir ses composants du stock.

---

## 5. Prix et tarification

### 5.1 Niveaux de prix

- [V1] Niveaux paramétrables : **Détail**, **Demi-gros**, **Gros**, **Revendeur**, **Spécial** (noms et nombre libres).
- [V1] Un prix **par conditionnement vendable et par niveau** (ex. huile : bouteille détail 1 300, carton gros 14 400).
- [V1] Chaque client a un **niveau par défaut** ; le client de passage est au niveau Détail.

### 5.2 Prix dégressifs par quantité

- [V1] Paliers : « à partir de 10 cartons : 14 200 ; à partir de 50 cartons : 14 000 ».
- Le palier s'applique automatiquement dans le panier.

### 5.3 Prix spéciaux

- [V2] Prix négocié **par client** et par produit, avec date de fin.
- [V2] Prix par boutique (une boutique en zone rurale peut vendre plus cher).

### 5.4 Marchandage contrôlé

- [V1] Chaque prix a un **prix plancher** (ou une marge minimale).
- [V1] Le vendeur peut baisser jusqu'à son **plafond de remise** ; en dessous, validation par code PIN du gérant ; **jamais sous le coût de revient** sans validation du propriétaire.
- [V1] Toute baisse est tracée (vendeur, montant, motif).

### 5.5 Mise à jour des prix

- [V1] Calcul depuis le coût : coefficient ou marge par famille et par niveau, arrondi au pas paramétré.
- [V1] Prix **imposés** saisis à la main, jamais recalculés.
- [V2] Alerte « coût en hausse » à la réception : propose de mettre à jour les prix de vente.
- [V1] Historique des prix (date d'effet) ; réimpression des étiquettes concernées.

---

## 6. Circuits de vente

Le circuit est paramétré **par boutique**.

### 6.1 Caisse directe

Le vendeur est aussi caissier : panier → paiement → ticket. Petite boutique, supérette.

### 6.2 Vendeur puis caisse (`circuit_separe`)

Identique à IkkaPharma : le vendeur crée un ticket avec un **numéro court** (`A-047`), le stock est **réservé** ; le client paie à la caisse, qui l'appelle (écran et voix en option) ; le stock sort au paiement ; ticket non payé abandonné après un délai. Voir [IKKAPHARMA-SPEC.md §3](IKKAPHARMA-SPEC.md#3-le-circuit-de-vente).

### 6.3 Paiement au bureau, enlèvement au dépôt (`enlevement_bon`)

Circuit typique des matériaux et du gros :

```
BUREAU / CAISSE                         DÉPÔT                             CLIENT
 Vente + paiement (total ou acompte) ─►  Bon d'enlèvement « BE-0312 »  ◄── présente le bon
 Bon d'enlèvement imprimé (QR)            Magasinier scanne le QR,          (lui-même ou
                                          sert les quantités, signe          son chauffeur)
                                          Enlèvement partiel possible ───►  revient plus tard
                                                                             pour le reste
```

- Le stock est **réservé** au paiement et **sort à l'enlèvement**.
- Un bon peut être enlevé **en plusieurs fois** ; le reste à enlever est suivi.
- Le bon est protégé contre la fraude : QR unique, enlèvements tracés, bon soldé inutilisable.
- [V2] Délai maximal d'enlèvement paramétrable, avec alerte sur les marchandises payées non enlevées.

### 6.4 Vente sur commande et livraison

Voir §7.

### 6.5 Vente terrain (`commerciaux`)

- [V2] Le commercial prend la commande chez le client sur smartphone (même hors ligne), voit le **solde dû** et le plafond du client ; la commande arrive au dépôt pour préparation et livraison.

### 6.6 Interface de vente

- [V1] **Smartphone d'abord** (PWA installable), tablette et PC ; boutons tactiles larges ; mode sombre.
- [V1] Recherche instantanée, **scan par la caméra** ou douchette ; favoris et grille de produits rapides (photos) pour les boutiques sans code-barres.
- [V1] Choix du **conditionnement** sur la ligne, prix selon niveau et palier, stock disponible affiché.
- [V1] Choix du client (passage par défaut) ; affichage de son niveau, de son crédit disponible et de ses emballages dus.
- [V1] **Mise en attente** de plusieurs paniers.
- [V1] **Mode hors ligne** sur smartphone : ventes en file locale, synchronisées au retour du réseau (§20.4).

---

## 7. Vente en gros : devis, commandes, livraisons

### 7.1 Devis et proforma

- [V1] Devis avec validité, conditions, remise ; PDF ou envoi WhatsApp.
- [V1] Conversion en commande ou en vente en un clic (prix figés du devis).

### 7.2 Commande client

- [V1] Statuts : `brouillon → confirmee → en_preparation → prete → livree partiellement → livree → facturee / soldee`, ou `annulee`.
- [V1] **Réservation** du stock à la confirmation (paramétrable).
- [V1] **Acompte** encaissé à la commande ; reste dû suivi.

### 7.3 Facture

- [V1] Facture issue d'une vente, d'une commande ou de plusieurs bons de livraison (**facture récapitulative** sur une période).
- [V1] Numérotation continue sans trou ; correction uniquement par **avoir**.
- [V1] Échéance de paiement selon les conditions du client.

### 7.4 Ticket et facture nominative

- [V1] La vente au détail produit un **ticket** (client de passage).
- [V1] Sur demande, le ticket payé est transformé en **facture au nom du client** (même règle qu'IkkaPharma : montants inchangés, une seule facture, duplicata marqué).

### 7.5 Livraisons (`livraison`)

- [V1 si option] **Bon de livraison** issu d'une commande ou d'une vente ; livraison partielle et reste à livrer.
- [V1 si option] Affectation à un **chauffeur** et à un **véhicule** ; frais de livraison (forfait, zone, poids).
- [V1 si option] Suivi : `a_preparer → chargee → en_route → livree / refusee / partielle`.
- [V1 si option] **Preuve de livraison** : signature sur le téléphone, photo, nom du réceptionnaire.
- [V1 si option] **Encaissement à la livraison** par le livreur (espèces, Mobile Money) : le livreur a sa propre **session de caisse** et reverse à son retour.
- [V2] **Tournées** : plusieurs livraisons ordonnées, feuille de route, chargement du camion et retour des invendus et emballages.

---

## 8. Caisse

Repris du moteur IkkaPharma (voir [IKKAPHARMA-SPEC.md §7](IKKAPHARMA-SPEC.md#7-volet-caisse)), avec ces compléments :

- [V1] Modes de paiement **paramétrables** : espèces, Orange Money, Moov Money, autres portefeuilles, carte, virement, chèque, **crédit client**, **avoir**, **acompte**.
- [V1] **Paiement mixte** et **paiement partiel** (le reste passe en crédit si le client y a droit).
- [V1] Encaissement de **règlements de dettes** clients hors vente (le client vient payer son ardoise).
- [V1] Encaissement et remboursement de **consignes** (§10).
- [V1] Sessions : ouverture avec fond, entrées et sorties justifiées, **clôture avec comptage par coupure**, écart, **ticket Z**.
- [V1] **Remise au coffre / à la banque** tracée ; versement au propriétaire.
- [V1] Imprimante thermique 58 ou 80 mm, ou ticket envoyé par WhatsApp / SMS (boutique sans imprimante).
- [V2] Confirmation automatique Mobile Money par API.
- [V1] **Alerte au propriétaire** (notification) à chaque clôture : total, écart, annulations du jour.

---

## 9. Clients, crédit et recouvrement

### 9.1 Clients

- [V1] Client de passage par défaut ; fiche client facultative pour le détail, **obligatoire pour le crédit et le gros**.
- [V1] Fiche : nom ou raison sociale, téléphone (WhatsApp), adresse ou quartier, IFU, catégorie (particulier, revendeur, entreprise, administration), **niveau de prix**, conditions de paiement, commercial attitré.
- [V2] Localisation GPS pour les livraisons et tournées.

### 9.2 Crédit (`credit_client`)

- [V1] **Plafond de crédit** et **délai de paiement** par client.
- [V1] Vente à crédit refusée (ou soumise à validation du gérant) si le plafond est dépassé ou si une échéance est en retard.
- [V1] **Compte client** : ventes, paiements, avoirs, solde ; **relevé** imprimable ou envoyé par WhatsApp.
- [V1] Paiements partiels **imputés** sur les factures les plus anciennes (ou au choix).
- [V1] **Relances** : liste des retards, message WhatsApp ou SMS préparé (montant, échéance).
- [V2] **Balance âgée** (0-30, 31-60, 61-90, +90 jours), dettes par commercial.
- [V2] Garanties : caution, chèque de garantie.
- [V2] Créances douteuses / passage en perte avec validation du propriétaire.

### 9.3 Acomptes et avances

- [V1] Le client peut **déposer une avance** (crédit en sa faveur) consommée sur ses prochains achats.

---

## 10. Emballages consignés

Option `emballages` (boissons, gaz, eau en bonbonnes, palettes).

- [V1 si option] Un produit consigné est lié à son **emballage** (ex. « Bière 65 cl » → bouteille + casier de 12).
- [V1 si option] Les emballages ont leur **stock** (pleins, vides, cassés) et leur **montant de consigne**.
- [V1 si option] À la vente : la consigne est facturée **sauf si le client rend autant d'emballages vides** (échange).
- [V1 si option] **Compte emballages par client** : emballages dus, consigne versée ; relevé.
- [V1 si option] Retour d'emballages : remboursement de la consigne ou crédit au compte.
- [V2] Retour des emballages vides au fournisseur ; casse et pertes ; inventaire des emballages.

---

## 11. Stock, dépôts et boutiques

Repris de MyStock et étendu.

- [V1] **Boutiques** (points de vente) et **dépôts** (magasins) ; chaque boutique vend depuis un ou plusieurs dépôts.
- [V1] **Transferts** entre dépôts et boutiques, avec réception et écarts (repris de MyStock).
- [V1] Lots et sortie **FIFO** ou **FEFO** selon le produit (si option).
- [V1] **Inventaire** complet ou tournant, sur smartphone avec scan ; ajustements par inventaire uniquement.
- [V1] Stock disponible, réservé (tickets, commandes, bons d'enlèvement), en transit.
- [V1] Alertes : seuil, rupture, péremption (si option), produits dormants.
- [V1] **Valeur du stock** au coût de revient et au prix de vente.
- [V1] Pertes et casse (document de sortie motivé, validé par le gérant).
- [V2] Proposition de réapprovisionnement d'une boutique depuis le dépôt central.

---

## 12. Achats, fournisseurs et coût de revient

### 12.1 Fournisseurs et commandes

- [V1] Fiches fournisseurs ; **commande fournisseur** ; réception totale ou partielle ; rapprochement avec la facture fournisseur.
- [V1] **Proposition de commande** (consommation moyenne, stock, commandes en cours).
- [V1] **Dettes fournisseurs** : factures, échéances, paiements, solde.
- [V1] Retours fournisseurs et avoirs.

### 12.2 Coût de revient et importations

- [V1] Coût d'achat saisi par conditionnement ; coût par unité de base et **CUMP** (`decimal(14,4)`).
- [V2] **Dossier d'importation** : facture fournisseur en devise (taux de change saisi), puis **frais d'approche** (fret, transit, douane, manutention, transport jusqu'au dépôt), **répartis** sur les produits (au prorata de la valeur, du poids ou du volume) → **coût de revient réel** et marge juste.
- [V2] Comparatif de prix entre fournisseurs.

---

## 13. Trésorerie et dépenses

La fonction qui manque le plus aux commerçants : savoir **où est l'argent et ce que gagne vraiment la boutique**.

- [V1] **Comptes de trésorerie** : caisses, coffre, banque(s), comptes Mobile Money ; soldes en temps réel.
- [V1] **Mouvements entre comptes** : versement de la caisse en banque, dépôt Mobile Money, retrait.
- [V1] **Dépenses** de la boutique par catégorie paramétrable (loyer, électricité, eau, transport, salaires, impôts, entretien), avec justificatif photo.
- [V1] **Résultat simplifié** sur une période : ventes − coût des marchandises vendues = marge brute ; − dépenses = **résultat**.
- [V2] Rapprochement bancaire et Mobile Money (relevé importé).
- [V2] Prévision de trésorerie : encaissements attendus (crédits clients), décaissements prévus (dettes fournisseurs, charges fixes).

---

## 14. Promotions et fidélité

- [V1 si option] **Promotions** datées : prix barré, pourcentage, montant, **lot** (« 3 pour 2 », « 1 acheté = 1 offert »), seuil (« dès 10 000 FCFA d'achat »).
- [V1 si option] Ciblage : produit, famille, boutique, niveau de prix.
- [V2] **Fidélité** : points par achat, paliers, utilisation en paiement ; carte ou numéro de téléphone.
- [V2] **Bons d'achat** et cartes cadeaux numérotés.
- [V3] Campagnes WhatsApp vers les clients (nouveautés, promotions), avec consentement.

---

## 15. Retours, avoirs et garanties

- [V1] **Retour client** à partir du ticket ou de la facture d'origine, dans un délai paramétré, avec motif, **validé par le gérant**.
- [V1] Destination : remise en stock (si vendable), stock défectueux, retour fournisseur.
- [V1] Remboursement : mode d'origine, **avoir**, crédit sur le compte client, ou **échange** (nouvelle vente compensée).
- [V1] Avoir obligatoire pour annuler tout ou partie d'une facture.
- [V1 si option] **Garantie** : vérification par numéro de série, date de vente, durée ; échange ou réparation tracés.

---

## 16. Pilotage et rapports

### 16.1 Tableau de bord du propriétaire (smartphone)

- [V1] Aujourd'hui, par boutique : chiffre d'affaires, marge, nombre de ventes, panier moyen, encaissements par mode.
- [V1] **Trésorerie** : soldes des caisses, banque, Mobile Money.
- [V1] **Alertes** : clôtures et écarts, annulations, remises importantes, ventes sous le plancher, ruptures, crédits dépassés.
- [V1] Crédits clients totaux et en retard ; dettes fournisseurs à échéance.

### 16.2 Rapports

- [V1] Ventes par période, boutique, vendeur, famille, produit, client ; marge par produit.
- [V1] Top / flop produits, produits dormants.
- [V1] Journal de caisse, Z de caisse, encaissements.
- [V1] Comptes clients, relevés, retards.
- [V2] Commissions des vendeurs et commerciaux (taux paramétrables).
- [V2] Comparatif entre boutiques, évolution mois par mois.
- [V2] Exports comptables (journaux ventes, achats, caisse, banque) et Excel de tous les rapports.

---

## 17. Modèle de données

Conventions identiques à IkkaPharma : montants en `bigint` FCFA, coûts unitaires `decimal(14,4)`, quantités de stock en `integer` unités de base, documents validés non modifiables.

### 17.1 Paramétrage

- **`options`** : `code`, `active` (bool), `boutique_id` nullable (option globale ou par boutique).
- **`champs_personnalises`** : `entite` (`produit`, `client`, `document`), `code`, `libelle`, `type` (texte, nombre, date, liste), `valeurs` (json), `obligatoire`.
- **`valeurs_champs`** : `entite`, `entite_id`, `champ_id`, `valeur` (ou colonne `json` `extra` sur chaque entité).
- **`modeles_documents`** : type, boutique, en-tête, pied, mentions, format (A4, A5, ticket 58 / 80 mm).
- **`sequences`** : comme IkkaPharma, par type de document et par boutique.

### 17.2 Produits

- **`produits`** : `code`, `designation`, `famille_id`, `marque_id`, `unite_base`, `precision_affichage`, `gestion_lot`, `gestion_peremption`, `gestion_serie`, `garantie_mois`, `emballage_id` nullable, `modele_id` nullable (variantes), `seuil_alerte`, `stock_mini`, `taux_tva_id`, `prix_impose`, `extra` (json), `actif`.
- **`modeles`** et **`attributs`** / **`valeurs_attributs`** / **`produit_attributs`** : variantes (un produit stocké = une combinaison).
- **`conditionnements`** : `produit_id`, `libelle`, `quantite_base`, `code_barres`, `achetable`, `vendable`, `defaut_achat`, `defaut_vente`.
- **`kits`** / **`kit_composants`** : `produit_id` du kit, composant, quantité de base.

### 17.3 Prix

- **`niveaux_prix`** : `code`, `libelle`, `ordre`.
- **`prix`** : `conditionnement_id`, `niveau_prix_id`, `boutique_id` nullable, `prix`, `prix_plancher`, `mode` (`calcule` / `impose`), `date_effet`.
- **`paliers_prix`** : `conditionnement_id`, `niveau_prix_id`, `quantite_min`, `prix`.
- **`prix_clients`** : `client_id`, `conditionnement_id`, `prix`, `fin`.
- **`promotions`** / **`promotion_cibles`** : type, valeur, période, cibles, conditions.

### 17.4 Stock (repris de MyStock)

- **`boutiques`**, **`magasins`** (dépôts, rattachés à une boutique ou centraux).
- **`lots`** (`cout_unitaire`, `date_peremption` nullable), **`stocks`** (`quantite`, `quantite_reservee`), **`numeros_serie`** (`produit_id`, `numero`, `lot_id`, `statut` : en_stock, vendu, retourne, defectueux ; `vente_ligne_id`).
- **`documents`** / lignes : réception, vente, retour, transfert, inventaire, perte, enlèvement, livraison ; chaque ligne avec `conditionnement_id`, `quantite_saisie`, `quantite_base`.

### 17.5 Ventes

- **`ventes`** (ticket ou vente directe) : `numero`, `numero_appel` (circuit séparé), `boutique_id`, `vendeur_id`, `client_id` nullable, `niveau_prix_id`, `statut`, `total_ht`, `total_tva`, `total`, `remise`, `paye`, `reste_du`, `session_id`.
- **`lignes_vente`** : produit, conditionnement, quantités, prix catalogue, **prix appliqué**, remise, motif, validateur, coût figé, TVA ; **`ligne_vente_lots`** et numéros de série.
- **`devis`**, **`commandes_client`** / lignes, **`bons_enlevement`** / **`enlevements`** (quantités enlevées par passage), **`bons_livraison`** / lignes, **`livraisons`** (chauffeur, véhicule, statut, preuve), **`tournees`**.
- **`factures`** (vente, récapitulative, avoir) avec copie figée des coordonnées du client ; **`facture_documents`** (pivot vers ventes / BL).

### 17.6 Caisse et trésorerie

- **`comptes_tresorerie`** : type (`caisse`, `coffre`, `banque`, `mobile_money`), boutique, solde calculé.
- **`sessions_caisse`**, **`encaissements`**, **`paiements`** (mode paramétrable, référence), **`mouvements_tresorerie`** (entre comptes), **`depenses`** (catégorie, compte, justificatif).
- **`modes_paiement`** : code, libellé, compte de trésorerie associé, référence obligatoire (bool).

### 17.7 Tiers

- **`clients`** : catégorie, niveau de prix, plafond, délai, commercial, téléphone, `extra`.
- **`ecritures_clients`** : compte client en **ajout seul** (vente à crédit, paiement, avoir, avance, consigne), avec `lettrage`.
- **`fournisseurs`**, **`ecritures_fournisseurs`**, **`commandes_fournisseur`**, **`dossiers_import`** / **`frais_approche`**.

### 17.8 Emballages

- **`emballages`** : libellé, montant de consigne, produit d'emballage (stocké).
- **`comptes_emballages`** : `client_id`, `emballage_id`, quantité due, consigne détenue ; mouvements en ajout seul.

---

## 18. Règles de gestion

### Prix

- **RG-PRX-01** — Prix appliqué = prix client spécial en cours, sinon palier de quantité du niveau du client, sinon prix du niveau du client, sinon prix Détail ; puis promotion la plus avantageuse si cumulable selon le paramétrage.
- **RG-PRX-02** — Un prix appliqué inférieur au prix plancher exige une validation par code PIN d'un rôle autorisé ; inférieur au coût de revient, une validation du propriétaire.
- **RG-PRX-03** — La ligne de vente fige le prix catalogue, le prix appliqué et le coût.
- **RG-PRX-04** — Prix calculés arrondis au pas paramétré ; montants en FCFA entiers.

### Stock

- **RG-STK-01** — Stock en entiers d'unités de base ; saisie décimale convertie et arrondie à l'unité de base.
- **RG-STK-02** — Sans l'option `stock_negatif`, une vente ne peut dépasser le disponible (`quantite − quantite_reservee`).
- **RG-STK-03** — Produit à numéro de série : un numéro par unité vendue, numéro `en_stock` uniquement.
- **RG-STK-04** — Écarts corrigés par inventaire ou document de perte validé, jamais par modification directe.
- **RG-STK-05** — Réservation au ticket validé (circuit séparé), à la commande confirmée (si paramétré) ou au paiement d'un bon d'enlèvement ; sortie au paiement (ticket), à l'enlèvement ou à la livraison.

### Crédit

- **RG-CRD-01** — Une vente à crédit exige un client identifié avec l'option `credit_client` active.
- **RG-CRD-02** — Si `solde + reste_du > plafond` ou si une échéance est échue depuis plus du délai de tolérance, la vente à crédit est bloquée, sauf validation du gérant (tracée).
- **RG-CRD-03** — Le compte client ne s'écrit qu'en ajout ; un paiement s'impute sur les créances les plus anciennes sauf choix explicite.

### Enlèvement et livraison

- **RG-ENL-01** — Quantité enlevée cumulée ≤ quantité vendue, ligne par ligne ; un bon soldé ne peut plus être utilisé.
- **RG-ENL-02** — Un enlèvement ou une livraison n'est possible que si le bon est payé ou couvert par le crédit autorisé.
- **RG-LIV-01** — L'encaissement à la livraison se fait dans la session de caisse du livreur, clôturée à son retour.

### Emballages

- **RG-EMB-01** — Consigne due = (emballages livrés − emballages vides rendus) × montant de consigne ; elle est facturée ou portée au compte emballages selon le paramétrage du client.
- **RG-EMB-02** — Le remboursement d'une consigne ne peut dépasser les emballages dus par l'établissement au client.

### Caisse, facturation, retours

- **RG-CAI-01** — Aucun encaissement hors session ouverte ; Mobile Money sans référence refusé si le mode l'exige.
- **RG-CAI-02** — Ticket Z définitif ; écart non nul commenté ; notification au propriétaire.
- **RG-FAC-01** — Numérotation continue par type de document, boutique et exercice ; correction par avoir uniquement.
- **RG-RET-01** — Retour sur document d'origine, dans le délai, validé par le gérant ; quantité retournée ≤ quantité vendue − déjà retournée.

### Hors ligne

- **RG-OFF-01** — Une vente hors ligne reçoit un numéro provisoire propre à l'appareil, remplacé à la synchronisation par le numéro définitif (le ticket imprimé hors ligne porte le numéro provisoire, rattaché ensuite).
- **RG-OFF-02** — À la synchronisation, une vente qui rend le stock négatif est acceptée (la vente a eu lieu) mais signalée au gérant pour contrôle.
- **RG-OFF-03** — Le crédit client hors ligne est limité au crédit disponible connu à la dernière synchronisation.

---

## 19. Écrans

| # | Écran | Appareil | Contenu |
|---|---|---|---|
| E01 | Assistant d'installation | PC, tablette | profil d'activité, boutiques, options, niveaux de prix, modes de paiement, modèles de documents |
| E02 | Connexion par code PIN | tous | utilisateurs de l'appareil |
| E03 | **Vente** | smartphone, tablette, PC | recherche, scan, grille de favoris, panier (conditionnement, prix, palier, remise), client, crédit disponible, emballages, mise en attente |
| E04 | Paiement | tous | modes, paiement mixte / partiel, monnaie, ticket imprimé ou WhatsApp |
| E05 | File d'attente caisse | tablette, PC | (circuit séparé) appel, encaissement |
| E06 | Bon d'enlèvement — dépôt | smartphone | scan du QR, quantités restantes, enlèvement partiel, signature |
| E07 | Devis / commandes / factures | PC, tablette | liste, création, conversion, PDF, envoi |
| E08 | Livraisons et tournées | PC (planification), smartphone (livreur) | affectation, statut, preuve, encaissement |
| E09 | Session de caisse | tous | ouverture, mouvements, clôture, Z |
| E10 | Clients et comptes | tous | fiche, relevé, relance WhatsApp, balance âgée |
| E11 | Emballages | tablette, PC | compte par client, retours, stock d'emballages |
| E12 | Produits | PC, tablette | fiche, conditionnements, prix par niveau, paliers, variantes, séries, étiquettes |
| E13 | Réception | smartphone, PC | scan, quantités, lots, séries, coûts, étiquettes |
| E14 | Achats et fournisseurs | PC | commandes, factures, dettes, dossiers d'import |
| E15 | Stock | tous | disponible / réservé / transit, transferts, inventaire, pertes |
| E16 | Trésorerie et dépenses | smartphone, PC | soldes, mouvements entre comptes, dépenses avec photo, résultat |
| E17 | **Tableau de bord propriétaire** | smartphone | indicateurs du jour, alertes, trésorerie, crédits |
| E18 | Rapports | PC | ventes, marges, commissions, exports |
| E19 | Paramètres | PC | options, champs personnalisés, documents, taxes, rôles, appareils |

---

## 20. Architecture technique

### 20.1 Socle

Identique à IkkaPharma : **Laravel 12**, PHP 8.2+, **PostgreSQL**, modules nwidart, spatie/permission, spatie/activitylog, dompdf, phpspreadsheet ; back-office Blade ; écrans de vente en interface réactive (Livewire 3 ou Alpine.js + API), PWA ; temps réel par Laravel Reverb.

### 20.2 Modules

| Module | Contenu |
|---|---|
| `Core` | utilisateurs, rôles, permissions, **options**, champs personnalisés, paramètres, journal, PDF, séquences |
| `Referentiel` | produits, conditionnements, variantes, séries, familles, marques, boutiques, dépôts |
| `Tarifs` | niveaux, prix, paliers, prix clients, promotions |
| `Stock` | lots, stocks, documents, transferts, inventaires (repris de MyStock) |
| `Ventes` | ventes / tickets, devis, commandes, factures, avoirs, retours |
| `Logistique` | bons d'enlèvement, bons de livraison, livraisons, tournées |
| `Caisse` | sessions, encaissements, paiements, modes de paiement |
| `Tiers` | clients, comptes clients, fournisseurs, comptes fournisseurs |
| `Emballages` | consignes et comptes emballages |
| `Achats` | commandes fournisseurs, réceptions, dossiers d'importation |
| `Tresorerie` | comptes, mouvements, dépenses, résultat |
| `Fidelite` | points, bons d'achat |
| `Etats` | tableaux de bord, rapports, exports |

Chaque option du §2.2 est contrôlée par un **service d'options** (`Options::active('emballages', $boutique)`) utilisé dans les règles, les menus et les vues ; un module dont toutes les options sont inactives disparaît des menus (mécanisme de modules de MyStock).

Communication par événements : `VenteValidee`, `VentePayee`, `EnlevementEffectue`, `LivraisonEffectuee`, `ReceptionValidee`, `SessionCloturee`, `RetourValide`.

### 20.3 Intégrité

Transactions par opération, verrouillage des stocks et séquences, documents en ajout seul, contre-documents pour corriger. Tests automatisés : prix appliqué (niveaux, paliers, promotions, plancher), conditionnements et unités fines, réservations concurrentes, enlèvements partiels, consignes, crédit et plafonds, synchronisation hors ligne.

### 20.4 Hors ligne

- L'application de vente sur smartphone garde en local (IndexedDB) le catalogue, les prix, les clients et leur crédit disponible, les stocks de la boutique.
- Les ventes hors ligne sont mises en file et **synchronisées** dès que le réseau revient (identifiant unique généré sur l'appareil pour éviter les doublons).
- Les fonctions qui exigent une vérification centrale (crédit au-delà du disponible connu, enlèvement sur bon, retours) restent indisponibles hors ligne.

### 20.5 Modes d'exploitation

| Mode | Pour qui | Principe |
|---|---|---|
| **Installation locale** | Grossiste, magasin avec dépôt | Serveur dans la boutique (mini-PC + onduleur), sauvegarde hors site |
| **Hébergé (abonnement)** | Petites boutiques, réseaux de boutiques | Serveur central, **une base de données par client** (isolation forte), application smartphone hors ligne |

Le même code sert les deux modes ; le choix multi-client (une base par client) est fait dès la conception pour ne pas devoir réécrire.

### 20.6 Fiscalité

- TVA paramétrable (taux, familles, prix TTC ou HT), option désactivable pour les régimes simplifiés.
- Interface `CertificationFiscale` (même principe qu'IkkaPharma) appelée à chaque ticket payé, facture et avoir, pour brancher la **facture électronique certifiée** de la DGI lorsque le commerce y est soumis.

### 20.7 Matériel

Smartphone Android (minimum), tablette, PC ; imprimante thermique 58 / 80 mm (Bluetooth ou USB) ; douchette ; balance à étiquettes (V2) ; imprimante d'étiquettes ; tiroir-caisse ; serveur local et onduleur en installation locale.

---

## 21. Socle commun avec MyStock et IkkaPharma

Trois produits partagent désormais beaucoup de choses. Recommandation :

```
                ┌───────────────────────────────────────────────┐
                │  ikka/core (socle)                            │
                │  utilisateurs, rôles, options, paramètres,    │
                │  journal, PDF, séquences, identité            │
                └───────────────┬───────────────────────────────┘
                                │
                ┌───────────────┴───────────────────────────────┐
                │  ikka/stock (repris de MyStock)               │
                │  lots, stocks, documents, transferts, invent. │
                └───────┬───────────────────────────┬───────────┘
                        │                           │
        ┌───────────────┴──────────┐   ┌────────────┴──────────────────────┐
        │  MyStock (hôpital)       │   │  ikka/commerce (moteur commercial) │
        │  directions, services    │   │  produits + conditionnements,      │
        └──────────────────────────┘   │  prix, ventes, caisse, tiers,      │
                                       │  achats, trésorerie                │
                                       └───────┬──────────────────┬─────────┘
                                               │                  │
                                 ┌─────────────┴──────┐  ┌────────┴────────────────┐
                                 │  IkkaCommerce      │  │  IkkaPharma             │
                                 │  options métier,   │  │  + module pharmacie :   │
                                 │  emballages,       │  │  DCI, ordonnances,      │
                                 │  livraisons, gros  │  │  assurances, stupéfiants│
                                 └────────────────────┘  └─────────────────────────┘
```

- **Ne pas écrire deux fois** le moteur commercial : conditionnements, tickets et réservation, caisse, factures, avoirs, retours, clients et fournisseurs sont identiques dans IkkaPharma et IkkaCommerce.
- **Ordre conseillé** : développer le moteur commercial **une fois**, dans le premier des deux produits mis en chantier, en modules propres et sans dépendance métier ; l'extraire en paquets Composer (`ikka/core`, `ikka/stock`, `ikka/commerce`) dès que le second produit démarre.
- IkkaPharma devient alors : moteur commercial + module `Pharmacie` (DCI, ordonnances, assurances et tiers payeurs, ordonnancier, stupéfiants) avec ses options propres.
- MyStock reste sur son socle actuel ; il pourra adopter `ikka/core` plus tard, sans urgence.

Repris de MyStock (voir aussi [IKKAPHARMA-SPEC.md §16](IKKAPHARMA-SPEC.md#16-réutilisation-de-mystock)) : `Core` (utilisateurs, rôles, modules, permissions, journal, PDF, identité, pages d'erreur, en-têtes de sécurité), `Referentiel` (`Parametres`, `Magasin`, `EnteteDocuments` ; `Article` / `Unite` transformés en `Produit` / `Conditionnement`), `Stock` (documents, lots, transferts, inventaires), `Etats` (alertes, analyses, exports). Retirés : `Direction`, `Service`, `ResponsableMagasin`.

---

## 22. Feuille de route

| Phase | Contenu | Durée indicative* | Résultat |
|---|---|---|---|
| **P0 — Socle** | Dépôt, socle repris de MyStock, **service d'options**, champs personnalisés, produits et conditionnements, assistant d'installation | 2 à 3 semaines | Base paramétrable |
| **P1 — Vente au détail** | Vente sur smartphone / tablette, niveaux de prix et plancher, caisse directe, sessions et Z, ticket thermique / WhatsApp, clients de passage, facture nominative, retours | 5 semaines | **V1 pour boutique de détail** |
| **P2 — Gros et crédit** | Clients et crédit, comptes et relances, paliers, devis, commandes, factures récapitulatives, acomptes, bons d'enlèvement, circuit séparé | 5 semaines | **V1 pour grossiste** |
| **P3 — Achats et trésorerie** | Fournisseurs, commandes, réceptions, dettes, comptes de trésorerie, dépenses, résultat simplifié, tableau de bord propriétaire | 4 semaines | Pilotage financier |
| **P4 — Options métier** | Emballages consignés, livraisons, variantes, séries et garantie, vente au poids / mètre, promotions | 5 semaines | Couverture des profils |
| **P5 — Mobilité et réseau** | Hors ligne complet, commerciaux terrain, tournées, multi-boutiques avancé, dossiers d'import, fidélité, mode hébergé | continu | Croissance |

\* Un développeur à plein temps assisté ; à réévaluer après P0. Si le moteur commercial est d'abord construit dans IkkaPharma, P1 et une partie de P2 sont largement raccourcies.

---

## 23. Risques

| Risque | Parade |
|---|---|
| **Trop de paramètres** : logiciel compliqué à configurer | Profils d'activité qui règlent tout par défaut ; options avancées masquées ; assistant d'installation |
| Paramétrage qui casse la cohérence (option désactivée alors que des données existent) | Désactivation interdite ou non destructive quand des données dépendent de l'option |
| Lenteur ou complexité au comptoir | Vente simple en moins de 15 s, favoris, scan, peu d'écrans |
| Hors ligne : doublons, stocks faux | Identifiants uniques par appareil, synchronisation idempotente, contrôle des stocks négatifs |
| Fraude (remises, annulations, enlèvements multiples) | Prix plancher, validations PIN, journal, bon d'enlèvement à QR unique, alertes au propriétaire |
| Crédits non recouvrés | Plafonds bloquants, relances, balance âgée, tableau de bord |
| Dispersion entre IkkaPharma et IkkaCommerce | Socle commun (§21) décidé avant le premier développement |
| Réglementation fiscale (facture certifiée, TVA) | Point d'accroche fiscal, validation avec un comptable |

---

## 24. Questions ouvertes

1. Quelles **boutiques pilotes** (au moins une de détail et un grossiste) ?
2. Quel produit démarre en premier : IkkaPharma ou IkkaCommerce ? (détermine où naît le moteur commercial)
3. Mode d'exploitation prioritaire : installation locale ou hébergé par abonnement ?
4. Quels profils d'activité livrer en V1 (recommandé : alimentation détail, grossiste alimentaire, quincaillerie) ?
5. Les ventes à crédit des pilotes : plafonds, délais, pratiques de relance actuelles ?
6. Faut-il gérer plusieurs devises à l'achat dès la V1 (importateurs) ?
7. Les pilotes sont-ils soumis à la TVA et à la facture électronique certifiée ?
8. Matériel disponible (smartphones, imprimantes, PC) ?
9. Modèle de prix de vente du logiciel : licence, abonnement par boutique, par utilisateur ?

---

## 25. Glossaire

| Terme | Définition |
|---|---|
| **Profil d'activité** | Jeu de réglages par défaut correspondant à un métier |
| **Option** | Fonctionnalité activable (lots, séries, emballages, crédit…) |
| **Unité de base** | Plus petite quantité vendable ; unité de compte du stock |
| **Conditionnement** | Présentation d'un produit (carton, sac, casier, mètre) en unités de base |
| **Niveau de prix** | Grille tarifaire (détail, demi-gros, gros, revendeur…) |
| **Palier** | Prix dégressif à partir d'une quantité |
| **Prix plancher** | Prix minimum autorisé sans validation |
| **Bon d'enlèvement** | Document remis au client après paiement pour retirer la marchandise au dépôt |
| **Bon de livraison (BL)** | Document accompagnant une livraison, signé par le client |
| **Consigne** | Somme versée pour un emballage, remboursée à son retour |
| **Compte client** | Historique des ventes à crédit, paiements et avoirs d'un client |
| **Balance âgée** | Répartition des dettes clients selon leur ancienneté |
| **Frais d'approche** | Frais d'acheminement d'une marchandise jusqu'au dépôt (fret, transit, douane) |
| **Coût de revient** | Prix d'achat + frais d'approche, par unité |
| **CUMP** | Coût unitaire moyen pondéré |
| **Ticket Z** | Récapitulatif définitif de clôture de caisse |
| **IFU** | Identifiant financier unique (Burkina Faso) |
