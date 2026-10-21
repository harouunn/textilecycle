# Configuration de l'analyse IA

## Problème résolu

✅ **Bug corrigé** : `diagnostiquer()` définissait `analyse_statut = "ok"` trop tôt, empêchant l'analyse IA.
✅ **Demande 9 réinitialisée** : prête pour une nouvelle analyse

## Configuration requise

### 1. Clé API Gemini (pour analyser les photos)

1. Allez sur : https://makersuite.google.com/app/apikey
2. Créez une clé API gratuite
3. Ajoutez dans `.env` :
```env
GEMINI_API_KEY=votre_clé_ici
```

### 2. Ollama (pour analyser le texte) - LOCAL

```bash
# Télécharger Ollama : https://ollama.ai/download
# Puis installer le modèle llama3 :
ollama pull llama3

# Démarrer le serveur (laissez tourner) :
ollama serve
```

Vérifiez dans `.env` :
```env
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llama3
```

## Relancer l'analyse

### Option 1 : Via l'interface admin
1. Allez sur http://localhost:8000/admin/ateliers/demandes/9
2. Cliquez sur **"Relancer l'analyse"**

### Option 2 : Via Tinker
```bash
php artisan tinker --execute="App\Jobs\AnalyserDemandeJob::dispatch(App\Models\DemandeReparation::find(9), true);"
```

## Vérifier les résultats

```bash
# Voir l'analyse dans la base de données
sqlite3 database/database.sqlite "SELECT id, analyse_statut, analyse_vision, analyse_texte FROM demandes_reparation WHERE id=9;"
```

## Tester avec une nouvelle demande

1. Créez une nouvelle demande avec photo ET description
2. L'analyse devrait se lancer automatiquement
3. Les résultats apparaissent dans le bloc "Suggestions IA" (admin uniquement)

## Si ça ne fonctionne toujours pas

Vérifiez les logs :
```bash
Get-Content storage/logs/laravel.log | Select-String "Demande" | Select-Object -Last 20
```

Les erreurs possibles :
- `Gemini API key non configurée` → Ajoutez GEMINI_API_KEY
- `Connection refused` pour Ollama → Lancez `ollama serve`
- `Photo introuvable` → La photo doit exister dans storage/app/public/reparations/
