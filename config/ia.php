<?php

/*
 * IA du module Ateliers (analyse des demandes de réparation).
 * Variables propres au module (ATELIERS_*) : OLLAMA_MODEL est déjà utilisé par l'Upcycling (qwen3:8b),
 * le partager casserait l'un des deux modules. Par défaut, Ollama utilise gemma3:4b, déjà installé et
 * chargé pour le module Dépôt : pas de téléchargement en plus ni de rechargement sur la carte graphique.
 */
return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
    ],

    'ollama' => [
        'url' => env('ATELIERS_OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('ATELIERS_OLLAMA_MODELE', 'gemma3:4b'),
        'timeout' => (int) env('ATELIERS_OLLAMA_DELAI', 60),
    ],

    'image' => [
        'max_width' => 1024,
        'max_height' => 1024,
        'quality' => 80,
    ],
];
