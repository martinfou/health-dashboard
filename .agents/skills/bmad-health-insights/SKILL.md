---
name: bmad-health-insights
description: 'Generate or refine health dashboard insights (comments, recommendations, suggestions) using BMad analyst persona and project data. Use when the user asks for health analysis, BMad insights, or to improve _bmad/custom/health-insights-prompt.md.'
---

# BMad Health Insights

## Overview

Regenerate or improve personalized health commentary for the Laravel health-dashboard app. The runtime pipeline lives in `App\Services\HealthInsights` and uses `_bmad/custom/health-insights-prompt.md` as the LLM system prompt (Mary / business analyst persona).

## When to use

- User wants richer insights, new recommendation categories, or prompt tuning
- User asks to run analysis without using the dashboard button
- Debugging OpenAI or rule-based insight generation

## Quick actions

1. **Dashboard UI:** User clicks *Analyse IA (BMad)* on `/dashboard` (requires `OPENAI_API_KEY`).
2. **Artisan (if command added later):** `php artisan health:insights {user}` — optional future.
3. **Edit prompt:** Update `_bmad/custom/health-insights-prompt.md` then refresh insights from the dashboard.

## Data sources

Read `docs/project-context.md` and inspect:

- `app/Services/HealthInsights/HealthInsightContextBuilder.php` — snapshot sent to LLM
- `health_insights` table — stored results

## Output schema

Each insight item: `type` (`insight`|`recommendation`|`suggestion`|`comment`), `title`, `body`, `priority` (`high`|`medium`|`low`).

## Env

```env
HEALTH_INSIGHTS_PROVIDER=openai   # or rule
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```
