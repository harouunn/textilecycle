# Module 1 : Dépôt & catalogue de vêtements

Responsable : Alaa Tahri (branche `alaa-tahri`).

## 1. La tâche en une phrase

Un particulier **dépose** un vêtement qu'il ne porte plus. Une **IA de vision locale (Ollama)** regarde la photo et remplit la fiche. Un **administrateur valide** le dépôt. Le vêtement apparaît alors dans le **catalogue**, où un autre client peut le **réserver** puis venir le **retirer**. Ce qui ne trouve pas preneur part vers les autres modules : réparation, upcycling ou don.

## 2. Les tables

| Table | Rôle | Champs principaux |
|---|---|---|
| `categories` (entité principale) | Rayons du catalogue | nom, slug, description, icone, couleur, ordre, active |
| `vetements` | Un vêtement déposé | categorie_id, user_id (déposant), titre, description, taille, genre, matiere, etat, photo, photo_empreinte, statut, moderation, motif_refus, date_depot |
| `reservations` | Un client réserve un vêtement | code (ex. `RES-…`), vetement_id, user_id (client), date_retrait, creneau, telephone, message, statut, motif_annulation, confirmee_le, retiree_le, annulee_le, rappel_envoye_le |

Relations :

- `Categorie` **hasMany** `Vetement`, et `Vetement` **belongsTo** `Categorie`.
- `User` **hasMany** `Vetement` (ses dépôts) et **hasMany** `Reservation` (ses réservations).
- `Vetement` **hasMany** `Reservation`, et `Reservation` **belongsTo** `Vetement` et `User`.
- Liens avec les autres modules : `Vetement` **hasMany** `DemandeReparation` (Ateliers), `ProjetUpcycling` (Upcycling) et `Don` (Dons), via la colonne `vetement_id`.

## 3. Les acteurs

| Acteur | Ce qu'il fait |
|---|---|
| **Visiteur** | Parcourt le catalogue, filtre et voit les fiches. |
| **Client connecté** (e-mail vérifié) | Dépose ses vêtements, suit leur validation, réserve les vêtements des autres, télécharge son bon de réservation en PDF. |
| **Administrateur** | Gère les catégories (CRUD), valide ou refuse les dépôts, gère les réservations, oriente les vêtements vers les autres modules, exporte les données. |

## 4. Les métiers

Les métiers sont rangés en deux niveaux :

- les **métiers simples** sont les fonctions classiques qu'on attend de toute gestion (recherche, tri, PDF…) ;
- les **métiers avancés** sont la vraie valeur ajoutée du module (IA, verrou, automatisation…).

### 4.1 Métiers simples

| # | Métier | Qui | Où le voir | Comment c'est fait |
|---|---|---|---|---|
| S1 | **CRUD des catégories** | Admin | Admin, Dépôt, Catégories | `CategorieController` (resource) + `StoreCategorieRequest` / `UpdateCategorieRequest` |
| S2 | **CRUD des vêtements** | Admin et déposant | Admin, Vêtements / Mes dépôts | `VetementController`, `MesDepotsController`, policy `VetementPolicy` |
| S3 | **Recherche** | Tous | Catalogue, listes admin | Champ `search` : titre du vêtement, nom de catégorie, ou code / vêtement / client d'une réservation (`where … like`) |
| S4 | **Filtres** | Tous | Catalogue, listes admin | Catégorie, taille, genre, état (catalogue), plus statut et validation (admin) : `scopeFilter()` |
| S5 | **Tri** | Tous | Catalogue | Plus récents, plus anciens, de A à Z : `scopeTrier()` |
| S6 | **Pagination** | Tous | Toutes les listes | `paginate(12)` au catalogue et `paginate(10)` en admin ; `withQueryString()` garde les filtres d'une page à l'autre |
| S7 | **Bon de réservation PDF** | Client | Mes réservations, Bon PDF | `BonReservationController` + DomPDF, format A5, avec code `RES-XXXXXX` |
| S8 | **Export Excel / CSV** | Admin | Vêtements / Réservations, Exporter | `ExportController` : le fichier respecte les filtres en cours et s'ouvre dans Excel (accents corrects) |
| S9 | **Validation des formulaires** | Tous | Tous les formulaires | FormRequests avec messages en français (taille du titre, photo ≤ 2 Mo en JPG, PNG ou WebP, date de retrait…) |
| S10 | **Statistiques du dépôt** | Admin | Admin, Statistiques du dépôt | `StatistiquesDepot::rapport()` + `Admin/Depot/StatistiqueController` : indicateurs, activité des 6 derniers mois, répartitions par catégorie, état, genre, taille et créneau, meilleurs déposants |
| S11 | **Compteurs par catégorie** | Tous | Catalogue (colonne de gauche) | `withCount('vetements')` : le nombre de vêtements disponibles par catégorie |
| S12 | **Slug automatique** | Admin | Catégories | Le slug est calculé à partir du nom et reste unique, par exemple « Pulls & gilets » devient `pulls-gilets` |
| S13 | **Rapport statistique PDF** | Admin | Statistiques du dépôt, Rapport PDF | DomPDF, format A4 : tous les indicateurs du module sur une page imprimable |

