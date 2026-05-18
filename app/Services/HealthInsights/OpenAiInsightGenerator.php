<?php

namespace App\Services\HealthInsights;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiInsightGenerator
{
    public function __construct(
        private readonly RuleBasedInsightGenerator $fallback,
    ) {}

    public function generate(array $context): array
    {
        $apiKey = config('health-insights.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $systemPrompt = $this->loadBmadPrompt();
        $userPayload = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::withToken($apiKey)
            ->timeout(config('health-insights.openai.timeout', 60))
            ->post(rtrim(config('health-insights.openai.base_url'), '/').'/chat/completions', [
                'model' => config('health-insights.openai.model'),
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Analyze this health dashboard data and return JSON only:\n\n".$userPayload],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI health insights failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('OpenAI request failed: '.$response->status());
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Empty response from OpenAI.');
        }

        $parsed = json_decode($content, true);

        if (! is_array($parsed) || ! isset($parsed['items'])) {
            throw new RuntimeException('Invalid JSON structure from OpenAI.');
        }

        return $this->normalize($parsed);
    }

    public function generateWithFallback(array $context): array
    {
        try {
            return $this->generate($context);
        } catch (\Throwable $e) {
            Log::info('Falling back to rule-based insights', ['error' => $e->getMessage()]);
            $result = $this->fallback->generate($context);
            $result['summary'] = ($result['summary'] ?? '').' (Analyse IA indisponible — mode règles utilisé.)';

            return $result;
        }
    }

    private function loadBmadPrompt(): string
    {
        $path = config('health-insights.bmad_prompt_path');

        if (is_readable($path)) {
            return file_get_contents($path);
        }

        return 'You are a health data analyst. Return JSON with summary and items (insight, recommendation, suggestion, comment).';
    }

    private function normalize(array $parsed): array
    {
        $allowedTypes = ['insight', 'recommendation', 'suggestion', 'comment'];
        $allowedPriority = ['high', 'medium', 'low'];

        $items = collect($parsed['items'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['body'] ?? null))
            ->map(function ($item) use ($allowedTypes, $allowedPriority) {
                $type = in_array($item['type'] ?? '', $allowedTypes, true)
                    ? $item['type']
                    : 'insight';
                $priority = in_array($item['priority'] ?? '', $allowedPriority, true)
                    ? $item['priority']
                    : 'medium';

                return [
                    'type' => $type,
                    'title' => (string) ($item['title'] ?? ucfirst($type)),
                    'body' => (string) $item['body'],
                    'priority' => $priority,
                ];
            })
            ->values()
            ->all();

        return [
            'summary' => (string) ($parsed['summary'] ?? ''),
            'items' => $items,
        ];
    }
}
