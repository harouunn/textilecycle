<?php

namespace Tests\Feature\Dons\Ia;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * Simule l'API Gemini (Http::fake) : succès, JSON invalide, délai dépassé, quota (429).
 */
trait FauxGemini
{
    protected const URL_GEMINI = 'generativelanguage.googleapis.com/*';

    protected function configurerGemini(): void
    {
        config(['services.gemini.key' => 'cle-de-test', 'services.gemini.model' => 'gemini-test']);
        // Les nouvelles tentatives attendent 500 ms : pas en test.
        Sleep::fake();
        Http::preventStrayRequests();
    }

    /**
     * @param  array<mixed>  $donnees
     */
    protected function reponseGemini(array $donnees): PromiseInterface
    {
        return Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($donnees)]]], 'finishReason' => 'STOP']]]);
    }

    /**
     * Chaque scénario repart d'un faux client neuf : sinon le premier faux enregistré resterait prioritaire.
     *
     * @param  array<string, mixed>  $faux
     */
    protected function simulerGemini(array $faux): Factory
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();

        return Http::fake($faux);
    }

    /**
     * @param  array<mixed>  $donnees
     */
    protected function geminiRepond(array $donnees): Factory
    {
        return $this->simulerGemini([self::URL_GEMINI => $this->reponseGemini($donnees)]);
    }

    protected function geminiJsonInvalide(): Factory
    {
        return $this->simulerGemini([self::URL_GEMINI => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"est_textile": tru']]]]]])]);
    }

    protected function geminiDelaiDepasse(): Factory
    {
        return $this->simulerGemini([self::URL_GEMINI => Http::failedConnection('cURL error 28: Operation timed out after 20000 milliseconds')]);
    }

    protected function geminiQuotaDepasse(): Factory
    {
        return $this->simulerGemini([self::URL_GEMINI => Http::response(['error' => ['code' => 429, 'message' => 'Resource has been exhausted']], 429)]);
    }

    /**
     * Petite image PNG valide (1 × 1 pixel), sans dépendre de l'extension GD.
     */
    protected function photo(string $nom = 'pull.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nom, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }
}
