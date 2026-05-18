<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Santé - {{ $user->name }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#333;padding:15px}
h1{font-size:18px;text-align:center;margin-bottom:4px;color:#1a1a2e}
.subtitle{text-align:center;font-size:10px;color:#666;margin-bottom:12px}
table{width:100%;border-collapse:collapse;margin:6px 0}
th{background:#f0f2f5;padding:4px 6px;text-align:left;font-size:9px;text-transform:uppercase}
td{padding:4px 6px;border-bottom:1px solid #eee;font-size:9px}
.section{margin:10px 0;padding:8px;border:1px solid #ddd;border-radius:4px}
.section h2{font-size:12px;margin-bottom:6px;color:#1a1a2e}
.grid{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0}
.kpi{flex:1;min-width:80px;text-align:center;padding:6px;background:#f8f9fb;border-radius:4px}
.kpi .val{font-size:16px;font-weight:700;color:#111}
.kpi .lbl{font-size:7px;color:#666;text-transform:uppercase}
.tag{display:inline-block;padding:1px 4px;border-radius:3px;font-size:8px;font-weight:600}
.tag-g{background:#d1fae5;color:#065f46}
.tag-r{background:#fee2e2;color:#991b1b}
.g{color:#059669} .r{color:#dc2626}
.footer{text-align:center;font-size:7px;color:#999;margin-top:16px;border-top:1px solid #ddd;padding-top:8px}
.insight{padding:6px;margin:4px 0;border-left:3px solid #6366f1;background:#f8fafc;font-size:9px}
.insight h4{font-size:10px;margin-bottom:2px}
</style>
</head>
<body>
<h1>🏥 Rapport Santé</h1>
<div class="subtitle">{{ $user->name }} · {{ $summary['period'] ?? '' }} · Généré le {{ $summary['generated_at'] ?? '' }}</div>

<div class="grid">
  <div class="kpi"><div class="val">{{ $summary['current_weight'] }} <span style="font-size:9px;color:#666">lb</span></div><div class="lbl">Poids</div></div>
  <div class="kpi"><div class="val {{ $summary['total_loss'] > 0 ? 'g' : 'r' }}">-{{ $summary['total_loss'] }}</div><div class="lbl">Perte (lb)</div></div>
  <div class="kpi"><div class="val">{{ $summary['waist_loss'] }} <span style="font-size:9px;color:#666">cm</span></div><div class="lbl">Taille perdu</div></div>
  <div class="kpi"><div class="val">{{ $summary['whr'] }}</div><div class="lbl">WHR</div></div>
  <div class="kpi"><div class="val">{{ $summary['total_gym'] }}</div><div class="lbl">Séances gym</div></div>
</div>

<div class="section">
  <h2>🥗 Nutrition — Moyennes mensuelles</h2>
  <table>
    <tr><th>Mois</th><th>Cals/j</th><th>Protéines</th><th>Gras</th><th>Glucides</th></tr>
    @foreach($summary['nutrition_monthly'] ?? [] as $n)
    <tr>
      <td>{{ data_get($n, 'month') }}</td>
      <td>{{ number_format((float) data_get($n, 'avg_cal', 0), 0) }}</td>
      <td>{{ number_format((float) data_get($n, 'avg_prot', 0), 0) }}g</td>
      <td>{{ number_format((float) data_get($n, 'avg_fat', 0), 0) }}g</td>
      <td>{{ number_format((float) data_get($n, 'avg_carbs', 0), 0) }}g</td>
    </tr>
    @endforeach
  </table>
</div>

<div class="section">
  <h2>🏋️ Activité</h2>
  <table>
    <tr><th>Mois</th><th>Gym</th><th>Pas</th><th>FC moy</th></tr>
    @foreach($summary['activity_monthly'] ?? [] as $a)
    <tr>
      <td>{{ data_get($a, 'month') }}</td>
      <td>{{ data_get($a, 'gym', 0) }} séances</td>
      <td>{{ number_format((float) data_get($a, 'steps', 0)) }}</td>
      <td>{{ number_format((float) data_get($a, 'hr', 0)) }} bpm</td>
    </tr>
    @endforeach
  </table>
</div>

<div class="insight">
  <h4>📊 Analyse</h4>
  <p>Perte de {{ $summary['total_loss'] }} lb sur la période. Tour de taille réduit de {{ $summary['waist_loss'] }} cm.
  WHR à {{ $summary['whr'] }}. Calories moyennes: {{ number_format((float) data_get(collect($summary['nutrition_monthly'] ?? [])->last(), 'avg_cal', 0), 0) }} kcal/jour.</p>
</div>

<div class="footer">
  Généré par C-3PO (OpenClaw AI) · Sources: Arboleaf, Garmin Connect, FatSecret
</div>
</body>
</html>
