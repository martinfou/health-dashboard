<?php

namespace App\Services\HealthInsights;

use App\Models\HealthInsight;
use App\Models\User;

class HealthInsightService
{
    public function __construct(
        private readonly HealthInsightContextBuilder $contextBuilder,
        private readonly RuleBasedInsightGenerator $ruleGenerator,
        private readonly OpenAiInsightGenerator $openAiGenerator,
    ) {}

    public function latestForUser(User $user): ?HealthInsight
    {
        return HealthInsight::where('user_id', $user->id)
            ->orderByDesc('generated_at')
            ->first();
    }

    public function isFresh(?HealthInsight $insight): bool
    {
        if (! $insight) {
            return false;
        }

        $hours = config('health-insights.cache_hours', 24);

        return $insight->generated_at->gte(now()->subHours($hours));
    }

    public function ensureInsights(User $user): HealthInsight
    {
        $existing = $this->latestForUser($user);

        if ($existing && $this->isFresh($existing)) {
            return $existing;
        }

        return $this->generate($user, preferAi: false);
    }

    public function generate(User $user, bool $preferAi = false): HealthInsight
    {
        $context = $this->contextBuilder->build($user);
        $provider = $this->resolveProvider($preferAi);

        $result = match ($provider) {
            'openai' => $this->openAiGenerator->generateWithFallback($context),
            default => $this->ruleGenerator->generate($context),
        };

        return HealthInsight::create([
            'user_id' => $user->id,
            'provider' => $provider === 'openai' && config('health-insights.openai.api_key')
                ? 'openai'
                : 'rule',
            'locale' => config('health-insights.locale', 'fr'),
            'summary' => $result['summary'] ?? '',
            'items' => $result['items'] ?? [],
            'context_snapshot' => $context,
            'generated_at' => now(),
        ]);
    }

    public function toArray(?HealthInsight $insight): ?array
    {
        if (! $insight) {
            return null;
        }

        return [
            'id' => $insight->id,
            'provider' => $insight->provider,
            'summary' => $insight->summary,
            'items' => $insight->items,
            'generated_at' => $insight->generated_at->toIso8601String(),
            'generated_at_human' => $insight->generated_at->locale(config('health-insights.locale', 'fr'))
                ->diffForHumans(),
            'can_refresh_ai' => filled(config('health-insights.openai.api_key')),
        ];
    }

    private function resolveProvider(bool $preferAi): string
    {
        if ($preferAi && filled(config('health-insights.openai.api_key'))) {
            return 'openai';
        }

        $configured = config('health-insights.provider', 'rule');

        if ($configured === 'openai' && filled(config('health-insights.openai.api_key'))) {
            return 'openai';
        }

        return 'rule';
    }
}