### 4.2 Métiers avancés

| # | Métier | Règle | Comment c'est fait |
|---|---|---|---|
| A1 | **Classement par IA locale (Ollama)** | « Analyser la photo » : le modèle de vision **gemma3:4b** regarde la photo et propose catégorie, matière, état, genre, taille, défauts et une **description rédigée**, avec un % de confiance. Rien ne part sur Internet. | `ClassificateurIA` : POST `/api/chat` d'Ollama avec la photo en base64 et un **schéma JSON** qui impose les valeurs de la base. Environ 6 s par photo. |
| A2 | **Repli sans IA** | Si Ollama est arrêté, trop lent ou sans modèle, une analyse locale prend le relais. Le dépôt marche **toujours**. | `ClassificateurVetement` + `AnalyseImage` : couleur dominante (GD) et mots-clés du titre |
| A3 | **Détection des doublons** | La même photo déposée deux fois, même redimensionnée, est signalée à l'admin. | `DetecteurDoublons` : empreinte visuelle **dHash** de 64 bits ; deux photos sont jugées identiques si 6 bits ou moins diffèrent |
| A4 | **Modération des dépôts** | Tout dépôt est « en attente » et invisible au catalogue avant approbation. Un refus exige un motif. Un dépôt modifié repasse « en attente ». | Enum `Moderation` ; `approuver()` / `refuser()` |
| A5 | **Réservation anti-conflit** | Deux clients ne peuvent pas réserver le même vêtement en même temps. On ne réserve jamais son propre dépôt. | `GestionReservations::reserver()` : transaction + `lockForUpdate()` |
| A6 | **Cycle de vie de la réservation** | Les statuts vont d'en attente, à confirmée, puis à retirée ; le vêtement devient alors « donné ». Une annulation renvoie le vêtement au catalogue. | Enum `StatutReservation` ; chaque transition vérifie le statut de départ |
| A7 | **Expiration automatique** | Une réservation non retirée à la date prévue est annulée toute seule, et le vêtement revient au catalogue. | Commande `depot:reservations`, planifiée **chaque jour à 8 h** |
| A8 | **Rappel par e-mail** | Le client reçoit un rappel la veille du retrait, une seule fois. | Notification `RappelRetraitReservation` + colonne `rappel_envoye_le` |
| A9 | **Impact écologique** | Le catalogue affiche l'eau économisée et le CO₂ évité grâce aux vêtements qui ont trouvé preneur. | `ImpactEcologique` : valeurs selon le type (un jean ≈ 7 500 L d'eau) |
| A10 | **Recommandations** | La fiche d'un vêtement propose les vêtements les plus proches. | `Recommandations` : score (même catégorie +3, taille +2, genre +2, matière +1, état +1) |
| A11 | **Lien avec les autres modules** | Un vêtement non réservé peut partir en **réparation**, en **upcycling** ou en **don**. C'est interdit s'il est réservé. | `CycleVetement` : crée la `DemandeReparation`, le `ProjetUpcycling` ou le `Don` lié par `vetement_id` |
| A12 | **Photo optimisée** | Chaque photo reçoit une miniature de 600 px pour les listes et pour l'IA. Les pages et l'analyse sont plus rapides. | `OptimiseurPhoto` (GD) |
| A13 | **Taux de pilotage** | La page de statistiques calcule le **taux d’acceptation** des dépôts, le **taux de réussite** des réservations et le **taux d’expiration**. | `StatistiquesDepot::rapport()` : une requête SQL agrégée par indicateur, calcul compatible MySQL et SQLite |

## 5. Le scénario de démonstration (12 minutes)

**Préparation :**

1. Lancer `demarrer-site.bat` (MySQL, Laravel et Apache), puis ouvrir Ollama.
2. Lancer `php artisan depot:ia` : la commande doit afficher « Ollama est en ligne et le modèle est installé ».

**Côté administrateur : préparer le catalogue**

1. Se connecter en admin. On arrive sur `/admin` ; un client, lui, arrive sur l'accueil.
2. Ouvrir **Dépôt, puis Catégories**. Créer une catégorie, par exemple « Accessoires », avec une couleur et un ordre, pour montrer le CRUD. *(S1, S12)*

