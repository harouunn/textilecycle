<?php

namespace Tests\Feature\Dons\Ia;

use App\Contracts\AiClient;
use App\Exceptions\AiUnavailableException;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiClientTest extends TestCase
{
    use FauxGemini;

    private const SCHEMA = ['type' => 'OBJECT', 'properties' => ['ok' => ['type' => 'BOOLEAN']]];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurerGemini();
    }

    private function client(): AiClient
    {
        return app(AiClient::class);
    }

    public function test_the_interface_is_bound_to_gemini(): void
    {
        $this->assertInstanceOf(GeminiClient::class, $this->client());
    }

    public function test_success_sends_a_json_constrained_request_and_decodes_the_answer(): void
    {
        $this->geminiRepond(['ok' => true]);
        $photo = $this->photo();

        $this->assertSame(['ok' => true], $this->client()->generateJson('Bonjour', self::SCHEMA, $photo->getRealPath(), 'Réponds en français.'));

        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/models/gemini-test:generateContent')
                && $request->hasHeader('x-goog-api-key', 'cle-de-test')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && $request['generationConfig']['responseSchema'] === self::SCHEMA
                && $request['contents'][0]['parts'][0]['text'] === 'Bonjour'
                && $request['contents'][0]['parts'][1]['inline_data']['mime_type'] === 'image/png'
                && $request['systemInstruction']['parts'][0]['text'] === 'Réponds en français.';
        });
    }

    public function test_identical_requests_are_cached(): void
    {
        $this->geminiRepond(['ok' => true]);

        $this->client()->generateJson('Même question', self::SCHEMA);
        $this->client()->generateJson('Même question', self::SCHEMA);
        $this->client()->generateJson('Autre question', self::SCHEMA);

        Http::assertSentCount(2);
    }

    public function test_invalid_json_raises_ai_unavailable(): void
    {
        $this->geminiJsonInvalide();

        $this->expectException(AiUnavailableException::class);
        $this->client()->generateJson('Question', self::SCHEMA);
    }

    public function test_empty_or_blocked_answer_raises_ai_unavailable(): void
    {
        Http::fake([self::URL_GEMINI => Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']])]);

        $this->expectException(AiUnavailableException::class);
        $this->expectExceptionMessage('SAFETY');
        $this->client()->generateJson('Question', self::SCHEMA);
    }

    public function test_429_is_retried_then_succeeds(): void
    {
        Http::fake([self::URL_GEMINI => Http::sequence()
            ->push(['error' => ['message' => 'quota']], 429)
            ->push(['error' => ['message' => 'indisponible']], 503)
            ->push(['candidates' => [['content' => ['parts' => [['text' => '{"ok":true}']]]]]])]);

        $this->assertSame(['ok' => true], $this->client()->generateJson('Question', self::SCHEMA));
        Http::assertSentCount(3);
    }

    public function test_persistent_429_gives_up_after_two_retries(): void
    {
        $this->geminiQuotaDepasse();

        try {
            $this->client()->generateJson('Question', self::SCHEMA);
            $this->fail('Une AiUnavailableException était attendue.');
        } catch (AiUnavailableException $e) {
            $this->assertSame(429, $e->status);
        }
        Http::assertSentCount(3);
    }

    public function test_client_errors_are_not_retried(): void
    {
        Http::fake([self::URL_GEMINI => Http::response(['error' => ['message' => 'API key not valid']], 400)]);

        try {
            $this->client()->generateJson('Question', self::SCHEMA);
            $this->fail('Une AiUnavailableException était attendue.');
        } catch (AiUnavailableException $e) {
            $this->assertSame(400, $e->status);
        }
        Http::assertSentCount(1);
    }

    public function test_timeout_raises_ai_unavailable_without_retry(): void
    {
        $this->geminiDelaiDepasse();

        $this->expectException(AiUnavailableException::class);
        $this->expectExceptionMessage('délai dépassé');
        $this->client()->generateJson('Question', self::SCHEMA);
    }

    public function test_timeout_is_configured_to_20_seconds(): void
    {
        $this->assertSame(20, config('services.gemini.timeout'));
    }

    public function test_missing_key_raises_without_any_request(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();

        $this->expectException(AiUnavailableException::class);
        try {
            $this->client()->generateJson('Question', self::SCHEMA);
        } finally {
            Http::assertNothingSent();
        }
    }
}
