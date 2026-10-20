# Fournisseur actuel : Ollama local

L’assistant utilise désormais uniquement `http://127.0.0.1:11434/api/chat` et `qwen3:8b`, sans clé API ni fournisseur cloud. Voir [la configuration et la procédure de démonstration](upcycling-ollama-local.md). Les anciennes références OpenAI ci-dessous décrivent l’intégration précédente et ne constituent plus la configuration active.

---

# Assistant IA Transformation / Upcycling

## Installation locale
Travailler sur `feature/upcycling-ai-assistant`, dans `textilecycle-main-latest`.
Renseigner uniquement dans le `.env` local :
```
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5-mini
```
Ne jamais partager ni committer la clé. Aucun nouveau package ni build npm n’est nécessaire.
Appliquer les deux nouvelles migrations avec `php artisan migrate` sur une base sauvegardée et dédiée.
Elles ajoutent des colonnes et convertissent le statut en chaîne, compatible MySQL et SQLite.
Aucune donnée existante n’est effacée. Les anciens projets publiés restent publiés.
Ne pas utiliser les commandes de réinitialisation de la base.

## Scénario métier et entités
`ProjetUpcycling` contient les informations générales, les deux photos, le statut et les traductions.
`EtapeProjet` appartient à un projet et conserve numéro unique par projet, titre, consignes, photo, outils, durée et sécurité.
La recherche dans la galerie publique utilise uniquement les projets publiés.
L’auteur propose un projet, corrige les champs, ajoute manuellement les photos et les étapes, puis soumet.
L’administrateur valide et publie ou rejette avec un motif obligatoire. L’auteur voit le motif, corrige et resoumet.
Toute modification via les formulaires d’un projet soumis/publié ou de ses étapes nécessite une nouvelle validation.
Les imports et seeders internes existants ne sont pas un moyen de publication accessible aux utilisateurs.

## Deux moteurs différents
Le moteur local TF-IDF pondère titre, origine, résultat, description, matériel et difficulté. Il normalise quelques accents, mots vides et synonymes, calcule les poids IDF puis classe par similarité cosinus. Aucun appel externe, aucune clé et aucun apprentissage de modèle. Ce moteur lexical ne comprend pas toutes les formulations : essayer des mots plus proches si aucun résultat.
Le corpus est chargé en mémoire, adapté au catalogue actuel ; un catalogue volumineux nécessiterait un index pré-calculé.
GPT-5 mini génère des suggestions de champs, des étapes et des traductions par l’API Responses OpenAI.
Le serveur utilise un JSON Schema strict et valide ensuite les valeurs avec Laravel.
Le texte saisi et les champs textuels du projet sont envoyés à OpenAI lorsque l’utilisateur clique sur une action IA.
Ni photo, ni contenu binaire, ni URL des photos, ni clé dans le navigateur. `store: false` est envoyé à l’API.
Source : https://developers.openai.com/api/docs/guides/structured-outputs
Modèle : https://developers.openai.com/api/docs/models/gpt-5-mini
L’accès au modèle dépend du compte API et de sa facturation ; l’abonnement ChatGPT ne configure pas cette application.

## Aperçus et conservation
Les champs suggérés ne sont appliqués qu’après clic sur « Utiliser cette proposition », puis restent modifiables.
Les étapes IA ne sont pas enregistrées à la génération. Choisir, corriger ou retirer les propositions, puis joindre une photo par étape avant de sauvegarder. Les étapes enregistrées sont ajoutées, jamais remplacées en masse.
La création manuelle reste disponible même sans IA.
Les traductions fr/ar/en sont d’abord affichées en aperçu. La confirmation explicite enregistre une variante, sans remplacer les originaux.
L’aperçu est lié à l’utilisateur, au projet, à la langue et à une empreinte du contenu ; il expire après 15 minutes.
Un aperçu devenu obsolète ne peut pas être confirmé. Une traduction enregistrée puis obsolète n’est plus affichée.
La confirmation d’une traduction d’un projet publié le remet en brouillon. Il faut le resoumettre et le faire valider.
La version arabe est affichée en RTL. Les langues disponibles sont accessibles depuis la fiche du projet.

