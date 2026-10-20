# Assistant Upcycling : Ollama local

## Configuration

Projet : `C:\wamp64\www\main0\textilecycle-main-latest`.
Branche : `feature/upcycling-ollama-local`.

```dotenv
AI_PROVIDER=ollama
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3:8b
```

`OPENAI_API_KEY` n’est jamais utilisée par l’assistant actif. `.env` reste ignoré par Git ; ne jamais le committer. Les modèles cloud, les autres URL et les redirections HTTP sont refusés. Le client contourne les proxies pour cette connexion loopback. Aucun jeton d’authentification ni photo n’est envoyé.

## Architecture et garanties

`AssistantIA` prépare les schémas et applique les validations Laravel. `FabriqueFournisseurIA` sélectionne uniquement `ClientOllama`, qui implémente `FournisseurIA`. `AssistantOpenAI` est conservée comme alias de compatibilité local, sans ancien transport cloud.

Une demande déclenche un seul POST `/api/chat`, avec `stream=false`, `think=false`, `format` contenant le schéma JSON et un timeout de 180 secondes (connexion : 3 secondes). Le contenu doit être un objet JSON complet, puis respecter les règles du formulaire. Une sortie tronquée, malformée ou invalide est refusée. Les étapes doivent être consécutives. Aucun retry automatique.

La génération ne sauvegarde aucun projet ni étape. Le projet est d’abord affiché en aperçu, puis appliqué au formulaire modifiable. Les étapes disposent d’un aperçu modifiable ; une photo réelle est obligatoire pour chaque étape enregistrée. La traduction FR/AR/EN est modifiable avant confirmation, protégée par un jeton de session, les droits de l’utilisateur et une empreinte des textes sources. Les identifiants des étapes ne peuvent pas être remplacés. Les originaux sont conservés. Toute modification nécessitant une nouvelle validation remet le projet en brouillon ; seule la modération administrative permet la publication.

Si Ollama est arrêté, occupé, lent ou retourne une réponse invalide, le formulaire reste utilisable et le message propose la recherche locale TF-IDF + similarité cosinus. Cette recherche utilise uniquement les projets publiés et ne nécessite pas Ollama.

## Routes conservées

- `POST /upcycling/ia/projet`
- `POST /upcycling/projets/{projet}/ia/etapes`
- `POST /upcycling/projets/{projet}/ia/traduction`
- `POST /upcycling/projets/{projet}/etapes/accepter`
- `PUT /upcycling/projets/{projet}/traductions/{langue}`
- `GET /upcycling?idee=jean+sac+facile` (public, TF-IDF)

Les générations nécessitent un utilisateur connecté avec adresse vérifiée ; étapes et traduction nécessitent les droits sur le projet. Les routes de sauvegarde conservent leurs validations et protections CSRF.

## Lancement et démonstration

1. Ouvrir Ollama. Si son serveur n’est pas lancé : `ollama serve`. Ne pas lancer une deuxième instance sur le même port.
2. Vérifier le modèle : `ollama list` ; `qwen3:8b` est déjà installé sur cet ordinateur.
3. Dans PowerShell :

```powershell
Set-Location 'C:\wamp64\www\main0\textilecycle-main-latest'
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000
```

Conserver le service MySQL Wamp actif. Aucun seeding ni migration n’est nécessaire à cette évolution.

4. Ouvrir `http://127.0.0.1:8000/upcycling/projets/create` après connexion.
5. Saisir « J’ai un vieux jean et je veux créer un sac facile. », puis cliquer sur l’aide IA. Relire l’aperçu. Il ne sauvegarde rien.
6. Cliquer sur « Utiliser cette proposition », corriger les champs et ajouter les deux photos uniquement si l’on souhaite enregistrer un vrai brouillon.
7. Dans les étapes de son projet, générer et corriger l’aperçu, puis ajouter les photos avant une éventuelle sauvegarde.
8. Choisir français, arabe ou anglais pour une traduction ; corriger l’aperçu avant de confirmer. Les textes originaux restent disponibles.
9. Pour le secours sans modèle, ouvrir `http://127.0.0.1:8000/upcycling?idee=jean+sac+facile`.

## Tests

Les tests Upcycling utilisent SQLite `:memory:` et `Http::fake()` avec blocage des requêtes non simulées. La génération réelle contrôlée est distincte et ne sauvegarde aucun résultat. Pour la suite globale, un bootstrap temporaire remplace uniquement la migration de test par `migrate` sur SQLite en mémoire : aucun fichier vendor n’est modifié et aucune commande destructive n’est exécutée. Une limite de 512 Mo est nécessaire pour les tests photo existants du dépôt.

## Vérification réalisée

- Suite Laravel complète : 578 tests réussis, 3 943 assertions (PHP avec `memory_limit=512M`).
- Navigateur Edge sur les vues réelles et réponses simulées : aperçu avant application, champs modifiables, conservation de la saisie après indisponibilité, traduction et étapes de traduction présentes dans le formulaire de confirmation, zéro erreur JavaScript. La session navigateur précédente avait expiré ; ce contrôle a utilisé un rendu isolé des vues, sans contourner la connexion.
- Un seul appel réel à Ollama : HTTP 200, 17,7 secondes, enveloppe JSON et objet généré valides, sept champs présents, validation Laravel réussie. Aucun résultat affiché, écrit dans un fichier ou enregistré en base.
- Aucun appel OpenAI, commit ou push. Les autres modules ne sont pas modifiés.

## Fichiers modifiés pendant cette évolution

- `.env` (uniquement les trois variables locales ; ignoré par Git)
- `.env.example`
- `app/Services/Upcycling/AssistantIA.php` (nouveau)
- `app/Services/Upcycling/FournisseurIA.php` (nouveau)
- `app/Services/Upcycling/FabriqueFournisseurIA.php` (nouveau)
- `app/Services/Upcycling/ClientOllama.php` (nouveau)
- `app/Services/Upcycling/AssistantOpenAI.php` (alias de compatibilité locale)
- `config/upcycling.php`
- `app/Http/Controllers/Upcycling/AssistantController.php`
- `app/Http/Controllers/Upcycling/GalerieController.php`
- `resources/views/upcycling/partials/assistant-projet.blade.php`
- `resources/views/upcycling/partials/assistant-etapes.blade.php`
- `resources/views/upcycling/partials/assistant-traduction.blade.php`
- `public/front/js/upcycling-assistant.js`
- `tests/Feature/UpcyclingAssistantTest.php`
- `docs/upcycling-ai.md`
- `docs/upcycling-ollama-local.md`

Les autres changements présents avant cette intervention sont conservés. Les routes existantes ne sont pas modifiées par cette évolution.
