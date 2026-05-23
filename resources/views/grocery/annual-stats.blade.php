<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Bilan annuel {{ $report['year'] }} — Comparatif des épiceries
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(!$hasData)
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <p class="text-5xl mb-4">📊</p>
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2">
                    Pas assez de données cette année
                </h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Les statistiques annuelles se rempliront au fil des semaines
                    au fur et à mesure que les circulaires sont scannées.
                    Reviens à la fin de l'année pour voir le grand comparatif !
                </p>
                <a href="{{ route('grocery') }}" class="inline-block mt-4 text-blue-500 hover:underline text-sm">
                    🛒 Voir les circulaires en cours
                </a>
            </div>
            @else

            {{-- Header stats --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-blue-600">
                        {{ $report['store_ranking'][0]['store'] ?? '—' }}
                    </p>
                    <p class="text-xs text-gray-500">🏆 Meilleure épicerie</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-green-600">
                        {{ number_format(collect($report['store_ranking'])->sum('total_deals')) }}
                    </p>
                    <p class="text-xs text-gray-500">Spéciaux total cette année</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-red-500">
                        {{ number_format(collect($report['store_ranking'])->sum('total_savings'), 2) }}$
                    </p>
                    <p class="text-xs text-gray-500">💰 Économies potentielles</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-purple-600">
                        {{ collect($report['category_leaders'])->count() }}
                    </p>
                    <p class="text-xs text-gray-500">Catégories analysées</p>
                </div>
            </div>

            {{-- 1. Store Ranking --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🏆 Classement des épiceries {{ $report['year'] }}
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-3 text-left font-semibold text-gray-600 dark:text-gray-300">#</th>
                                <th class="p-3 text-left font-semibold text-gray-600 dark:text-gray-300">Épicerie</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">Spéciaux</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">💰 Économies</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">% moyen</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">Prix moyen</th>
                                <th class="p-3 text-left font-semibold text-gray-600 dark:text-gray-300">Meilleur deal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($report['store_ranking'] as $i => $store)
                            <tr class="{{ $i === 0 ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                                <td class="p-3 font-bold text-center
                                    {{ $i === 0 ? 'text-yellow-500' : ($i === 1 ? 'text-gray-400' : ($i === 2 ? 'text-orange-400' : 'text-gray-600')) }}">
                                    @if($i === 0) 🥇 @elseif($i === 1) 🥈 @elseif($i === 2) 🥉 @else {{ $i + 1 }} @endif
                                </td>
                                <td class="p-3 font-semibold text-gray-800 dark:text-gray-200">{{ $store['store'] }}</td>
                                <td class="p-3 text-right text-gray-700 dark:text-gray-300">{{ $store['total_deals'] }}</td>
                                <td class="p-3 text-right font-semibold text-green-600">
                                    {{ number_format($store['total_savings'], 2) }}$
                                </td>
                                <td class="p-3 text-right">
                                    @if($store['avg_savings_pct'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $store['avg_savings_pct'] >= 25 ? 'bg-green-100 text-green-700' : ($store['avg_savings_pct'] >= 15 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $store['avg_savings_pct'] }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right text-gray-500">
                                    {{ $store['avg_sale_price'] > 0 ? number_format($store['avg_sale_price'], 2) . '$' : '—' }}
                                </td>
                                <td class="p-3 text-gray-600 dark:text-gray-400 text-xs max-w-[200px] truncate">
                                    @if($store['best_deal'])
                                        {{ $store['best_deal']['product'] }}
                                        ({{ number_format($store['best_deal']['savings'], 2) }}$)
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Monthly Trends Chart --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    📈 Tendances mensuelles — Économies par épicerie
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <canvas id="monthlyTrendsChart" height="120"></canvas>
                </div>
            </div>

            {{-- 3. Category Leaders --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🥇 Meilleure épicerie par catégorie
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-3 text-left font-semibold text-gray-600 dark:text-gray-300">Catégorie</th>
                                <th class="p-3 text-left font-semibold text-gray-600 dark:text-gray-300">Meilleure épicerie</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">% économie moyen</th>
                                <th class="p-3 text-right font-semibold text-gray-600 dark:text-gray-300">Spéciaux</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($report['category_leaders'] as $cat)
                            <tr>
                                <td class="p-3 font-medium text-gray-800 dark:text-gray-200">{{ $cat['category'] }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center gap-1 text-green-600 font-medium">
                                        🏪 {{ $cat['best_store'] }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $cat['avg_savings_pct'] >= 25 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $cat['avg_savings_pct'] }}%
                                    </span>
                                </td>
                                <td class="p-3 text-right text-gray-600 dark:text-gray-400">{{ $cat['total_deals'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-400">
                                    Pas assez de données par catégorie pour établir un classement.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Seasonal Insights --}}
            @if(!empty($report['seasonal_insights']))
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🗓️ Meilleur moment pour acheter
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($report['seasonal_insights'] as $insight)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-green-400">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $insight['category'] }}</p>
                                <p class="text-sm text-green-600 font-medium mt-1">
                                    🗓️ Meilleur mois: {{ $insight['best_month'] }}
                                </p>
                            </div>
                            <span class="text-xs font-bold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full">
                                -{{ $insight['best_avg_savings_pct'] }}%
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 5. Top 10 Deals of the Year --}}
            @if(!empty($report['best_deals_overall']))
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🔥 Top 10 des meilleurs deals de l'année
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-3 text-left font-semibold text-gray-600">#</th>
                                <th class="p-3 text-left font-semibold text-gray-600">Produit</th>
                                <th class="p-3 text-left font-semibold text-gray-600">Épicerie</th>
                                <th class="p-3 text-left font-semibold text-gray-600">Catégorie</th>
                                <th class="p-3 text-right font-semibold text-gray-600">Prix spécial</th>
                                <th class="p-3 text-right font-semibold text-gray-600">💰 Économie</th>
                                <th class="p-3 text-center font-semibold text-gray-600">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($report['best_deals_overall'] as $i => $deal)
                            <tr>
                                <td class="p-3 text-center font-bold text-gray-400">{{ $i + 1 }}</td>
                                <td class="p-3 font-medium text-gray-800 dark:text-gray-200">{{ $deal['product'] }}</td>
                                <td class="p-3"><span class="text-blue-600">🏪 {{ $deal['store'] }}</span></td>
                                <td class="p-3 text-gray-500 text-xs">{{ $deal['category'] ?? '—' }}</td>
                                <td class="p-3 text-right text-green-600 font-semibold">{{ number_format($deal['sale_price'], 2) }}$</td>
                                <td class="p-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        -{{ number_format($deal['savings'], 2) }}$
                                    </span>
                                </td>
                                <td class="p-3 text-center text-xs text-gray-400">{{ $deal['date'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="text-center space-x-4 mt-4">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    🛒 Circulaires en cours
                </a>
                <a href="{{ route('grocery.price-intel') }}" class="text-blue-500 hover:underline text-sm">
                    💹 Price Intelligence
                </a>
                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline text-sm">
                    🍽️ Plan repas
                </a>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    @if($hasData)
    try {
        const res = await fetch('{{ route("grocery.annual-stats.trends") }}');
        const data = await res.json();

        const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        const datasets = data.stores.map((store, i) => ({
            label: store.store,
            data: store.data.map(m => m ? m.avg_savings_pct : null),
            borderColor: colors[i % colors.length],
            backgroundColor: colors[i % colors.length] + '20',
            fill: true,
            tension: 0.3,
            spanGaps: false,
        }));

        new Chart(document.getElementById('monthlyTrendsChart'), {
            type: 'line',
            data: {
                labels: data.months.map(m => {
                    const [y, mo] = m.split('-');
                    const months = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'];
                    return months[parseInt(mo) - 1] + ' ' + y;
                }),
                datasets: datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + ctx.formattedValue + '%',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: '% économie moyen' },
                        ticks: { callback: v => v + '%' },
                    },
                },
            },
        });
    } catch (e) {
        console.error('Chart error:', e);
    }
    @endif
});
</script>
@endpush
