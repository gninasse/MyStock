# IkkaPharma — Spécification fonctionnelle et technique

> Logiciel de gestion d'officine : **stock**, **facturation au comptoir** et **caisse**.
> Construit à partir du socle MyStock (Laravel 12, modules nwidart, PostgreSQL).
>
> Version du document : 0.1 — 13 novembre 2026 — statut : **brouillon à valider avec la pharmacie pilote**.

---

## Sommaire

1. [Vision et périmètre](#1-vision-et-périmètre)
2. [Acteurs et rôles](#2-acteurs-et-rôles)
3. [Le circuit de vente](#3-le-circuit-de-vente)
4. [Volet stock](#4-volet-stock)
5. [Conditionnements](#5-conditionnements)
6. [Volet comptoir (création des tickets)](#6-volet-comptoir-création-des-tickets)
7. [Volet caisse](#7-volet-caisse)
8. [Facture nominative](#8-facture-nominative)
9. [Assurances et tiers payeurs](#9-assurances-et-tiers-payeurs)
10. [Remboursements et retours](#10-remboursements-et-retours)
11. [Fonctions transverses](#11-fonctions-transverses)
12. [Modèle de données](#12-modèle-de-données)
13. [Règles de gestion](#13-règles-de-gestion)
14. [Écrans de la V1](#14-écrans-de-la-v1)
15. [Architecture technique](#15-architecture-technique)
16. [Réutilisation de MyStock](#16-réutilisation-de-mystock)
17. [Feuille de route](#17-feuille-de-route)
18. [Risques](#18-risques)
19. [Questions ouvertes](#19-questions-ouvertes)
20. [Glossaire](#20-glossaire)

Priorités utilisées dans tout le document : **[V1]** indispensable au démarrage, **[V2]** version suivante, **[V3]** plus tard.

---

## 1. Vision et périmètre

### 1.1 Objectif

Remplacer le cahier, la calculatrice et la caisse enregistreuse d'une officine par un outil unique qui :

- sait à tout moment **ce qu'il reste, en quel lot et jusqu'à quand** (péremptions, FEFO) ;
- permet de **vendre vite au comptoir sur tablette**, à la boîte comme au comprimé ;
- **sépare la vente de l'encaissement** (la vendeuse ne touche pas l'argent, la caissière ne modifie pas un ticket) ;
- gère les **assurances et tiers payeurs** (dont l'État) et **récupère l'argent** qu'ils doivent ;
- donne au pharmacien titulaire **son chiffre, sa marge, sa valeur de stock et ses créances**.

### 1.2 Hors périmètre

- Comptabilité générale complète, paie, ressources humaines : on produit des **exports** pour le cabinet comptable.
- Dossier médical du patient.
- Alertes d'interactions médicamenteuses sans base de données médicale reconnue sous licence (voir §11.6).

### 1.3 Contraintes de contexte

| Contrainte | Conséquence |
|---|---|
| Coupures d'électricité et d'internet fréquentes | Serveur **local** dans l'officine, réseau local, onduleur ; internet seulement pour la sauvegarde et les services en ligne |
| Ventes majoritairement anonymes | Aucune saisie de nom obligatoire ; le ticket suffit |
| Vente au détail courante (comprimés, plaquettes) | Stock tenu en **unités de base** (§5) |
| Paiement Mobile Money très répandu | Orange Money et Moov Money dès la V1 |
| Monnaie FCFA sans subdivision | Montants en **entiers** FCFA |
| Personnel peu habitué à l'informatique | Interface tactile, gros boutons, peu d'écrans, vocabulaire de l'officine |

---

## 2. Acteurs et rôles

| Rôle | Fait | Ne fait pas |
|---|---|---|
| **Vendeuse / agent de comptoir** | Crée les tickets, applique l'assurance, photographie ordonnances et cartes, remet le numéro au client | N'encaisse pas, n'accorde pas de remise au-delà de son plafond |
| **Caissière** | Appelle les tickets, encaisse, remet les produits, établit la facture nominative, ouvre et clôture sa session | Ne modifie ni les lignes ni les prix d'un ticket |
| **Préparateur / gestionnaire de stock** | Commandes, réceptions, étiquettes, inventaires, transferts réserve → rayon | N'encaisse pas |
| **Pharmacien (adjoint)** | Valide les remises exceptionnelles, les retours, les annulations, le changement de lot, les ventes sur ordonnance sensibles | — |
| **Pharmacien titulaire** | Tout ce qui précède, plus les prix, conventions, paramètres, tableaux de bord | — |
| **Comptable / recouvrement** | Bordereaux d'organismes, suivi des paiements et des rejets, exports comptables | Ne vend pas |
| **Administrateur** | Utilisateurs, rôles, matériel, sauvegardes | — |

Les rôles sont entièrement paramétrables (repris de MyStock : permissions par module et par action). Un même utilisateur peut cumuler des rôles (petite officine : comptoir et caisse).

---

## 3. Le circuit de vente

### 3.1 Déroulement

```
COMPTOIR (tablette)                 CAISSE                         CLIENT
 Vendeuse : scanne / cherche   ──►  Le ticket apparaît en      ◄── Reçoit le n° « A-047 »
 les produits, applique              « en attente » avec ses        et le montant
 l'assurance, valide le ticket       produits                        │
 Pose les produits au panier   ──►  Annonce « Ticket 47 »     ◄─────┘ vient payer
 de la caisse                        (écran + voix)
                                     Encaisse ─► remet produits + ticket de caisse
                                                          │
                              (sur demande) facture à son nom ◄── donne nom et prénom
```

1. La vendeuse compose le panier sur la tablette et valide : un **ticket** est créé avec un **numéro court** (ex. `A-047`).
2. Le stock des produits est **réservé** (pas encore sorti).
3. La vendeuse pose les produits dans le **bac de la caisse**, remet au client son numéro (papier ou oral) et éventuellement le montant à payer.
4. La caissière voit le ticket dans la file d'attente, l'**appelle** (écran d'appel + annonce vocale).
5. Le client paie sa part ; la caissière **encaisse**, remet les produits et le ticket de caisse : le stock **sort** à ce moment.
6. Si le client le demande, la caissière transforme le ticket en **facture à son nom** (§8).

En petite officine, un paramètre permet le **mode comptoir unique** : la même personne crée le ticket et l'encaisse dans la foulée.

### 3.2 Cycle de vie d'un ticket

```
              ┌─────────────┐  valider   ┌────────────┐  appeler  ┌─────────┐  encaisser  ┌──────┐  remettre  ┌────────┐
  nouveau ──► │ en_saisie   │ ─────────► │ en_attente │ ────────► │ appele  │ ──────────► │ paye │ ─────────► │ remis  │
              └─────────────┘            └────────────┘           └─────────┘             └──────┘            └────────┘
                    │ suspendre ▲               │  │                  │ absent → retour       │
                    ▼           │ reprendre     │  │                  ▼ en_attente            │ retour (§10)
              ┌─────────────┐                   │  │ délai dépassé / clôture / annulation    ▼
              │  suspendu   │                   ▼  ▼                                 ┌──────────────┐
              └─────────────┘              ┌────────────┐                            │ rembourse    │
                                           │ abandonne  │  (stock libéré)            │ (partiel/tot)│
                                           └────────────┘                            └──────────────┘
```

| Statut | Stock | Modifiable | Qui |
|---|---|---|---|
| `en_saisie` | rien | oui | vendeuse |
| `suspendu` | rien | oui (à la reprise) | vendeuse |
| `en_attente` | **réservé** | non (sauf annulation par la vendeuse avant appel) | — |
| `appele` | réservé | non | caissière |
| `paye` | **sorti** | non | caissière |
| `remis` | sorti | non | caissière |
| `abandonne` | libéré | non | automatique ou caissière |
| `rembourse` | réintégré ou quarantaine selon le retour | non | pharmacien |

### 3.3 Numérotation

- **Numéro d'appel** : lettre du poste de comptoir + numéro du jour, remis à 1 chaque jour (`A-001`, `B-001`…). Court, facile à crier.
- **Numéro comptable** : séquence continue par exercice, sans trou (`T2026-000123`), imprimé sur le ticket de caisse. C'est lui qui fait foi.

---

## 4. Volet stock

### 4.1 Références, codes-barres, QR codes et étiquettes

- [V1] Saisie de la **référence** du produit, ou lecture de son **code EAN** (douchette ou caméra de la tablette / du téléphone).
- [V1] **Génération d'un code interne** pour ce qui n'a pas de code-barres (plaquettes, comprimés, produits locaux, préparations) : EAN-13 dans la plage réservée à l'usage interne (préfixe `2`) ou Code 128.
- [V1] **Étiquettes à la réception** : nom, dosage, prix, lot, date de péremption, code-barres ou **QR code**.
  - Le QR code encode produit + conditionnement + lot + péremption : au comptoir, un seul scan choisit le bon produit **et** le bon lot.
- [V1] Impression sur imprimante d'étiquettes thermique ou sur planche A4 d'étiquettes adhésives.
- [V2] Lecture du **DataMatrix GS1** des boîtes (GTIN, lot, péremption) qui pré-remplit la réception.
- [V2] Réimpression des étiquettes d'un rayon après un changement de prix.

### 4.2 Achats

- [V1] Fiches **fournisseurs** : grossistes répartiteurs, CAMEG, laboratoires ; délais et conditions.
- [V1] **Proposition de commande** automatique : `besoin = (consommation moyenne journalière × couverture visée) + stock mini − stock disponible − commandes en cours`, arrondie au conditionnement d'achat.
- [V1] **Bon de commande** (PDF avec en-tête officiel, envoi WhatsApp ou e-mail).
- [V1] **Réception** liée ou non à une commande : contrôle quantité, lot, péremption, prix d'achat ; écarts signalés ; rapprochement avec la facture fournisseur (numéro, montant).
- [V1] **Retour fournisseur** (périmé, abîmé, erreur) et suivi de l'avoir attendu.
- [V2] Import des factures et catalogues des grossistes (Excel / CSV).
- [V2] Comparatif des prix d'achat par fournisseur.

### 4.3 Tenue du stock (repris de MyStock)

- [V1] **Lots** avec date de péremption, sortie en **FEFO** (premier périmé, premier sorti).
- [V1] Magasins : **réserve**, **rayon**, **quarantaine** ; transferts réserve → rayon.
- [V1] Alertes de péremption à 30 / 60 / 90 jours (paramétrables), mise en **quarantaine**, **procès-verbal de destruction**.
- [V1] **Inventaire** complet ou tournant par rayon, sur tablette avec scan ; les écarts ne sont ajustés **que** par l'inventaire (règle MyStock).
- [V1] **Valeur du stock** au coût moyen (CUMP) et au prix de vente.
- [V2] **Rappel de lot** : blocage en un clic, vente impossible, liste des ventes concernées.
- [V2] Articles dormants, ruptures fréquentes, classement ABC.
- [V2] **Registre des stupéfiants** et **ordonnancier** générés depuis les mouvements.

---

## 5. Conditionnements

### 5.1 Principe

Chaque produit a une **unité de base** : la plus petite quantité vendable (comprimé, gélule, ampoule, sachet, flacon, paire…). **Toutes les quantités de stock sont des entiers en unités de base.** Les conditionnements sont des multiples de l'unité de base.

Exemple — paracétamol 500 mg :

| Conditionnement | Contient | Unités de base | Achetable | Vendable | Code-barres |
|---|---|---|---|---|---|
| Carton | 20 boîtes | 2 000 | ✅ | ❌ | grossiste |
| **Boîte** | 10 plaquettes | 100 | ✅ (défaut) | ✅ (défaut) | EAN laboratoire |
| Plaquette | 10 comprimés | 10 | ❌ | ✅ | code interne généré |
| **Comprimé** | — | 1 | ❌ | ✅ | QR interne (sachet) |

On achète en carton ou en boîte, on vend en boîte, plaquette ou comprimé : le stock ne connaît que des comprimés.

Conséquences :

- jamais de quantité à virgule (« 0,3 boîte » n'existe pas : c'est « 30 cp ») ;
- pas de document de « déconditionnement » à saisir : vendre 6 comprimés retire 6 unités du lot ;
- **affichage décomposé** pour l'humain : `12 boîtes + 37 cp` (décomposition gloutonne sur les conditionnements vendables) ;
- le FEFO et les lots fonctionnent sans changement.

### 5.2 Attributs d'un conditionnement

| Attribut | Rôle |
|---|---|
| `libelle` | Boîte, Plaquette, Comprimé, Carton… |
| `quantite_base` | Nombre d'unités de base contenues (entier ≥ 1) |
| `parent_id` | Conditionnement qui le contient (pour l'affichage et les étiquettes) |
| `code_barres` | EAN du laboratoire ou code interne généré (unique) |
| `achetable`, `vendable` | Utilisable à l'achat / à la vente |
| `defaut_achat`, `defaut_vente` | Proposé par défaut |
| prix de vente | Voir §5.3 |

Un produit **non divisible** (sirop, tube, poche) a une seule unité vendable : son unité de base est le flacon / le tube.

### 5.3 Coûts et prix

**Coût d'achat.** On saisit le prix du conditionnement acheté (1 000 FCFA la boîte de 100). Le coût par unité de base (10 FCFA) est conservé sur le **lot** avec 4 décimales (`decimal(14,4)` : 1 250 / 30 = 41,6667). Le **CUMP** du produit est recalculé à chaque réception.

**Prix de vente.** Chaque conditionnement vendable a son prix, en FCFA entiers, de deux façons :

- **calculé** : `prix_boite ÷ quantite_base × quantite_base_du_conditionnement × (1 + majoration_detail)`, arrondi au pas paramétré (5, 25 ou 50 FCFA) ;
- **imposé** : saisi à la main (prix réglementés, politique de l'officine). Un produit à prix imposé n'est jamais recalculé automatiquement.

| Vente | Prix | Équivalent / cp |
|---|---|---|
| 1 boîte (100 cp) | 1 500 FCFA | 15 |
| 1 plaquette (10 cp) | 175 FCFA | 17,5 |
| 1 comprimé | 25 FCFA | 25 |

Les prix sont **historisés** (date d'effet) : une vente garde toujours le prix du jour où elle a été faite.

**Marge** calculée par ligne : `montant_ligne − quantite_base × cout_unitaire_du_lot`.

### 5.4 Au comptoir

- Boutons **[Boîte] [Plaquette] [Comprimé]** sur la ligne du panier ; le prix se met à jour.
- Le scan de l'EAN de la boîte sélectionne « Boîte » ; on bascule en comprimés d'un geste.
- Contrôle de disponibilité en unités de base : « Il reste 2 boîtes + 7 cp ; demande : 250 cp — insuffisant ».
- **Boîtes entamées d'abord** : dans un lot, on puise d'abord dans une boîte déjà ouverte (compteur `boites_entamees` par lot et magasin) avant d'en ouvrir une nouvelle.
- **Étiquette de sachet** pour une vente au détail : nom, dosage, lot, péremption, posologie, QR code.

### 5.5 Réception et inventaire

- Réception saisie dans le conditionnement d'achat (« 3 cartons ») ; le lot reçoit 6 000 cp.
- Inventaire : on compte les **boîtes pleines** puis les **unités en vrac** par lot ; le total est recalculé.

### 5.6 Cas particuliers

| Cas | Traitement |
|---|---|
| Même molécule en boîte de 20 et de 100 | Deux **produits** distincts (EAN différents), reliés par un **groupe d'équivalence** |
| Grand conditionnement hospitalier (pot de 1 000 cp) | Seul le détail est vendable ; étiquette de sachet systématique |
| Ampoules, sachets, seringues, gants (vente par paire) | Unité de base adaptée |
| Flacon, tube, poche | Non divisible |
| Assurance et vente au détail | Taux appliqué au prix de l'unité vendue ; option par convention « **détail refusé** » |
| Retour d'unités vendues au détail | Refusé par défaut (paramètre) |
| Le laboratoire change la boîte (30 → 28) | Nouveau conditionnement ; les lots anciens gardent leurs quantités en unités de base |

---

## 6. Volet comptoir (création des tickets)

### 6.1 Interface tablette et smartphone

- [V1] **Application web installable** (PWA) sur l'écran d'accueil, pensée tactile d'abord : boutons ≥ 48 px, utilisable d'une main, mode sombre, polices lisibles.
- [V1] **Scan par la caméra** (et douchette Bluetooth ou USB) ; recherche instantanée par nom commercial, **DCI**, code.
- [V1] Panier : quantité (+/−), conditionnement (§5.4), stock disponible affiché, prix, total, part de l'assurance.
- [V1] Lot choisi en FEFO, modifiable par une personne habilitée.
- [V1] **Valider** : le numéro d'appel s'affiche en très grand ; impression optionnelle d'un **bon** (numéro, montant, QR code).
- [V1] **Suspendre / reprendre** un ticket (le client va chercher son ordonnance).
- [V1] Connexion rapide par **code PIN** personnel, verrouillage automatique après inactivité.

### 6.2 Aide à la vente

- [V1] **Génériques proposés** : même DCI, même dosage, même forme, avec prix et stock.
- [V1] Avertissement « **sur ordonnance** » et liste réglementaire ; **photo de l'ordonnance** avec la tablette ; saisie du prescripteur.
- [V1] **Remise** plafonnée selon le rôle ; au-delà, validation par code PIN du pharmacien.
- [V2] **Produit manquant → commande client** : produit, quantité, téléphone ; notification SMS / WhatsApp à la réception.
- [V2] Étiquette de **posologie**.
- [V3] Alertes d'interactions (uniquement avec une base médicale sous licence).

---

## 7. Volet caisse

### 7.1 File d'attente et appel

- [V1] Liste en temps réel des tickets `en_attente`, du plus ancien au plus récent ; recherche par numéro, **scan du QR** du bon.
- [V1] Bouton **Appeler** : numéro affiché sur l'**écran d'appel** (téléviseur de la salle, page web plein écran) et **annonce vocale** en français (« Ticket A quarante-sept, caisse deux »), générée localement.
- [V1] **Rappeler**, **passer** (client absent → retour en attente), **abandonner** après le délai paramétré.

### 7.2 Encaissement

- [V1] Modes : **espèces** (calcul de la monnaie à rendre), **Orange Money**, **Moov Money** (référence de transaction obligatoire), **carte**, **crédit client** (V2), et **paiement mixte**.
- [V1] Affichage séparé **part client / part organisme(s)** : le client ne paie que sa part.
- [V1] **Ticket de caisse** imprimé (thermique 80 mm) ou envoyé par SMS / WhatsApp ; numéro comptable, détail des lignes, part assurance, mode de paiement.
- [V1] **Remise des produits** confirmée par la caissière (`paye → remis`).
- [V2] Confirmation automatique des paiements Mobile Money par l'API de l'opérateur.
- [V2] **Monnaie due** au client quand la caisse manque de petites coupures : avoir tracé, consommable au prochain passage.

### 7.3 Session de caisse

- [V1] **Ouverture** : fond de caisse déclaré.
- [V1] **Mouvements** : sorties justifiées (achat de dépannage, avance), entrées (approvisionnement en monnaie).
- [V1] **Clôture** : comptage par coupure, totaux par mode de paiement, **écart** calculé, **ticket Z** numéroté et définitif ; dépôt au coffre.
- [V1] À la clôture, les tickets encore `en_attente` du jour sont proposés à l'abandon.
- [V1] Plusieurs caisses en parallèle, une session par caissière et par caisse.
- [V2] Rapport des écarts par caissière ; alertes sur annulations ou retours répétés.

---

## 8. Facture nominative

- [V1] À partir d'un ticket **payé**, on établit une **facture à un nom** : nom, prénom et, si besoin, téléphone, entreprise, IFU (identifiant fiscal), adresse.
- [V1] **Aucun montant ne change** : la facture reprend le ticket ; elle a sa **propre numérotation continue** (`F2026-000045`) et mentionne le numéro du ticket.
- [V1] Un ticket ne peut donner qu'**une** facture ; toute réimpression porte « **DUPLICATA** ».
- [V1] Facture A4 avec l'en-tête officiel de l'officine (repris de MyStock), en PDF ou envoyée par WhatsApp / e-mail.
- [V1] Les coordonnées saisies sont **figées sur la facture** (copie), même si la fiche client change plus tard.
- [V2] **Facture regroupée** : plusieurs tickets d'une entreprise ou d'une clinique sur une période.
- [V2] Fiches clients pour ne pas ressaisir les habitués.
- [V2] **Devis / proforma** transformable en ticket.

---

## 9. Assurances et tiers payeurs

### 9.1 Référentiel

- [V1] **Organismes** : État (agents publics, dispositifs de prise en charge publics selon les conventions de l'officine), mutuelles, assurances privées, CNSS, entreprises conventionnées ; à terme l'assurance maladie universelle.
- [V1] **Convention** par organisme :
  - taux de prise en charge par défaut ;
  - règles par famille ou par produit (taux spécifique, **exclusion**) ;
  - plafond par ordonnance et/ou par bénéficiaire et par an ;
  - justificatifs exigés (ordonnance, carte, bon de prise en charge) ;
  - option « vente au détail refusée » ;
  - période de facturation (bordereau mensuel, quinzaine…) ;
  - dates de validité de la convention.
- [V2] Prix de remboursement imposé par la convention (la différence est à la charge du client).

### 9.2 Au comptoir

- [V1] Saisie ou **photo de la carte** : organisme, numéro, titulaire, **bénéficiaire** (titulaire, conjoint, enfant), validité.
- [V1] **Bon de prise en charge** (État, employeur) : numéro, photo, montant autorisé, date limite.
- [V1] Calcul **ligne par ligne** de la part organisme et de la part client (§13, RG-ASS).
- [V1] Blocage si un justificatif obligatoire manque.
- [V2] Contrôle des plafonds déjà consommés par le bénéficiaire ; alerte carte expirée.
- [V3] Vérification des droits en ligne si un organisme ouvre un service.

### 9.3 Recouvrement

- [V1] Chaque ticket assuré **payé** crée une **créance** sur l'organisme.
- [V1] **Bordereau** périodique par organisme : liste des tickets, montants, bénéficiaires, copies des ordonnances et bons ; export PDF et Excel.
- [V1] Suivi : `brouillon → envoye → accepte → paye` (partiellement ou totalement), ou **rejet** par ligne.
- [V1] Rejet : motif, correction et renvoi, **réclamation au client** (§10.C) ou passage en perte (validation titulaire).
- [V2] Rapprochement des virements reçus, **balance âgée** par organisme, délai moyen de paiement.

---

## 10. Remboursements et retours

Trois situations distinctes.

### A. L'officine rembourse le client (retour de produit)

- [V1] Retour **uniquement à partir du ticket d'origine**, dans un délai paramétré, avec un motif.
- [V1] **Validation par un pharmacien** (code PIN), tracée.
- [V1] Le produit retourne en stock **seulement s'il est intact et vendable** ; sinon quarantaine. **Chaîne du froid : jamais remis en stock.** Unités vendues au détail : refusées par défaut.
- [V1] Remboursement par le **mode de paiement d'origine** (espèces, Mobile Money) ou par **avoir** client.
- [V1] Un retour sur ticket assuré **annule la part organisme correspondante** dans la créance.
- [V1] Si le ticket avait une facture nominative, un **avoir** nominatif est émis.

### B. Le client se fait rembourser par son assurance

- [V1] Officine non conventionnée avec l'organisme du client : le client paie tout ; on lui remet une **facture nominative acquittée détaillée** (produits, DCI, lot, prescripteur) qu'il présente à son assurance.
- [V2] Modèles de document propres à certains organismes.

### C. L'organisme rembourse l'officine

- C'est le recouvrement (§9.3).
- [V2] En cas de rejet : **ticket de régularisation** pour réclamer la part rejetée au client.

---

## 11. Fonctions transverses

### 11.1 Clients

- [V2] Fiche facultative : téléphone, assurance, historique.
- [V2] **Clients en compte** (paiement en fin de mois) : plafond, relevé, relances.
- [V3] Fidélité à points ; rappel de renouvellement des traitements chroniques.

### 11.2 Pilotage

- [V1] Tableau de bord du jour : chiffre d'affaires, nombre de tickets, panier moyen, encaissements par mode, tickets abandonnés, temps d'attente moyen.
- [V1] **Marge** par produit, famille, période ; **valeur du stock**.
- [V2] Créances par organisme, ruptures, valeur des produits proches de la péremption, rapport mensuel PDF.
- [V2] **Exports comptables** : journaux ventes, achats, caisse, avec plan de comptes paramétrable.

### 11.3 Sécurité et contrôle

- [V1] Rôles et permissions par module et par action (repris de MyStock).
- [V1] Code PIN par utilisateur sur les appareils partagés.
- [V1] **Journal** de toute action sensible : annulation, remise, retour, changement de prix, changement de lot, ouverture du tiroir, abandon de ticket.
- [V1] Les documents validés ne se modifient jamais : toute correction passe par un **contre-document** (avoir, retour, inventaire).
- [V1] Sauvegarde automatique chaque nuit : copie locale + copie chiffrée hors de l'officine.

### 11.4 Organisation de l'officine

- [V2] **Mode garde** (nuit, dimanche) : majoration éventuelle, caisse simplifiée à une personne.
- [V2] Notifications SMS / WhatsApp (commande client arrivée).
- [V3] **Multi-officines** : stocks consolidés, transferts entre officines (moteur MyStock).

### 11.5 Fiscalité

- [V1] **TVA par famille** (médicaments en principe exonérés, parapharmacie taxée) — à valider avec un comptable.
- [V1] **Point d'accroche de certification fiscale** : interface `CertificationFiscale` appelée à chaque ticket payé et à chaque facture / avoir, avec un pilote « aucun » par défaut. Elle permettra de brancher la **facture électronique certifiée** de la DGI si l'officine y est soumise (à vérifier avant la V1).

### 11.6 Données médicales

- Les alertes d'interactions, contre-indications et posologies maximales ne seront ajoutées qu'avec une **base de données médicamenteuse reconnue et sous licence**. Aucune règle médicale n'est inventée dans le logiciel.

---

## 12. Modèle de données

Conventions : clés primaires `id` bigint ; **montants en `bigint` FCFA** ; coûts unitaires en `decimal(14,4)` ; quantités de stock en `integer` **unités de base** ; horodatages `created_at` / `updated_at` ; suppression logique sur les référentiels uniquement.

### 12.1 Référentiel produits

**`produits`**

| Colonne | Type | Note |
|---|---|---|
| `code` | string unique | référence interne |
| `nom_commercial` | string | |
| `dci_id` | FK `dci` | molécule(s) |
| `dosage` | string | « 500 mg » |
| `forme_id` | FK `formes` | comprimé, sirop, injectable… |
| `laboratoire_id` | FK `laboratoires` | |
| `famille_id` | FK `familles` | médicament, parapharmacie, consommable, matériel ; porte le taux de TVA et la marge par défaut |
| `liste` | enum | `aucune`, `I`, `II`, `stupefiant` |
| `sur_ordonnance` | bool | |
| `generique` | bool | |
| `groupe_equivalence_id` | FK nullable | produits substituables |
| `unite_base` | string | « comprimé », « flacon »… |
| `chaine_froid` | bool | |
| `prix_impose` | bool | |
| `seuil_alerte`, `stock_mini`, `couverture_jours` | integer | en unités de base / jours |
| `actif` | bool | |

**`conditionnements`** : `produit_id`, `libelle`, `quantite_base` (int ≥ 1), `parent_id`, `code_barres` (unique, nullable), `code_genere` (bool), `achetable`, `vendable`, `defaut_achat`, `defaut_vente`, `ordre`.
Contraintes : exactement un conditionnement de `quantite_base = 1` par produit ; au plus un `defaut_achat` et un `defaut_vente`.

**`prix_vente`** : `conditionnement_id`, `prix` (bigint), `mode` (`calcule` / `impose`), `date_effet`, `auteur_id`. Le prix courant est le dernier à la date du jour.

Tables simples : `dci`, `formes`, `laboratoires`, `familles` (`taux_tva`, `marge_defaut`, `majoration_detail`), `groupes_equivalence`.

### 12.2 Stock

**`magasins`** (repris) : type `reserve`, `rayon`, `quarantaine`.

**`lots`** (repris, étendu) : `produit_id`, `numero`, `date_peremption`, `cout_unitaire` decimal(14,4), `fournisseur_id`, `reception_id`, `bloque` (bool, rappel), `motif_blocage`.

**`stocks`** (repris, étendu) : `lot_id`, `magasin_id`, `quantite` (int, unités de base), `quantite_reservee` (int), `boites_entamees` (int).
Disponible vendable = `quantite − quantite_reservee`, lot non bloqué, non périmé, magasin de type `rayon`.

**Documents et mouvements** (repris de MyStock) : `documents` (types étendus : `reception`, `retour_fournisseur`, `vente`, `retour_client`, `transfert`, `inventaire`, `destruction`), `ligne_mouvements`, `ligne_lots`. Chaque ligne stocke **`conditionnement_id`, `quantite_saisie`, `quantite_base`**.

### 12.3 Achats

- **`fournisseurs`** : raison sociale, contacts, délai de livraison, conditions de paiement.
- **`commandes`** / **`lignes_commande`** : statut `brouillon → envoyee → partielle → soldee / annulee`.
- **`receptions`** : document de stock de type `reception` + `facture_fournisseur_numero`, `facture_fournisseur_montant`, `commande_id`.

### 12.4 Ventes

**`postes`** : appareils et rôles (`comptoir`, `caisse`, `ecran_appel`), lettre de numérotation, imprimante associée.

**`tickets`**

| Colonne | Note |
|---|---|
| `numero_appel` | `A-047`, unique par jour |
| `numero` | séquence comptable `T2026-000123`, attribuée au paiement |
| `poste_id`, `vendeur_id` | |
| `statut` | voir §3.2 |
| `date_validation`, `date_appel`, `date_paiement`, `date_remise`, `expire_le` | |
| `total`, `remise_totale`, `part_organismes`, `part_client` | bigint |
| `prise_en_charge_id` | FK nullable (§12.6) |
| `ordonnance_id` | FK nullable (photo, prescripteur) |
| `caisse_session_id` | renseigné au paiement |
| `document_id` | document de stock de sortie, créé au paiement |

**`lignes_ticket`** : `ticket_id`, `produit_id`, `conditionnement_id`, `quantite_saisie`, `quantite_base`, `prix_unitaire` (prix du conditionnement figé), `remise`, `montant`, `taux_organisme`, `part_organisme`, `exclu_assurance` (bool).

**`ligne_ticket_lots`** : `ligne_ticket_id`, `lot_id`, `magasin_id`, `quantite_base`, `cout_unitaire` (figé, pour la marge). Créée à la réservation.

### 12.5 Caisse

- **`caisses`** : caisse physique, poste associé.
- **`sessions_caisse`** : `caisse_id`, `caissier_id`, `ouverte_le`, `fond_initial`, `fermee_le`, `comptage` (json par coupure), `total_theorique`, `total_compte`, `ecart`, `numero_z`.
- **`encaissements`** : `ticket_id`, `session_id`, `montant`, `date`.
- **`paiements`** : `encaissement_id`, `mode` (`especes`, `orange_money`, `moov_money`, `carte`, `credit_client`, `avoir`), `montant`, `reference`, `rendu`.
- **`mouvements_caisse`** : `session_id`, `sens` (`entree` / `sortie`), `montant`, `motif`, `auteur_id`, `validateur_id`.

### 12.6 Assurances

- **`organismes`** : nom, type (`etat`, `mutuelle`, `assurance`, `cnss`, `entreprise`), contacts, délai de paiement attendu.
- **`conventions`** : `organisme_id`, `taux_defaut`, `plafond_ordonnance`, `plafond_annuel_beneficiaire`, `detail_refuse`, `justificatifs` (json), `periodicite_bordereau`, `debut`, `fin`.
- **`convention_regles`** : `convention_id`, `famille_id` ou `produit_id`, `taux` ou `exclu`.
- **`beneficiaires`** : `organisme_id`, `numero_carte`, `nom`, `prenom`, `lien` (titulaire, conjoint, enfant), `titulaire_id`, `valide_jusqua`, `photo_carte`.
- **`prises_en_charge`** : `ticket_id`, `convention_id`, `beneficiaire_id`, `numero_bon`, `montant_autorise`, `photo_bon`.

### 12.7 Facturation et créances

- **`factures`** : `numero` (`F2026-…` ou `AV2026-…`), `type` (`nominative`, `regroupee`, `avoir`), `client_id` nullable, **copie figée** `client_nom`, `client_prenom`, `client_telephone`, `client_ifu`, `client_adresse`, `total`, `facture_origine_id` (pour un avoir), `duplicatas` (compteur).
- **`facture_tickets`** : pivot facture ↔ tickets.
- **`creances`** : `debiteur_type` (`organisme` / `client`), `debiteur_id`, `ticket_id`, `montant`, `solde`, `statut`.
- **`bordereaux`** / **`bordereau_lignes`** : `organisme_id`, période, statut, totaux ; une ligne par créance, avec `statut_ligne` et `motif_rejet`.
- **`reglements`** : `debiteur`, `montant`, `date`, `reference` (virement, chèque), lettrage sur les créances.
- **`retours`** / **`lignes_retour`** : `ticket_id`, `motif`, `validateur_id`, `destination` (`stock` / `quarantaine`), `mode_remboursement`, `montant`.
- **`avoirs_clients`** : avoir monnaie ou avoir de retour, `solde`, `utilise_sur_ticket_id`.

### 12.8 Séquences

**`sequences`** : `code` (`ticket`, `facture`, `avoir`, `z`, `appel:A:2026-11-13`…), `valeur`. Incrément sous verrou (`SELECT … FOR UPDATE`) dans la même transaction que le document : **aucun trou, aucun doublon**.

---

## 13. Règles de gestion

### Stock

- **RG-STK-01** — Toutes les quantités de stock sont des entiers en unités de base.
- **RG-STK-02** — `quantite_base = quantite_saisie × conditionnement.quantite_base`.
- **RG-STK-03** — Allocation FEFO : lots non bloqués, non périmés, du rayon, triés par date de péremption croissante ; à péremption égale, lot ayant une boîte entamée d'abord.
- **RG-STK-04** — Un lot périmé ou bloqué n'est jamais alloué à une vente.
- **RG-STK-05** — Les écarts de stock ne se corrigent que par un inventaire validé.
- **RG-STK-06** — CUMP après réception : `(stock × cump + qté_reçue × coût_unitaire) ÷ (stock + qté_reçue)`.
- **RG-STK-07** — Un produit sans prix de vente courant pour un conditionnement vendable ne peut pas être vendu dans ce conditionnement.

### Tickets et réservation

- **RG-TKT-01** — La validation d'un ticket alloue les lots (RG-STK-03) et incrémente `quantite_reservee`, sous verrou, dans une seule transaction ; si le stock disponible est insuffisant, la validation échoue avec la quantité réellement disponible.
- **RG-TKT-02** — Le paiement crée le document de sortie, décrémente `quantite` et `quantite_reservee` des mêmes lots et attribue le numéro comptable.
- **RG-TKT-03** — L'abandon libère la réservation. Un ticket `en_attente` est abandonné automatiquement après `delai_abandon_ticket` minutes (paramètre) ou à la clôture de la dernière session du jour.
- **RG-TKT-04** — Une fois `en_attente`, les lignes et prix d'un ticket ne sont plus modifiables ; la vendeuse peut l'annuler tant qu'il n'est pas appelé, et le recréer.
- **RG-TKT-05** — La caissière ne peut ni ajouter, ni retirer, ni modifier une ligne.
- **RG-TKT-06** — Remise au-delà du plafond du rôle : validation par code PIN d'un pharmacien, tracée.
- **RG-TKT-07** — Changement de lot manuel : réservé aux rôles habilités, tracé, et jamais vers un lot périmé ou bloqué.

### Prix

- **RG-PRX-01** — Montants en FCFA entiers ; prix calculés arrondis au pas `arrondi_prix` (paramètre : 5, 25, 50).
- **RG-PRX-02** — Prix calculé d'un conditionnement = `prix_reference ÷ q_ref × q_cond × (1 + majoration_detail)` si `q_cond < q_ref`, puis arrondi ; `prix_reference` = prix du conditionnement de vente par défaut.
- **RG-PRX-03** — Un prix imposé n'est jamais recalculé.
- **RG-PRX-04** — La ligne de ticket fige le prix unitaire et le coût du lot au moment de la validation.

### Assurance

- **RG-ASS-01** — Taux d'une ligne : règle produit, sinon règle famille, sinon taux par défaut de la convention ; une ligne exclue a un taux de 0.
- **RG-ASS-02** — Ligne vendue au détail sur une convention « détail refusé » : taux 0 (le client paie tout), avec avertissement.
- **RG-ASS-03** — `part_organisme_ligne = arrondi_inférieur(montant_ligne × taux)` ; `part_client = total − Σ part_organisme`.
- **RG-ASS-04** — Si `Σ part_organisme` dépasse le plafond (ordonnance, annuel restant, ou montant du bon), elle est ramenée au plafond, répartie au prorata des lignes ; le reste passe au client.
- **RG-ASS-05** — Convention hors période de validité ou carte expirée : prise en charge refusée.
- **RG-ASS-06** — Justificatif obligatoire absent : validation du ticket impossible avec l'assurance.

### Caisse

- **RG-CAI-01** — Aucun encaissement sans session ouverte sur la caisse.
- **RG-CAI-02** — Paiement Mobile Money sans référence refusé.
- **RG-CAI-03** — Somme des paiements = part client du ticket (espèces : le rendu est calculé et enregistré).
- **RG-CAI-04** — Clôture : `ecart = total_compte − (fond_initial + espèces encaissées + entrées − sorties − espèces remboursées)` ; un écart non nul exige un commentaire.
- **RG-CAI-05** — Un ticket Z n'est jamais modifiable ; une erreur se corrige dans la session suivante par un mouvement justifié.

### Facturation et retours

- **RG-FAC-01** — Une facture nominative ne peut être établie que sur un ticket payé, une seule fois ; ses montants sont ceux du ticket.
- **RG-FAC-02** — Factures, avoirs et tickets ont chacun une numérotation continue par exercice (§12.8).
- **RG-FAC-03** — Une facture ne se modifie ni ne se supprime ; on la corrige par un avoir.
- **RG-RET-01** — Un retour se fait sur un ticket d'origine payé, dans le délai `delai_retour_jours`, validé par un pharmacien.
- **RG-RET-02** — Quantité retournée ≤ quantité vendue − quantités déjà retournées, ligne par ligne.
- **RG-RET-03** — Produit sous chaîne du froid : destination quarantaine obligatoire.
- **RG-RET-04** — Le retour réduit la créance de l'organisme au prorata de la part organisme de la ligne.

### Paramètres principaux

`mode_circuit` (`separe` / `comptoir_unique`), `delai_abandon_ticket`, `arrondi_prix`, `majoration_detail` (par famille), `delai_retour_jours`, `retour_detail_autorise`, `alertes_peremption_jours`, `plafond_remise_par_role`, `annonce_vocale` (activée, voix, volume), `impression_bon_comptoir`.

---

## 14. Écrans de la V1

Toutes les vues « terrain » sont conçues pour **tablette 10″ en portrait et smartphone** ; le back-office reste utilisable sur PC.

| # | Écran | Appareil | Contenu essentiel |
|---|---|---|---|
| E01 | Connexion par code PIN | tablette | grille d'avatars des utilisateurs du poste, pavé numérique |
| E02 | **Comptoir — panier** | tablette, smartphone | barre de recherche + bouton caméra, résultats avec stock et prix, panier avec boutons de conditionnement, total, part assurance, boutons Suspendre / Valider |
| E03 | Comptoir — assurance | tablette | choix de l'organisme, photo ou saisie de la carte, bénéficiaire, bon de prise en charge, photo de l'ordonnance |
| E04 | Comptoir — ticket validé | tablette | numéro d'appel en très grand, montant client, impression du bon |
| E05 | **Caisse — file d'attente** | PC tactile ou tablette | tickets en attente (numéro, ancienneté, montant), Appeler / Rappeler / Passer |
| E06 | **Caisse — encaissement** | PC tactile ou tablette | détail du ticket (lecture seule), part client, pavé des modes de paiement, monnaie à rendre, Encaisser, Remettre |
| E07 | Caisse — facture nominative | PC ou tablette | recherche du ticket, saisie nom / prénom / IFU, aperçu PDF, Imprimer / Envoyer |
| E08 | Caisse — session | PC | ouverture, mouvements, clôture avec comptage par coupure, ticket Z |
| E09 | Écran d'appel | téléviseur | dernier numéro appelé en très grand, caisse, 5 derniers appels, annonce vocale |
| E10 | Retour client | tablette ou PC | ticket d'origine, lignes retournables, motif, destination, mode de remboursement, validation PIN |
| E11 | Produits et conditionnements | PC | fiche produit, tableau des conditionnements, prix (calculé / imposé), codes-barres, génération de code |
| E12 | Réception | tablette ou PC | fournisseur, scan des produits, lot, péremption, quantité dans le conditionnement d'achat, coût ; impression des étiquettes |
| E13 | Étiquettes | PC | choix des produits / lots, format (thermique, A4), aperçu |
| E14 | Commandes | PC | proposition automatique, bon de commande |
| E15 | Inventaire | tablette | scan, comptage boîtes pleines + unités en vrac par lot |
| E16 | Stock et péremptions | PC, tablette | stock décomposé, alertes, quarantaine, destruction |
| E17 | Organismes et conventions | PC | règles, plafonds, justificatifs |
| E18 | Bordereaux et créances | PC | génération, envoi, saisie des paiements et rejets |
| E19 | Tableau de bord titulaire | PC, smartphone | chiffre du jour, marge, valeur du stock, créances, alertes |
| E20 | Paramètres, utilisateurs, postes | PC | repris de MyStock et étendus |

---

## 15. Architecture technique

### 15.1 Socle

- **Laravel 12**, PHP 8.2+, **PostgreSQL**, modules **nwidart/laravel-modules** (comme MyStock).
- Paquets repris : `spatie/laravel-permission`, `spatie/laravel-activitylog`, `barryvdh/laravel-dompdf`, `phpoffice/phpspreadsheet`, `tightenco/ziggy`.
- **Back-office** : Blade comme MyStock.
- **Écrans terrain** (comptoir, caisse, écran d'appel) : interface réactive en **Livewire 3** ou **Alpine.js + API JSON** (choix à faire en P0), PWA installable, utilisable au clavier et au tactile.
- **Temps réel** (file d'attente, écran d'appel) : **Laravel Reverb** (WebSockets) sur le serveur local ; rafraîchissement périodique en secours.
- Codes-barres et QR : génération côté serveur (SVG / PNG) ; lecture par la caméra côté navigateur (API `BarcodeDetector` avec bibliothèque de secours).
- Impression thermique : commandes **ESC/POS** via un agent d'impression local (type QZ Tray) pour imprimer sans boîte de dialogue.
- Annonce vocale : synthèse locale (pas de dépendance à internet).

### 15.2 Modules

| Module | Contenu |
|---|---|
| `Core` | utilisateurs, rôles, permissions, modules, paramètres, journal, PDF, identité (repris) |
| `Referentiel` | produits, conditionnements, prix, DCI, formes, laboratoires, familles, magasins, fournisseurs, organismes (étendu) |
| `Stock` | lots, stocks, documents, mouvements, inventaires, transferts, quarantaine (repris) |
| `Achats` | commandes, réceptions, retours fournisseurs, propositions de commande |
| `Ventes` | tickets, réservation, file d'attente, ordonnances, génériques |
| `Caisse` | sessions, encaissements, paiements, mouvements, ticket Z, écran d'appel |
| `Facturation` | factures, avoirs, assurances, créances, bordereaux, règlements, retours |
| `Etats` | tableaux de bord, analyses, exports (repris et étendu) |

Les modules communiquent par **événements** (`TicketValide`, `TicketPaye`, `TicketAbandonne`, `ReceptionValidee`, `SessionCloturee`, `RetourValide`) : le module Caisse ne manipule pas le stock directement, il déclenche `TicketPaye` que le module Stock traite.

### 15.3 Intégrité

- Chaque opération (validation, paiement, retour, clôture) dans **une transaction** ; verrouillage des lignes `stocks` et `sequences` concernées.
- Documents validés en **ajout seul** ; corrections par contre-documents.
- Tests automatisés obligatoires sur : allocation FEFO avec conditionnements, réservation concurrente (deux tickets sur la dernière boîte), calcul d'assurance avec plafond, séquences sans trou, clôture de caisse, retours.

### 15.4 Déploiement en officine

- **Serveur local** : mini-PC (SSD, 8 Go de RAM minimum) sous Linux, sur **onduleur**, avec PostgreSQL et l'application.
- Réseau local Wi-Fi pour les tablettes ; poste caisse en filaire si possible.
- Sauvegarde : dump PostgreSQL chiffré chaque nuit, copie locale (disque externe) + envoi hors site dès que la connexion le permet ; restauration testée.
- Mise à jour de l'application par un script, avec sauvegarde automatique préalable.
- Plus tard (multi-officines) : un serveur central avec synchronisation.

### 15.5 Matériel de référence (V1)

| Poste | Matériel |
|---|---|
| Comptoir | tablette Android 10″ ou smartphone, douchette Bluetooth optionnelle |
| Caisse | PC ou tablette, imprimante thermique 80 mm ESC/POS, tiroir-caisse relié à l'imprimante, douchette |
| Réserve | imprimante d'étiquettes thermique, douchette |
| Salle d'attente | téléviseur avec navigateur (ou petit boîtier) pour l'écran d'appel, haut-parleur |
| Serveur | mini-PC + onduleur + disque de sauvegarde |

---

## 16. Réutilisation de MyStock

Stratégie : **nouveau dépôt créé à partir de MyStock**, nettoyé, puis évolution indépendante ; extraction d'un socle commun (`ikka/laravel-core`) quand les deux produits auront mûri.

| Élément MyStock | Décision |
|---|---|
| `Modules/Core` : `User`, `Role`, `Module`, `Activity`, `ModuleService`, `PermissionService`, `PdfService` | **Repris** ; ajout du code PIN et des postes |
| Écran de démarrage, identité, pages d'erreur, `EntetesSecurite`, en-têtes PDF (`EnteteDocuments`) | **Repris**, identité IkkaPharma |
| `Referentiel` : `Parametres`, `Magasin`, `PerimetreMagasins` | **Repris** |
| `Referentiel` : `Article`, `Categorie`, `Unite` | **Transformés** en `Produit`, `Famille`, `Conditionnement` |
| `Referentiel` : `Direction`, `Service`, `ResponsableMagasin` | **Retirés** (propres à l'hôpital) |
| `Stock` : `Document`, `LigneMouvement`, `Lot`, `LigneLot`, `Stock`, `Inventaire`, `LigneInventaire` | **Repris** ; quantités en unités de base, coût sur le lot, réservation, boîtes entamées |
| `Stock` : `MouvementService`, `LotService`, `TransfertService`, `InventaireService` | **Repris et étendus** (conditionnements, réservation) |
| `Etats` : `AlerteStockService`, `AnalysesService`, `StatistiquesService`, `ExportService`, `EtatsService` | **Repris** ; valorisation en FCFA |
| `DemoSeeder` | **Réécrit** pour une officine fictive |
| Tests existants | Repris pour le socle ; nouveaux tests métier |

Travaux de démarrage (P0) :

1. Créer le dépôt `IkkaPharma` depuis le dernier commit de MyStock, avec un historique neuf.
2. Renommer l'application, l'identité, la configuration ; retirer les mentions du CHU-YO.
3. Supprimer `Direction`, `Service`, `ResponsableMagasin` et leurs écrans.
4. Migrer `Article` → `Produit` et `Unite` → `Conditionnement`, convertir les quantités en unités de base.
5. Faire passer les tests du socle, puis écrire les tests de conditionnement et de réservation.

---

## 17. Feuille de route

| Phase | Contenu | Durée indicative* | Résultat |
|---|---|---|---|
| **P0 — Socle** | Dépôt, nettoyage, produits, conditionnements, prix, codes PIN, postes | 2 semaines | Base saine |
| **P1 — Stock valorisé et achats** | Fournisseurs, commandes, réceptions (lots, coûts, CUMP), codes-barres, QR, étiquettes, valeur du stock, alertes | 4 à 5 semaines | Gestion de stock utilisable seule |
| **P2 — Comptoir et caisse** | Tickets sur tablette, réservation, file d'attente, écran d'appel et voix, encaissement mixte, sessions et Z, ticket thermique, facture nominative, retours | 5 à 6 semaines | **V1 utilisable en officine** |
| **P3 — Assurances et facturation** | Organismes, conventions, calcul des parts, bordereaux, créances, rejets, avoirs, point d'accroche fiscal | 4 semaines | Recouvrement maîtrisé |
| **P4 — Pilotage** | Tableau de bord titulaire, marges, exports comptables, ordonnancier, stupéfiants | 3 semaines | Aide à la décision |
| **P5 — Extensions** | API Mobile Money, commandes clients et notifications, clients en compte, mode garde, multi-officines, fidélité | continu | Croissance |

\* Un développeur à plein temps assisté ; à réévaluer à la fin de P0.

> Remarque : si la pharmacie pilote travaille déjà avec des assurances au quotidien, une **version simplifiée de P3** (organisme + taux, sans plafonds ni bordereaux) doit entrer dans P2, sinon la V1 ne pourra pas servir les clients assurés.

---

## 18. Risques

| Risque | Parade |
|---|---|
| Coupures d'électricité ou d'internet | Serveur local + onduleur ; aucune dépendance internet pour vendre |
| Réglementation mal connue (facture certifiée, ordonnancier, stupéfiants, prix encadrés) | Validation par un pharmacien titulaire et un comptable **avant P2** ; point d'accroche fiscal dès la V1 |
| Lenteur au comptoir → abandon du logiciel | Objectif mesuré : **ticket simple en moins de 20 s** ; scan, recherche instantanée, peu d'écrans |
| Reprise des données (catalogue de plusieurs milliers de produits, stock initial) | Import Excel, inventaire de démarrage guidé |
| Fraude en caisse | Séparation comptoir / caisse, Z définitif, journal, validation PIN des opérations sensibles |
| Erreurs de conditionnement (prix ou quantités faux) | Unité de base unique, contrôles à la création des conditionnements, tests automatisés |
| Dérive du périmètre | Comptabilité générale et paie exclues ; exports seulement |

---

## 19. Questions ouvertes

À trancher avec la pharmacie pilote avant ou pendant P0 :

1. Combien de postes de comptoir et de caisses fonctionnent en même temps aux heures de pointe ?
2. Liste exacte des organismes et conventions, avec un exemple de bordereau et de carte pour chacun.
3. Délai d'abandon d'un ticket non payé (30 minutes ? fin de journée ?).
4. Les retours sont-ils acceptés, sous quel délai, et pour quels produits ?
5. L'officine est-elle concernée par la facture électronique certifiée de la DGI ?
6. Quels prix sont imposés (génériques CAMEG, autres) et quelle majoration pour la vente au détail ?
7. Arrondi des prix de vente : aux 5, 25 ou 50 FCFA ?
8. Le ticket remis au client au comptoir doit-il être imprimé, ou le numéro oral suffit-il ?
9. Quel matériel l'officine possède-t-elle déjà ?
10. Choix technique de l'interface terrain : Livewire 3 ou Alpine.js + API ?

---

## 20. Glossaire

| Terme | Définition |
|---|---|
| **Unité de base** | Plus petite quantité vendable d'un produit ; unité de compte du stock |
| **Conditionnement** | Présentation d'un produit (carton, boîte, plaquette, comprimé) exprimée en unités de base |
| **DCI** | Dénomination commune internationale : nom de la molécule |
| **FEFO** | *First Expired, First Out* : on sort d'abord le lot qui périme le plus tôt |
| **CUMP** | Coût unitaire moyen pondéré |
| **Ticket** | Vente préparée au comptoir, payée à la caisse ; vaut facture simplifiée |
| **Numéro d'appel** | Numéro court du jour (`A-047`) annoncé par la caissière |
| **Facture nominative** | Facture établie au nom du client à partir d'un ticket payé |
| **Tiers payeur / organisme** | Payeur d'une partie de la vente (État, mutuelle, assurance…) |
| **Part client / part organisme** | Répartition du montant d'un ticket assuré |
| **Bordereau** | Relevé périodique des ventes à facturer à un organisme |
| **Avoir** | Document qui annule tout ou partie d'une facture ou d'un ticket |
| **Ticket Z** | Récapitulatif définitif de clôture d'une session de caisse |
| **Quarantaine** | Magasin des produits invendables (périmés, rappelés, retournés) |
| **IFU** | Identifiant financier unique (numéro fiscal au Burkina Faso) |