## Routes
- `GET /upcycling?idee=...&difficulte=facile` : recherche publique TF-IDF.
- `POST /upcycling/ia/projet` : champs proposés.
- `POST /upcycling/projets/{projet}/ia/etapes` : aperçu des étapes.
- `POST /upcycling/projets/{projet}/etapes/accepter` : étapes validées et photos.
- `POST /upcycling/projets/{projet}/ia/traduction` : aperçu fr/ar/en.
- `PUT /upcycling/projets/{projet}/traductions/{langue}` : confirmation de l’aperçu.
- `GET /upcycling/projets/{projet}?langue=ar` : lecture d’une traduction confirmée.
- `POST /upcycling/projets/{projet}/soumettre` : soumission/resoumission.
- `POST /admin/upcycling/projets/{projet}/publier` : validation/publication.
- `POST /admin/upcycling/projets/{projet}/rejeter` : rejet motivé.

## Permissions et validations
Les rôles existants sont `admin` et `client`, via `User::isAdmin()`.
Le middleware existant protège /admin ; la policy et le service de modération vérifient aussi le rôle.
Les actions IA demandent authentification, e-mail vérifié, accès au projet et limitation à 10 appels par minute.
Un client ne peut pas publier/rejeter, changer l’auteur ni modifier le projet d’un tiers.
Le champ statut du CRUD ne permet plus la publication directe, y compris dans le formulaire admin.
Workflow : brouillon → soumis → publie ou rejete → correction → soumis.
Projet : champs existants obligatoires, durée 5–1440, difficulté facile/moyen/difficile, photos avant/après obligatoires.
Étape : numéro 1–100 unique dans le projet, titre et consignes obligatoires, photo obligatoire ; les métadonnées sont facultatives en saisie manuelle.
Images : contenu réel contrôlé, JPEG/PNG/WEBP uniquement, 2 Mo maximum, image existante conservée à la modification.
Les étapes proposées par l’IA doivent aussi inclure outils, durée 1–1440 et conseils de sécurité.
Les estimations et consignes proposées doivent être relues avant utilisation et publication.

## Pannes et sécurité
Clé absente, erreur réseau, erreur HTTP, refus, réponse tronquée ou invalide : message explicite, aucune sauvegarde automatique, formulaire manuel conservé.
La clé est lue depuis config/upcycling.php ; aucune clé réelle dans le code ou les tests.
Les suggestions sont affichées comme texte, jamais comme HTML injecté.
Toutes les écritures utilisent les protections CSRF, les FormRequests et les permissions Laravel.
Aucun appel API réel dans les tests (`Http::fake`, `Http::preventStrayRequests`).

## Tests
```
php vendor/phpunit/phpunit/phpunit tests/Feature/UpcyclingWorkflowTest.php tests/Feature/UpcyclingAssistantTest.php tests/Feature/UpcyclingRecommendationTest.php tests/Feature/UpcyclingTest.php tests/Feature/UpcyclingValidationTest.php
```
Les tests Upcycling utilisent SQLite `:memory:` et les migrations normales, jamais la base MySQL locale.
Les scénarios couvrent CRUD, photos, limites, auteur tiers, publication interdite au client, rejet motivé, correction/resoumission, génération, traduction, pannes et recommandations excluant les non-publiés.

## Démonstration pour la professeure
1. Ouvrir /upcycling ; rechercher « J’ai un vieux jean et je veux créer un sac facile ».
2. Se connecter comme client ; proposer un projet et demander de l’aide pour les champs.
3. Relire la proposition, l’appliquer, modifier un champ et ajouter les deux photos réelles.
4. Enregistrer, générer les étapes ; en modifier une, en retirer une et joindre les photos avant de sauvegarder.
5. Prévisualiser une traduction arabe, constater que les originaux sont inchangés, puis confirmer.
6. Soumettre ; montrer que le projet reste absent de la galerie publique.
7. Comme administrateur, rejeter avec un motif ; comme auteur, montrer le motif, corriger puis resoumettre.
8. Comme administrateur, valider et publier ; consulter la galerie et la version arabe.
9. Avec un client, tenter /admin : accès refusé. Sans clé API, montrer le message et la création manuelle.

## Résultats de validation de cette livraison

- Phase 1 : 325 tests, 3 005 assertions ; migration appliquée avec succès sur MySQL.
- Phase 2 : 335 tests, 3 046 assertions ; nouvelle migration appliquée sur MySQL ; appels OpenAI simulés.
- Phase 3 finale : 345 tests Upcycling, 3 102 assertions, sans erreur.
- Non-régression globale : 564 tests, 3 840 assertions, sans erreur ni avertissement final.
- La suite globale a utilisé SQLite en mémoire, initialisé par migrations normales dans un lanceur temporaire, sans commande de réinitialisation. Sa première tentative a atteint 128 Mo dans un test existant de grandes images ; la validation finale utilise 512 Mo pour le processus uniquement.
- Edge headless : connexion réelle avec CAPTCHA ; message sans clé et conservation des champs ; génération simulée et application explicite ; choix/modification des étapes et photos obligatoires ; aperçu arabe RTL ; recherche publique ; aucune erreur JavaScript détectée.
- Aucun appel réel à OpenAI. La clé locale reste vide ; à configurer pour utiliser réellement la génération et la traduction.
- Aucun push, commit, reset ou modification d’un autre module.

