<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Insight provider
    |--------------------------------------------------------------------------
    |
    | openai — uses OpenAI Chat Completions (requires OPENAI_API_KEY)
    | rule   — deterministic insights from your data (no API key)
    |
    */
    'provider' => env('HEALTH_INSIGHTS_PROVIDER', 'rule'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
    ],

    'locale' => env('HEALTH_INSIGHTS_LOCALE', 'fr'),

    'cache_hours' => (int) env('HEALTH_INSIGHTS_CACHE_HOURS', 24),

    'bmad_prompt_path' => base_path('_bmad/custom/health-insights-prompt.md'),

];
