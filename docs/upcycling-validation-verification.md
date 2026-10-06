Contrôles de saisie du module Upcycling
=====================================

Les contrôles s’appliquent aux créations et modifications du front-office et du back-office. Les textes sont nettoyés aux extrémités, y compris les espaces Unicode, sans modifier les retours à la ligne internes. Les champs techniques restent déduits du contexte. Les autorisations existantes sont conservées.

| Champ | Règle | Message d’erreur représentatif |
|---|---|---|
| Projet : titre | Obligatoire, 5–150 caractères | Le titre doit contenir au moins 5 caractères. |
| Description | Obligatoire, 20–5 000 caractères | La description doit contenir au moins 20 caractères. |
| Vêtement d’origine | Obligatoire, 3–255 caractères | Le vêtement d’origine est obligatoire. |
| Résultat attendu | Obligatoire, 3–255 caractères | Le résultat attendu doit contenir au moins 3 caractères. |
| Matériel nécessaire | Obligatoire, au moins un élément non vide, 3–2 000 caractères ; un élément par ligne | Indiquez au moins un élément de matériel non vide. |
| Difficulté | Enum : facile, moyen, difficile | La difficulté doit être facile, moyen ou difficile. |
| Durée | Entier, 5–1 440 minutes | La durée doit être un nombre entier supérieur à zéro. |
| Statut | Back-office obligatoire : brouillon ou publie ; automatique en front-office | Le statut doit être brouillon ou publié. |
| Photos avant et après | Obligatoires sans image stockée ; JPEG, PNG ou WebP réels, au plus 2 Mo chacune | La photo avant est obligatoire. / L’image ne doit pas dépasser 2 Mo. |
| Étape : projet associé | Projet existant issu de l’URL ; appartenance de l’étape contrôlée ; autorisation avant validation | Projet inexistant : HTTP 404 ; projet non accessible : HTTP 403. |
| Étape : titre | Obligatoire, 3–150 caractères | Le titre doit contenir au moins 3 caractères. |
| Consignes (contenu) | Obligatoires, 10–5 000 caractères | Les consignes doivent contenir au moins 10 caractères. |
| Numéro | Entier, 1–100 ; unique pour le projet ; étape courante ignorée en modification | Ce numéro est déjà utilisé pour une étape de ce projet. |
| Photo d’étape | Obligatoire sans image stockée ; mêmes formats et taille que le projet | La photo de l’étape est obligatoire. |

Les contrôles HTML sont actifs : required, minlength, maxlength, min, max, step et accept. Un script commun complète les contrôles sur les espaces, les nombres et les fichiers. Les erreurs apparaissent en français sous les champs invalides, avec aria-invalid. Les erreurs et anciennes valeurs utilisent des sacs distincts projet et etape. Les fichiers doivent être sélectionnés à nouveau après un échec. En modification, les photos présentes sur le disque satisfont l’obligation ; les anciennes données sans photos ne sont pas modifiées automatiquement.

Les nouveaux fichiers sont stockés après validation. L’enregistrement est transactionnel. En cas d’échec, les nouveaux fichiers sont nettoyés et les anciens conservés. Les anciennes images remplacées ne sont supprimées qu’après commit. Aucun schéma ni aucune donnée réelle n’a été modifié par cette intervention.

Fichiers applicatifs et tests modifiés ou ajoutés
------------------------------------------------

- [app/Http/Requests/Upcycling/UpcyclingRequest.php](../app/Http/Requests/Upcycling/UpcyclingRequest.php)
- [app/Http/Requests/Upcycling/ProjetUpcyclingRequest.php](../app/Http/Requests/Upcycling/ProjetUpcyclingRequest.php)
- [app/Http/Requests/Upcycling/EtapeProjetRequest.php](../app/Http/Requests/Upcycling/EtapeProjetRequest.php)
- [app/Rules/UpcyclingImage.php](../app/Rules/UpcyclingImage.php)
- [app/Services/Upcycling/EnregistrerAvecPhotos.php](../app/Services/Upcycling/EnregistrerAvecPhotos.php)
- [app/Http/Controllers/Upcycling/MesProjetsController.php](../app/Http/Controllers/Upcycling/MesProjetsController.php)
- [app/Http/Controllers/Upcycling/EtapeProjetController.php](../app/Http/Controllers/Upcycling/EtapeProjetController.php)
- [app/Http/Controllers/Admin/Upcycling/ProjetUpcyclingController.php](../app/Http/Controllers/Admin/Upcycling/ProjetUpcyclingController.php)
- [app/Http/Controllers/Admin/Upcycling/EtapeProjetController.php](../app/Http/Controllers/Admin/Upcycling/EtapeProjetController.php)
- [resources/views/upcycling/partials/form-projet.blade.php](../resources/views/upcycling/partials/form-projet.blade.php)
- [resources/views/upcycling/partials/form-etape.blade.php](../resources/views/upcycling/partials/form-etape.blade.php)
- [resources/views/upcycling/partials/flash.blade.php](../resources/views/upcycling/partials/flash.blade.php)
- [resources/views/upcycling/partials/validation-browser.blade.php](../resources/views/upcycling/partials/validation-browser.blade.php)
- [resources/views/upcycling/create.blade.php](../resources/views/upcycling/create.blade.php)
- [resources/views/upcycling/edit.blade.php](../resources/views/upcycling/edit.blade.php)
- [resources/views/upcycling/etapes/index.blade.php](../resources/views/upcycling/etapes/index.blade.php)
- [resources/views/upcycling/etapes/edit.blade.php](../resources/views/upcycling/etapes/edit.blade.php)
- [resources/views/admin/upcycling/projets/_form.blade.php](../resources/views/admin/upcycling/projets/_form.blade.php)
- [resources/views/admin/upcycling/etapes/_form.blade.php](../resources/views/admin/upcycling/etapes/_form.blade.php)
- [resources/views/admin/upcycling/partials/flash.blade.php](../resources/views/admin/upcycling/partials/flash.blade.php)
- [resources/views/admin/upcycling/projets/create.blade.php](../resources/views/admin/upcycling/projets/create.blade.php)
- [resources/views/admin/upcycling/projets/edit.blade.php](../resources/views/admin/upcycling/projets/edit.blade.php)
- [resources/views/admin/upcycling/projets/show.blade.php](../resources/views/admin/upcycling/projets/show.blade.php)
- [resources/views/admin/upcycling/etapes/edit.blade.php](../resources/views/admin/upcycling/etapes/edit.blade.php)
- [phpunit.xml](../phpunit.xml)
- [tests/Feature/UpcyclingTest.php](../tests/Feature/UpcyclingTest.php)
- [tests/Feature/UpcyclingValidationTest.php](../tests/Feature/UpcyclingValidationTest.php)