## Inventaire des fichiers créés ou modifiés

- .env.example
- app/Enums/Upcycling/StatutProjet.php
- app/Http/Controllers/Admin/Upcycling/EtapeProjetController.php
- app/Http/Controllers/Upcycling/AssistantController.php
- app/Http/Controllers/Upcycling/EtapeProjetController.php
- app/Http/Controllers/Upcycling/GalerieController.php
- app/Http/Controllers/Upcycling/ModerationController.php
- app/Http/Requests/Upcycling/AssistantRequest.php
- app/Http/Requests/Upcycling/EtapeProjetRequest.php
- app/Http/Requests/Upcycling/EtapesAssistantRequest.php
- app/Http/Requests/Upcycling/ProjetUpcyclingRequest.php
- app/Models/EtapeProjet.php
- app/Models/ProjetUpcycling.php
- app/Policies/ProjetUpcyclingPolicy.php
- app/Services/Upcycling/AssistantIndisponible.php
- app/Services/Upcycling/AssistantOpenAI.php
- app/Services/Upcycling/EnregistrerAvecPhotos.php
- app/Services/Upcycling/ModerationProjet.php
- app/Services/Upcycling/RecommandationProjets.php
- config/upcycling.php
- database/migrations/2026_10_22_100000_add_upcycling_moderation.php
- database/migrations/2026_10_22_110000_add_upcycling_assistance_fields.php
- docs/upcycling-ai.md
- public/front/css/upcycling-assistant.css
- public/front/js/upcycling-assistant.js
- resources/views/admin/upcycling/etapes/_form.blade.php
- resources/views/admin/upcycling/projets/_form.blade.php
- resources/views/admin/upcycling/projets/show.blade.php
- resources/views/upcycling/etapes/index.blade.php
- resources/views/upcycling/index.blade.php
- resources/views/upcycling/mes-projets.blade.php
- resources/views/upcycling/partials/assistant-assets.blade.php
- resources/views/upcycling/partials/assistant-etapes.blade.php
- resources/views/upcycling/partials/assistant-projet.blade.php
- resources/views/upcycling/partials/assistant-traduction.blade.php
- resources/views/upcycling/partials/champs-etape-assistance.blade.php
- resources/views/upcycling/partials/details-etape.blade.php
- resources/views/upcycling/partials/form-etape.blade.php
- resources/views/upcycling/partials/form-projet.blade.php
- resources/views/upcycling/partials/moderation.blade.php
- resources/views/upcycling/show.blade.php
- routes/modules/upcycling.php
- tests/Concerns/UpcyclingDatabase.php
- tests/Feature/UpcyclingAssistantTest.php
- tests/Feature/UpcyclingRecommendationTest.php
- tests/Feature/UpcyclingTest.php
- tests/Feature/UpcyclingValidationTest.php
- tests/Feature/UpcyclingWorkflowTest.php

Le .env local a reçu uniquement les variables OpenAI vides/par défaut ; il reste ignoré par Git.


## Réponses 429 et conservation de la saisie
Deux envois maximum par action, jamais une boucle de relances. Une erreur de quota/facturation n’est pas relancée automatiquement.
`Retry-After` est interprété en secondes ou date HTTP. Pour une attente de deux secondes au plus, le serveur peut effectuer une seule seconde tentative ; sans en-tête, l’attente est d’une seconde. Un délai supérieur revient immédiatement au navigateur, sans relance planifiée.
Après un refus persistant, l’échéance est mise en cache : le serveur ne contacte plus OpenAI avant celle-ci. Sans en-tête, le délai de protection est d’une seconde pour une limitation et de 60 secondes pour un quota épuisé.
Le navigateur affiche le délai, désactive seulement les boutons IA et ne fait aucun polling ni nouvelle requête automatique à l’expiration. Les champs et fichiers restent en place ; les actions manuelles restent utilisables.
Un lien ouvre la recommandation locale TF-IDF dans un nouvel onglet avec l’idée saisie. Aucune clé API n’est nécessaire et la saisie du formulaire reste intacte.
Une réponse tronquée, refusée ou invalide n’est jamais appliquée ni sauvegardée automatiquement.
