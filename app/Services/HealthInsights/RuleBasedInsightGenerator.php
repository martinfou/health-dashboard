<?php

namespace App\Services\HealthInsights;

class RuleBasedInsightGenerator
{
    public function generate(array $context): array
    {
        $kpis = $context['kpis'] ?? [];
        $items = [];
        $locale = $context['locale'] ?? 'fr';
        $fr = $locale === 'fr';

        $hasData = ($kpis['weight_reading_count'] ?? 0) > 0
            || ($kpis['nutrition_log_count'] ?? 0) > 0;

        if (! $hasData) {
            return [
                'summary' => $fr
                    ? 'Aucune donnée suffisante pour une analyse. Importez vos CSV (FatSecret, Garmin, Arboleaf) ou saisissez des relevés manuellement.'
                    : 'Not enough data for analysis. Import CSV files or add readings manually.',
                'items' => [
                    $this->item('recommendation', $fr ? 'Commencer l\'import' : 'Start importing', $fr
                        ? 'Allez dans Import CSV et chargez au moins un export FatSecret ou Garmin pour débloquer les graphiques et cette analyse.'
                        : 'Go to CSV Import and upload a FatSecret or Garmin export to unlock charts and insights.', 'high'),
                    $this->item('suggestion', $fr ? 'Journal quotidien' : 'Daily journal', $fr
                        ? 'Notez énergie, sommeil et humeur chaque jour — les tendances seront plus utiles une fois les données physiques en place.'
                        : 'Log energy, sleep, and mood daily for richer insights once physical data exists.', 'medium'),
                ],
            ];
        }

        $loss = $kpis['total_weight_loss_lb'] ?? null;
        if ($loss !== null && $loss > 0) {
            $items[] = $this->item('insight', $fr ? 'Progression du poids' : 'Weight progress', $fr
                ? sprintf('Vous avez perdu environ %.1f lb depuis votre premier relevé (poids actuel : %.1f lb).', $loss, $kpis['current_weight_lb'] ?? 0)
                : sprintf('You have lost about %.1f lb since your first reading (current: %.1f lb).', $loss, $kpis['current_weight_lb'] ?? 0), 'high');
        } elseif ($loss !== null && $loss < 0) {
            $items[] = $this->item('insight', $fr ? 'Variation du poids' : 'Weight change', $fr
                ? sprintf('Le poids a augmenté de %.1f lb depuis le début de suivi — utile à croiser avec les calories et l\'activité.', abs($loss))
                : sprintf('Weight is up %.1f lb since tracking started — cross-check with calories and activity.', abs($loss)), 'medium');
        }

        $whr = $kpis['current_whr'] ?? null;
        if ($whr) {
            $whrNote = $whr >= 0.9
                ? ($fr ? 'au-dessus du seuil de risque souvent cité (0,90) pour les hommes.' : 'above the commonly cited risk threshold (0.90) for men.')
                : ($fr ? 'dans une zone généralement favorable.' : 'in a generally favorable range.');
            $items[] = $this->item('insight', $fr ? 'Rapport taille/hanches' : 'Waist-to-hip ratio', $fr
                ? sprintf('Votre WHR actuel est %.2f — %s', $whr, $whrNote)
                : sprintf('Your current WHR is %.2f — %s', $whr, $whrNote), $whr >= 0.9 ? 'high' : 'medium');
        }

        $waistLoss = $kpis['waist_loss_cm'] ?? null;
        if ($waistLoss !== null && $waistLoss > 0) {
            $items[] = $this->item('insight', $fr ? 'Tour de taille' : 'Waist measurement', $fr
                ? sprintf('Réduction d\'environ %.1f cm au tour de taille — signe de composition corporelle en amélioration.', $waistLoss)
                : sprintf('Waist down about %.1f cm — suggests improving body composition.', $waistLoss), 'medium');
        }

        $avgCal = $kpis['avg_calories_60d'] ?? 0;
        if ($avgCal > 0) {
            $items[] = $this->item('comment', $fr ? 'Apport calorique' : 'Calorie intake', $fr
                ? sprintf('Moyenne d\'environ %d kcal/jour sur les 60 derniers jours (FatSecret).', $avgCal)
                : sprintf('Average about %d kcal/day over the last 60 days.', $avgCal), 'low');
            if ($avgCal > 2200) {
                $items[] = $this->item('recommendation', $fr ? 'Déficit calorique' : 'Calorie deficit', $fr
                    ? 'Si l\'objectif est la perte de poids, envisagez de stabiliser autour de 1800–2000 kcal selon votre activité — à valider avec un professionnel.'
                    : 'If weight loss is the goal, consider stabilizing around 1800–2000 kcal depending on activity — confirm with a professional.', 'medium');
            }
        }

        $gym = $kpis['total_gym_sessions'] ?? 0;
        if ($gym > 0) {
            $items[] = $this->item('insight', $fr ? 'Activité gym' : 'Gym activity', $fr
                ? sprintf('%d séances de gym enregistrées au total — la régularité compte plus que les pics isolés.', $gym)
                : sprintf('%d gym sessions logged in total — consistency matters more than isolated peaks.', $gym), 'low');
        } else {
            $items[] = $this->item('suggestion', $fr ? 'Bouger davantage' : 'Move more', $fr
                ? 'Peu de séances gym importées — ajoutez des exports Garmin ou planifiez 2–3 séances structurées par semaine.'
                : 'Few gym sessions logged — import Garmin exports or plan 2–3 structured sessions per week.', 'medium');
        }

        $journals = $context['journal_recent'] ?? [];
        if (count($journals) >= 3) {
            $avgMood = collect($journals)->avg('mood');
            $items[] = $this->item('insight', $fr ? 'Bien-être (journal)' : 'Wellness (journal)', $fr
                ? sprintf('Sur %d entrées récentes, humeur moyenne ~%.1f/10.', count($journals), $avgMood)
                : sprintf('Over %d recent entries, average mood ~%.1f/10.', count($journals), $avgMood), 'low');
        } else {
            $items[] = $this->item('suggestion', $fr ? 'Enrichir le journal' : 'Enrich the journal', $fr
                ? 'Complétez le journal quelques jours de suite pour corréler énergie/sommeil avec poids et nutrition.'
                : 'Log the journal several days in a row to correlate energy/sleep with weight and nutrition.', 'low');
        }

        $items[] = $this->item('recommendation', $fr ? 'Rapport PDF' : 'PDF report', $fr
            ? 'Générez un rapport dans l\'onglet Rapports avant un rendez-vous ou une revue mensuelle.'
            : 'Generate a report under Reports before appointments or monthly reviews.', 'low');

        $items[] = $this->item('comment', $fr ? 'Avis' : 'Disclaimer', $fr
            ? 'Cette analyse est informative (BMad + règles locales) et ne remplace pas un avis médical.'
            : 'This analysis is informational (BMad + local rules) and does not replace medical advice.', 'low');

        $summary = $fr
            ? $this->buildFrenchSummary($kpis, $loss)
            : $this->buildEnglishSummary($kpis, $loss);

        return [
            'summary' => $summary,
            'items' => array_slice($items, 0, 10),
        ];
    }