Ce rapport est également ajouté dans docs/upcycling-validation-verification.md. Les outils ponctuels et journaux de vérification sont conservés dans storage, hors des sources suivies par Git.

Vérification automatisée
-----------------------

Suite complète : 348 tests réussis, 3 046 assertions. SQLite en mémoire est imposé dans phpunit.xml, même si des variables de connexion sont définies dans le terminal. Les fichiers sont isolés avec Storage::fake('public'). La base MySQL de l’application et les images réelles ne sont pas utilisées par ces tests.

Couverture : champs absents, vides, espaces ASCII et Unicode ; bornes de longueur ; nombres invalides, négatifs et décimaux ; enums ; autorisations ; projet inexistant et étape d’un autre projet ; numéro en double ; faux JPEG, PNG corrompu, GIF et SVG refusés ; taille supérieure à 2 Mo et acceptation de 2 Mo exactement ; vrais JPEG, PNG et WebP ; conservation des images sans remplacement ; création et modification valides ; sauvegarde annulée ou erreur SQL ; suppression des anciens fichiers après commit ; affichage des valeurs et erreurs sans contamination entre formulaires.

Commande PowerShell depuis la racine :

```powershell
& C:/wamp64/bin/php/php8.3.6/php.exe artisan test
```

Le contrôle de syntaxe JavaScript et git diff --check passent. Les huit pages de création, modification et gestion des étapes répondent HTTP 200 via le noyau Laravel avec MySQL actif. Aucun test automatisé avec un navigateur graphique n’a été exécuté.

Parcours navigateur
------------------

1. Ouvrir http://127.0.0.1:8000/login et se connecter. Utiliser uniquement des projets TEST pour les essais.
2. Dans http://127.0.0.1:8000/upcycling/projets/create, vérifier les astérisques et la mention des champs obligatoires. Essayer des champs vides, des espaces, un titre de 4 caractères, une description de 19 caractères, une durée de 2 ou 5,5 minutes. L’enregistrement doit être bloqué.
3. Ajouter des photos JPEG, PNG ou WebP de moins de 2 Mo. Vérifier le refus d’un autre format et d’un fichier dépassant 2 Mo. Créer avec toutes les données valides : le statut doit rester brouillon.
4. Ajouter une étape avec sa photo. Essayer un numéro déjà utilisé dans le projet, puis un numéro valide. Modifier l’étape en conservant le numéro courant : cela doit réussir.
5. Modifier le projet et une étape sans sélectionner de nouvelles images : celles déjà enregistrées doivent être conservées. Sur une ancienne donnée sans photo, une nouvelle image est désormais nécessaire pour valider la modification.
6. Dans http://127.0.0.1:8000/admin/upcycling/projets, répéter les essais de création et modification. Vérifier le statut obligatoire, puis publier le projet TEST et consulter sa fiche dans la galerie.
7. Pour vérifier Laravel sans les contrôles navigateur, dans les outils de développement exécuter document.querySelector('form[data-upcycling-form]').submit() sur un formulaire invalide : cette méthode envoie la requête sans événement submit ni validation HTML. Le serveur doit renvoyer les erreurs en français. Vérifier les valeurs conservées et sélectionner à nouveau les fichiers.
8. Avec un autre compte dans une fenêtre privée, tenter la modification du projet du premier compte côté front-office : elle doit être refusée. Les droits du back-office restent ceux du projet actuel.

Aucun commit, push, migrate ou réinitialisation de la base applicative n’est effectué.
