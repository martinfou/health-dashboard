---
project_name: health-dashboard
user_name: Martinfou
date: '2026-05-17'
sections_completed:
  - technology_stack
  - language_rules
  - framework_rules
  - testing_rules
  - quality_rules
  - workflow_rules
  - anti_patterns
status: complete
rule_count: 42
optimized_for_llm: true
existing_patterns_found: 18
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Project summary

Personal health dashboard (Laravel + Inertia/Vue). Aggregates CSV data from **FatSecret**, **Garmin Connect**, and **Arboleaf**; shows KPIs/charts; daily wellness **journal**; PDF **reports**; **BMad-powered health insights** (comments, recommendations, suggestions). UI copy is primarily **French (Canada)**.

Planning artifacts: `_bmad-output/planning-artifacts/` (backlog, epics, sprint plan). BMad config: `_bmad/bmm/config.yaml`.

---

## Technology Stack & Versions

| Layer | Package / tool | Version constraint |
|-------|----------------|-------------------|
| PHP | php | ^8.3 |
| Framework | laravel/framework | ^13.8 |
| Auth UI | laravel/breeze (Inertia Vue) | ^2.4 |
| SPA bridge | inertiajs/inertia-laravel | ^2.0 |
| Frontend | vue | ^3.4 |
| SPA client | @inertiajs/vue3 | ^2.0 |
| Routes (JS) | tightenco/ziggy | ^2.0 |
| CSS | tailwindcss | ^3.2 |
| Build | vite | ^8.0 |
| Charts | chart.js, vue-chartjs | ^4.5 / ^5.3 |
| PDF | barryvdh/laravel-dompdf | ^3.1 |
| DB (local) | SQLite | `database/database.sqlite` |
| BMad | bmm module | 6.6.0 (`_bmad/`) |

---

## Critical Implementation Rules

### Language-Specific Rules (PHP)

