<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Heatmap des prix — Évolution hebdomadaire
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produit</label>
                        <select id="product-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">— Tous les produits —</option>
                            @foreach($products as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Magasin</label>
                        <select id="store-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">— Tous les magasins —</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Période</label>
                        <select id="months-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="3">3 mois</option>
                            <option value="6">6 mois</option>
                            <option value="12" selected>12 mois</option>
                            <option value="24">24 mois</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
                <span>Légende:</span>
                <span class="inline-block w-4 h-4 rounded bg-green-500"></span> Excellent
                <span class="inline-block w-4 h-4 rounded bg-green-300"></span> Bon
                <span class="inline-block w-4 h-4 rounded bg-yellow-400"></span> Moyen
                <span class="inline-block w-4 h-4 rounded bg-orange-400"></span> Élevé
                <span class="inline-block w-4 h-4 rounded bg-red-500"></span> Très élevé
            </div>

            {{-- Heatmap grid --}}
            <div id="heatmap-container" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 min-h-[400px]">
                <div class="text-center py-16 text-gray-400">
                    <p class="text-4xl mb-4">📊</p>
                    <p>Sélectionnez un produit pour afficher la heatmap</p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    ← Retour aux circulaires
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        const productSelect = document.getElementById('product-select');
        const storeSelect = document.getElementById('store-select');
        const monthsSelect = document.getElementById('months-select');
        const container = document.getElementById('heatmap-container');

        function loadHeatmap() {
            const product = productSelect.value;
            const storeId = storeSelect.value;
            const months = monthsSelect.value;

            const params = new URLSearchParams();
            if (product) params.set('product', product);
            if (storeId) params.set('store_id', storeId);
            if (months) params.set('months', months);

            fetch(`/grocery/heatmap/data?${params}`)
                .then(r => r.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-16 text-gray-400">
                                <p class="text-4xl mb-4">📊</p>
                                <p>Aucune donnée pour cette sélection</p>
                            </div>`;
                        return;
                    }
                    renderHeatmap(data);
                })
                .catch(() => {
                    container.innerHTML = `
                        <div class="text-center py-16 text-red-400">
                            <p class="text-4xl mb-4">⚠️</p>
                            <p>Erreur de chargement des données</p>
                        </div>`;
                });
        }

        function renderHeatmap(data) {
            const colorMap = {
                'green': 'bg-green-500',
                'light-green': 'bg-green-300',
                'yellow': 'bg-yellow-400',
                'orange': 'bg-orange-400',
                'red': 'bg-red-500',
            };

            const textColorMap = {
                'green': 'text-green-800 dark:text-green-200',
                'light-green': 'text-green-700 dark:text-green-300',
                'yellow': 'text-yellow-800 dark:text-yellow-200',
                'orange': 'text-orange-800 dark:text-orange-200',
                'red': 'text-red-800 dark:text-red-200',
            };

            let html = '<div class="overflow-x-auto"><table class="w-full text-sm">';
            html += '<thead><tr><th class="text-left text-gray-500 pb-2 pr-4">Semaine</th>';
            html += '<th class="text-left text-gray-500 pb-2 pr-4">Date</th>';
            html += '<th class="text-right text-gray-500 pb-2 pr-4">Prix moy.</th>';
            html += '<th class="text-right text-gray-500 pb-2 pr-4">Min</th>';
            html += '<th class="text-right text-gray-500 pb-2 pr-4">Max</th>';
            html += '<th class="text-center text-gray-500 pb-2">Perf.</th>';
            html += '</tr></thead><tbody>';

            data.forEach(item => {
                const colorClass = colorMap[item.color] || 'bg-gray-300';
                const textClass = textColorMap[item.color] || 'text-gray-500';
                html += `<tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <td class="py-2 pr-4 font-mono text-xs text-gray-600 dark:text-gray-400">${item.week}</td>
                    <td class="py-2 pr-4 text-gray-800 dark:text-gray-200">${item.date}</td>
                    <td class="py-2 pr-4 text-right font-bold">${item.avg_price.toFixed(2)}$</td>
                    <td class="py-2 pr-4 text-right text-green-600">${item.min_price.toFixed(2)}$</td>
                    <td class="py-2 pr-4 text-right text-red-500">${item.max_price.toFixed(2)}$</td>
                    <td class="py-2 text-center">
                        <span class="inline-flex items-center gap-1">
                            <span class="inline-block w-3 h-3 rounded-full ${colorClass}" title="${item.label}"></span>
                            <span class="text-xs ${textClass}">${item.percentile}%</span>
                        </span>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';

            // Stats summary
            const stats = {
                weeks: data.length,
                best: Math.min(...data.map(d => d.avg_price)),
                worst: Math.max(...data.map(d => d.avg_price)),
                bestWeek: data.find(d => d.avg_price === Math.min(...data.map(x => x.avg_price)))?.week || 'N/A',
                worstWeek: data.find(d => d.avg_price === Math.max(...data.map(x => x.avg_price)))?.week || 'N/A',
            };

            html = `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Semaines</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-200">${stats.weeks}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Meilleur prix</p>
                        <p class="text-xl font-bold text-green-600">${stats.best.toFixed(2)}$</p>
                        <p class="text-xs text-gray-400">${stats.bestWeek}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Prix le plus haut</p>
                        <p class="text-xl font-bold text-red-500">${stats.worst.toFixed(2)}$</p>
                        <p class="text-xs text-gray-400">${stats.worstWeek}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fourchette</p>
                        <p class="text-xl font-bold text-gray-800 dark:text-gray-200">${(stats.worst - stats.best).toFixed(2)}$</p>
                    </div>
                </div>
            ` + html;

            container.innerHTML = html;
        }

        productSelect.addEventListener('change', loadHeatmap);
        storeSelect.addEventListener('change', loadHeatmap);
        monthsSelect.addEventListener('change', loadHeatmap);

        // Load on page load if product pre-selected
        if (productSelect.value) loadHeatmap();
    })();
    </script>
    @endpush
</x-app-layout>
