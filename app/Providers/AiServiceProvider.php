<?php

namespace App\Providers;

use App\Contracts\AiClient;
use App\Services\Ai\GeminiClient;
use Illuminate\Support\ServiceProvider;

/**
 * Liaison du client d'IA. En test : $this->app->instance(AiClient::class, $faux) ou Http::fake() sur Gemini.
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiClient::class, GeminiClient::class);
    }
}