**Côté client A (le déposant)**

3. S'inscrire : CAPTCHA image, puis **code à 6 chiffres reçu par e-mail**.
4. Aller dans **Déposer un vêtement**. Saisir un titre (« Chemise »), choisir une photo, puis cliquer sur **Analyser la photo**. *(A1, A12)*
   - Le message indique : « Analyse par l'IA locale Ollama (gemma3:4b, confiance 85 %) ».
   - Catégorie, matière, état et description sont pré-remplis.
5. Corriger si besoin, puis cliquer sur **Déposer**. Le statut affiché est « En attente de validation ». *(A4)*

**Côté administrateur : modération**

6. Ouvrir **Dépôts à valider**. Ouvrir la fiche : on voit la photo, et l'alerte doublon si la photo existe déjà. *(A3)*
7. **Approuver.** Pour la démo, déposer une 2ᵉ fois la même photo puis la **refuser** avec un motif.

**Côté client B (le preneur)**

8. Ouvrir le **Catalogue** : filtres par catégorie, taille, genre et état ; tri ; bandeau d'impact écologique. *(S3, S4, S5, S6, A9)*
9. Ouvrir la fiche, puis cliquer sur **Réserver** : choisir une date (de demain à J+14), un créneau et un téléphone.
   - Le vêtement disparaît du catalogue (statut « réservé »). *(A5)*
10. Dans **Mes réservations**, télécharger le **bon PDF**. *(S7)*

**Côté administrateur : fin du cycle**

11. Dans **Réservations**, cliquer sur **Confirmer**, puis sur **Marquer comme retirée**. Le vêtement passe à « donné ». *(A6)*
12. Sur un autre vêtement, cliquer sur **Envoyer en réparation**, **Transformer** ou **Donner** : on montre le lien avec les 3 autres modules. *(A11)*
13. **Exporter** les réservations en Excel, puis lancer `php artisan depot:reservations` pour montrer l'expiration automatique. *(S8, A7, A8)*
14. Ouvrir **Statistiques du dépôt** : montrer les taux clés et l’activité par mois, puis télécharger le **rapport PDF**. *(S10, S13, A13)*

## 6. Où est le code

| Quoi | Fichier |
|---|---|
| CRUD catégories (admin) | `app/Http/Controllers/Admin/Depot/CategorieController.php` |
| CRUD vêtements + modération (admin) | `app/Http/Controllers/Admin/Depot/VetementController.php` |
| Dépôts du client | `app/Http/Controllers/Depot/MesDepotsController.php` |
| Catalogue public | `app/Http/Controllers/Depot/CatalogueController.php` |
| Réservations | `app/Http/Controllers/Depot/ReservationController.php`, `app/Services/Depot/GestionReservations.php` |
| **IA Ollama (vision)** | `app/Services/Depot/ClassificateurIA.php`, `config/depot.php` |
| Analyse de secours sans IA | `app/Services/Depot/ClassificateurVetement.php`, `AnalyseImage.php` |
| Doublons, impact, recommandations | `app/Services/Depot/DetecteurDoublons.php`, `ImpactEcologique.php`, `Recommandations.php` |
| Lien avec les autres modules | `app/Services/Depot/CycleVetement.php` |
| Statistiques du dépôt + rapport PDF | `app/Services/Depot/StatistiquesDepot.php`, `app/Http/Controllers/Admin/Depot/StatistiqueController.php`, `resources/views/admin/depot/statistiques/` |
| Validation des formulaires | `app/Http/Requests/Depot/*` |
| Commandes | `app/Console/Commands/TraiterReservations.php`, `TesterIA.php` |
| Tests | `tests/Feature/Depot/*` |

## 7. Installer l'IA (une seule fois)

```bash
# 1. Installer Ollama : https://ollama.com/download (ou winget install Ollama.Ollama)
# 2. Télécharger le modèle de vision (≈ 3,3 Go)
ollama pull gemma3:4b
# 3. Vérifier depuis le projet
php artisan depot:ia
php artisan depot:ia public/front/images/product-item-1.jpg --titre="Jean"
```

Réglages dans `.env` :

```dotenv
DEPOT_IA_ACTIVE=true
DEPOT_OLLAMA_URL=http://127.0.0.1:11434
DEPOT_OLLAMA_MODELE=gemma3:4b
```

Le premier appel charge le modèle sur la carte graphique (jusqu'à 2 minutes) : `demarrer-site.bat` le précharge au démarrage. Ensuite, une photo est analysée en 6 à 7 secondes environ (mesuré sur une RTX 4050).
