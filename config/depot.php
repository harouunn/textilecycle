<?php

/*
 * Module Dépôt & catalogue : classement des photos par une IA de vision qui tourne en local avec Ollama.
 * Aucune photo ne quitte l'ordinateur. Si Ollama est arrêté, l'analyse locale sans IA prend le relais.
 */
return [
    'ia' => [
        'active' => (bool) env('DEPOT_IA_ACTIVE', true),
        'url' => env('DEPOT_OLLAMA_URL', 'http://127.0.0.1:11434'),
        // Modèle multimodal (il « voit » les images) : gemma3:4b tient dans 6 Go de mémoire graphique.
        'modele' => env('DEPOT_OLLAMA_MODELE', 'gemma3:4b'),
        // Le premier appel charge le modèle sur la carte graphique (jusqu'à 2 minutes) ; ensuite 5 à 10 s par photo.
        'delai' => (int) env('DEPOT_OLLAMA_DELAI', 180),
    ],
];
