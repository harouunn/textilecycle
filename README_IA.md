# Analyse IA - Demandes de Réparation

## Installation

### 1. Ollama (local)
```bash
# Windows/Mac/Linux : https://ollama.ai/download
ollama pull llama3
ollama serve
```

### 2. Clé Gemini API
1. Aller sur https://makersuite.google.com/app/apikey
2. Créer une clé API gratuite
3. Copier la clé

### 3. Configuration
Copier `.env.example` vers `.env` et remplir :
```
GEMINI_API_KEY=votre_clé_ici
GEMINI_MODEL=gemini-3.8-flash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llama3
```

### 4. Base de données
```bash
php artisan migrate
```

### 5. Queue
Par défaut, l'analyse est synchrone (`QUEUE_CONNECTION=sync`).

Pour passer à la queue database :
1. Dans `.env` : `QUEUE_CONNECTION=database`
2. Lancer : `php artisan queue:work`

## Utilisation

### Créer une demande
1. Connectez-vous (particulier ou admin)
2. Remplissez le formulaire avec description et/ou photo
3. L'analyse IA se lance automatiquement

### Voir les suggestions (admin)
1. Ouvrez une demande
2. Le bloc "Suggestions IA" affiche les résultats
3. Bouton "Relancer l'analyse" si besoin

### Résultats
- **Photo** : Type dommage, gravité, temps estimé
- **Texte** : Résumé, atelier conseillé, priorité
- **Statut** : "ok" (succès) ou "erreur" (échec partiel)

## Économie de tokens
- Photo redimensionnée max 1024px, JPEG 80%
- Prompts courts, maxOutputTokens=300
- Une seule analyse (sauf relance manuelle)
- Timeout 20s, 1 retry max

## Tests
```bash
php artisan test --filter="TextServiceTest|VisionServiceTest|AnalysisServiceTest"
```