    private function item(string $type, string $title, string $body, string $priority): array
    {
        return compact('type', 'title', 'body', 'priority');
    }

    private function buildFrenchSummary(array $kpis, ?float $loss): string
    {
        $parts = [];
        if ($loss !== null && $loss > 0) {
            $parts[] = sprintf('Perte totale ~%.1f lb.', $loss);
        }
        if ($kpis['current_whr'] ?? null) {
            $parts[] = sprintf('WHR %.2f.', $kpis['current_whr']);
        }
        if ($kpis['avg_calories_60d'] ?? 0) {
            $parts[] = sprintf('~%d kcal/j en moyenne.', $kpis['avg_calories_60d']);
        }
        if (empty($parts)) {
            return 'Vue d\'ensemble basée sur vos données importées — consultez les cartes ci-dessous pour les actions prioritaires.';
        }

        return 'Vue d\'ensemble : '.implode(' ', $parts).' Analyse générée localement (mode règles).';
    }

    private function buildEnglishSummary(array $kpis, ?float $loss): string
    {
        $parts = [];
        if ($loss !== null && $loss > 0) {
            $parts[] = sprintf('Total loss ~%.1f lb.', $loss);
        }
        if ($kpis['current_whr'] ?? null) {
            $parts[] = sprintf('WHR %.2f.', $kpis['current_whr']);
        }
        if ($kpis['avg_calories_60d'] ?? 0) {
            $parts[] = sprintf('~%d kcal/day average.', $kpis['avg_calories_60d']);
        }

        return empty($parts)
            ? 'Overview from your imported data — see cards below for priorities.'
            : 'Overview: '.implode(' ', $parts).' Rule-based analysis.';
    }
}