- Use **PHP 8.3+** features: constructor property promotion, typed returns, `match` where appropriate.
- Controllers stay thin; put non-trivial logic in **`App\Services\{Domain}\`** (see `HealthInsights/`).
- **Never** use `strftime()` in new code without a DB portability plan — existing code uses SQLite `strftime('%Y-%m', ...)` in `DashboardController`, `ReportController`, `HealthInsightContextBuilder`; migrating to MySQL requires `DATE_FORMAT` or query-builder abstractions (Epic 3.4).
- Eloquent models: minimal — many use `$fillable` arrays and relationship methods only; follow existing style (no heavy docblocks).
- User-scoped queries: **always** filter by `$request->user()->id` or `auth()->id()` — several endpoints still missing ownership checks (see Don't-Miss).
- Form requests exist for auth/profile; health endpoints often validate inline in controllers — match the nearest controller pattern.
- Flash messages: `->with('success', ...)` / `->with('error', ...)` — shared via `HandleInertiaRequests` as `flash.success` / `flash.error`.

### Framework-Specific Rules (Laravel + Inertia + Vue)

**Laravel / routes**

- All authenticated app routes live in **one** `Route::middleware(['auth', 'verified'])->group()` in `routes/web.php` (includes dashboard, import, reports, journal, profile, insights).
- Auth routes in `routes/auth.php` — do not duplicate profile routes; Breeze expects `profile.edit`, `profile.update`, `profile.destroy`.
- Named routes are required anywhere the frontend calls `route('...')` — missing routes cause **Ziggy runtime errors** in Vue.
- No `routes/api.php` wired yet — API controller stubs under `App\Http\Controllers\Api\` are unused.

**Inertia**

- Pages live at `resources/js/Pages/{Name}.vue` or nested (`Pages/Auth/Login.vue`).
- Render: `Inertia::render('Dashboard', [...props])` — component path uses `/` not `.`.
- Shared props: `auth.user`, `flash` only (see `HandleInertiaRequests`) — add new global props there if needed.
- Use `AuthenticatedLayout` for logged-in pages, `GuestLayout` for auth.

**Vue**

- `<script setup>` + `defineProps` — match existing pages.
- Import alias: `@/` → `resources/js/` (verify `jsconfig.json`).
- Navigation: `@inertiajs/vue3` `Link`, `router`, `useForm` for mutations.
- **Always** use `route('name')` from Ziggy for URLs — never hardcode `/dashboard` in new Vue code unless matching an existing exception.
- Charts: register Chart.js components in page script (see `Dashboard.vue`); use `vue-chartjs` `Line` / `Bar`.
- Reusable UI: `resources/js/Components/` (e.g. `HealthInsightsPanel.vue`).

**Health insights (BMad)**

- Services: `App\Services\HealthInsights\*` — `HealthInsightService` orchestrates; auto-load uses **rule** provider; `POST /insights/refresh` with `ai=1` triggers OpenAI.
- BMad LLM system prompt: `_bmad/custom/health-insights-prompt.md` — edit here to change analyst behavior.
- Config: `config/health-insights.php`, env `OPENAI_API_KEY`, `HEALTH_INSIGHTS_PROVIDER`.
- Insight items schema: `type` ∈ `insight|recommendation|suggestion|comment`, `priority` ∈ `high|medium|low`.
- Cursor skill: `.agents/skills/bmad-health-insights/`.

**Import**

- `ImportController::uploadCsv` validates `type` ∈ `fatsecret,arboleaf_weight,arboleaf_measurements,garmin` but only **fatsecret** and partial **garmin** are implemented — UI must stay in sync when adding handlers.
- Use `updateOrCreate` with `user_id` + date keys for idempotent imports.

### Testing Rules

- PHPUnit 12 under `tests/Feature/` and `tests/Unit/`.
- Existing coverage: **Breeze auth/profile only** — no health-domain tests yet.
- New features: add Feature tests in `tests/Feature/` mirroring auth test style (`RefreshDatabase`, acting as user).
- Priority untested areas: import CSV, report PDF authorization, journal `stoic_reflection`, `HealthInsightService`.
- Run: `composer test` or `php artisan test`.

### Code Quality & Style Rules

- Match existing file layout; **no drive-by refactors** unrelated to the task.
- Vue/UI strings: **French** for user-facing text (labels, buttons, flash), unless an i18n epic changes this.
- Tailwind utility classes inline in templates — follow dashboard patterns (`rounded-xl`, `ring-1 ring-gray-200`, emoji section headers).
- Do not add verbose comments on obvious code.
- Avoid new markdown docs unless requested; **this file** and `_bmad-output/` are the planning exceptions.

### Development Workflow Rules

- Local setup: `composer install`, `npm install`, `.env`, `php artisan migrate --seed`, `npm run build`, `php artisan serve`.
- Hot reload: `composer run dev` (serve + queue + pail + vite) — creates `public/hot`.
- **Production / serve-only:** run `npm run build` and **delete `public/hot`** if present — otherwise blank white page (Vite dev URLs).
- Seeded user: `martin@fournier.dev` / `password` (`HealthDataSeeder`).
- Git: do not commit `.env`, `vendor/`, `node_modules/`, `_bmad-output/` (gitignored), `public/hot`.
- Commits/PRs: only when user asks.

### Critical Don't-Miss Rules

**Security (fix when touching related code)**

- `ReportController::downloadPdf` — `findOrFail($id)` without `user_id` scope (**IDOR**).
- `JournalController::destroy` — no ownership check on entry.
- Add Laravel **policies** when implementing Epic 2.1.

**Data / journal**

- `JournalController::store` does not validate `stoic_reflection` but Vue sends it; model casts `stoic_reflection` as **array** — align validation and UI (string vs JSON).

**Routes / frontend**

- Any new named route → rebuild frontend if using cached Ziggy in production (`npm run build`); hard refresh browser after route changes.

**Roles**

- `users.role` (`admin`, `viewer`) and `User::isAdmin()` / `canShare()` exist but are **not enforced** in middleware or UI.

**Arboleaf**

- Import types validated but **not implemented** — do not expose in UI without backend handler.

**API stubs**

- `App\Http\Controllers\Api\*` are empty — do not register routes without implementation.

**BMad output paths**

- Planning: `_bmad-output/planning-artifacts/`
- Sprint status: `_bmad-output/implementation-artifacts/sprint-status.yaml`
- Project knowledge (this file): `docs/project-context.md`

---

## Domain model quick reference

| Model | Purpose |
|-------|---------|
| `User` | Auth; `role`; hasMany health relations |
| `WeightReading` | `recorded_at`, `weight_lb`, `body_fat_pct`, `source` |
| `BodyMeasurement` | `measured_at`, waist/hips/abdomen, `whr` |
| `NutritionLog` | daily macros, `source` (e.g. FatSecret) |
| `ActivityLog` | `activity_date`, steps, gym_sessions, HR |
| `HealthReport` | generated PDF metadata + `summary_data` JSON |
| `DailyJournal` | per-day wellness + `stoic_reflection` |
| `HealthInsight` | cached AI/rule insight runs |

---

## Usage Guidelines

**For AI Agents**

- Read this file before implementing any code.
- Follow ALL rules exactly as documented.
- When in doubt, prefer the more restrictive option (auth scope, validation, French UI).
- Update this file when stack patterns or critical gotchas change.
- Use BMad skills (`bmad-help`, `bmad-dev-story`, `bmad-health-insights`) for planning and insight work.

**For Humans**

- Keep lean — agent-focused rules only.
- Update when dependencies or architecture change.
- Sync with `_bmad-output/planning-artifacts/backlog.md` when closing epics.

**Last Updated:** 2026-05-17
