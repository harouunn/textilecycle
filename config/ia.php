<?php

return [
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
        'timeout' => 20,
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3'),
        'timeout' => 20,
    ],

    'image' => [
        'max_width' => 1024,
        'max_height' => 1024,
        'quality' => 80,
    ],
];
