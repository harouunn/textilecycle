# Installation d'Ollama pour Windows

## Étape 1 : Télécharger Ollama

1. Allez sur : https://ollama.ai/download
2. Cliquez sur **"Download for Windows"**
3. Le fichier `OllamaSetup.exe` sera téléchargé

## Étape 2 : Installer

1. Double-cliquez sur `OllamaSetup.exe`
2. Suivez l'assistant d'installation
3. Ollama s'installera dans `C:\Users\DELL\AppData\Local\Programs\Ollama`

## Étape 3 : Vérifier l'installation

Ouvrez un **nouveau** PowerShell et tapez :
```bash
ollama --version
```

Vous devriez voir : `ollama version 0.x.x`

## Étape 4 : Télécharger le modèle llama3

```bash
ollama pull llama3
```

Cela va télécharger ~4.7 GB (peut prendre 5-15 minutes selon votre connexion).

Vous verrez :
```
pulling manifest
pulling xxxxx... 100%
verifying sha256 digest
success
```

## Étape 5 : Démarrer le serveur Ollama

```bash
ollama serve
```

Vous devriez voir :
```
Ollama is running
```

⚠️ **IMPORTANT** : Laissez cette fenêtre PowerShell ouverte ! Ollama tourne en serveur.

## Étape 6 : Tester dans un nouveau terminal

Ouvrez un **nouveau** PowerShell :

```bash
# Test simple
ollama run llama3 "Bonjour"

# Test API
curl http://localhost:11434
```

Devrait répondre : `Ollama is running`

## Étape 7 : Tester avec Laravel

```bash
cd C:\Users\DELL\textilecycle

# Relancer une analyse
php artisan tinker --execute="App\Jobs\AnalyserDemandeJob::dispatchSync(App\Models\DemandeReparation::find(13), true);"
```

## Vérifier le résultat

```bash
sqlite3 database/database.sqlite "SELECT analyse_statut, analyse_vision, analyse_texte FROM demandes_reparation WHERE id=13;"
```

Vous devriez voir :
- `analyse_statut` : "ok"
- `analyse_vision` : {...}  
- `analyse_texte` : {...}

## Troubleshooting

### "command not found: ollama"
→ Fermez et rouvrez PowerShell

### "Failed to connect to localhost:11434"
→ Lancez `ollama serve` dans un terminal séparé

### Le téléchargement est lent
→ Normal, le modèle llama3 fait ~4.7 GB

### Ollama utilise trop de RAM
→ Normal, llama3 utilise ~4-8 GB de RAM

## Alternative : Modèle plus léger

Si votre PC a peu de RAM, utilisez un modèle plus petit :

```bash
# Au lieu de llama3
ollama pull llama3.2:1b

# Puis dans .env
OLLAMA_MODEL=llama3.2:1b
```

Le modèle 1b est plus rapide mais moins précis.
