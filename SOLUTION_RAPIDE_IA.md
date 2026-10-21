# Solution rapide : Analyse IA

## Problème actuel

- `GEMINI_API_KEY` est vide → VisionService ne peut pas analyser les photos
- Ollama n'est pas démarré → TextService ne peut pas analyser le texte
- Le Job tourne en boucle mais échoue silencieusement

## Solution 1 : Désactiver temporairement l'analyse IA

Si vous voulez tester le site SANS l'IA :

```bash
# Marquer toutes les demandes comme analysées (erreur)
sqlite3 database/database.sqlite "UPDATE demandes_reparation SET analyse_statut='erreur' WHERE analyse_statut IS NULL;"
```

L'interface affichera : "Analyse IA indisponible, à traiter manuellement"

## Solution 2 : Configurer l'IA complètement

### Étape 1 : Obtenir une clé Gemini (GRATUIT)

1. Allez sur : https://makersuite.google.com/app/apikey
2. Cliquez "Create API Key"
3. Copiez la clé

### Étape 2 : Configurer dans .env

Ouvrez `.env` et ajoutez :
```env
GEMINI_API_KEY=AIzaSy.... (votre vraie clé)
```

### Étape 3 : Installer et démarrer Ollama

```bash
# Windows : Téléchargez depuis https://ollama.ai/download
# Puis dans un terminal PowerShell :

ollama pull llama3
ollama serve
```

Laissez `ollama serve` tourner dans un terminal séparé.

### Étape 4 : Relancer l'analyse

```bash
# Réinitialiser la demande 9
sqlite3 database/database.sqlite "UPDATE demandes_reparation SET analyse_statut=NULL, analyse_vision=NULL, analyse_texte=NULL, analysed_at=NULL WHERE id=9;"

# Relancer le Job manuellement
php artisan tinker --execute="App\Jobs\AnalyserDemandeJob::dispatchSync(App\Models\DemandeReparation::find(9), true);"
```

## Solution 3 : Test minimal (photo seulement)

Si vous voulez juste tester la photo SANS Ollama :

1. Configurez seulement `GEMINI_API_KEY`
2. La demande doit avoir une photo mais PAS de description
3. L'analyse réussira avec la photo seule

## Vérification rapide

```bash
# Voir si Ollama tourne
curl http://localhost:11434

# Devrait répondre "Ollama is running"
```

## Pour NOW : Marquer comme erreur

```bash
sqlite3 database/database.sqlite "UPDATE demandes_reparation SET analyse_statut='erreur' WHERE id=9;"
```

Rechargez la page admin → "Analyse IA indisponible, à traiter manuellement"
