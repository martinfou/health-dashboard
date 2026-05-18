<?php

namespace App\Http\Controllers;

use App\Services\HealthInsights\HealthInsightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HealthInsightController extends Controller
{
    public function __construct(
        private readonly HealthInsightService $insightService,
    ) {}

    public function refresh(Request $request): RedirectResponse
    {
        $useAi = $request->boolean('ai', true);

        $this->insightService->generate($request->user(), $useAi);

        return back()->with(
            'success',
            $useAi && filled(config('health-insights.openai.api_key'))
                ? 'Analyse IA (BMad) régénérée.'
                : 'Analyse régénérée.'
        );
    }
}
