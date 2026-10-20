<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),
    'ollama_base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
    'ollama_model' => env('OLLAMA_MODEL', 'qwen3:8b'),
    'timeout' => 180,
];
