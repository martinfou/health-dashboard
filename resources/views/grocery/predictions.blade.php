<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📈 Prédictions de prix — Achetez au bon moment
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produit</label>
                        <select id="product-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">— Choisissez un produit —</option>
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
                </div>
            </div>

            {{-- Results --}}
            <div id="results-container" class="space-y-6">
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <p class="text-5xl mb-4">📈</p>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Sélectionnez un produit pour voir les prédictions</p>
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
        const container = document.getElementById('results-container');

        function loadPrediction() {
            const product = productSelect.value;
            const storeId = storeSelect.value;

            if (!product && !storeId) {
                container.innerHTML = `
                    <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <p class="text-5xl mb-4">📈</p>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Sélectionnez un produit pour voir les prédictions</p>
                    </div>`;
                return;
            }

            const params = new URLSearchParams();
            if (product) params.set('product', product);
            if (storeId) params.set('store_id', storeId);

            fetch(`/grocery/predictions/data?${params}`)
                .then(r => r.json())
                .then(data => renderPrediction(data))
                .catch(() => {
                    container.innerHTML = `
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <p class="text-5xl mb-4">⚠️</p>
                            <p class="text-red-400">Erreur de chargement des données</p>
                        </div>`;
                });
        }

        function renderPrediction(data) {
            if (!data || !data.current_price) {
                container.innerHTML = `
                    <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <p class="text-5xl mb-4">📭</p>
                        <p class="text-gray-400 text-lg">Aucune donnée disponible pour ce produit</p>
                    </div>`;
                return;
            }

            const trendIcon = data.trend === 'up' ? '📈' : (data.trend === 'down' ? '📉' : '➡️');
            const trendColor = data.trend === 'up' ? 'text-red-500' : (data.trend === 'down' ? 'text-green-600' : 'text-yellow-500');

            let html = `
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">${data.product}</h3>
                            ${data.store ? `<span class="text-sm text-gray-500">🏪 ${data.store}</span>` : ''}
                        </div>
                        <span class="text-4xl">${trendIcon}</span>
                    </div>

                    {{-- Current vs Predicted --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Prix actuel (moyen)</p>
                            <p class="text-3xl font-bold text-gray-800 dark:text-gray-200">${data.current_price.toFixed(2)}$</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Prédiction mois prochain</p>
                            <p class="text-3xl font-bold ${data.next_month_prediction > data.current_price ? 'text-red-500' : 'text-green-600'}">
                                ${data.next_month_prediction.toFixed(2)}$
                            </p>
                            ${data.next_month_prediction ? `
                                <p class="text-xs mt-1 ${data.next_month_prediction > data.current_price ? 'text-red-400' : 'text-green-500'}">
                                    ${((data.next_month_prediction - data.current_price) / data.current_price * 100).toFixed(1)}%
                                    ${data.next_month_prediction > data.current_price ? 'de hausse' : 'de baisse'}
                                </p>
                            ` : ''}
                        </div>
                    </div>

                    {{-- Trend info --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tendance</p>
                            <p class="text-lg font-bold ${trendColor}">${data.trend === 'up' ? 'Hausse' : (data.trend === 'down' ? 'Baisse' : 'Stable')}</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Meilleur mois</p>
                            <p class="text-lg font-bold text-green-600">${data.best_month ?? 'N/A'}</p>
                        </div>
                        <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pire mois</p>
                            <p class="text-lg font-bold text-red-500">${data.worst_month ?? 'N/A'}</p>
                        </div>
                    </div>

                    {{-- Recommendation --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-6">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">💡 Recommandation</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">${data.recommendation}</p>
                    </div>

                    {{-- Monthly chart as bar chart --}}
                    ${data.monthly_data && data.monthly_data.length > 0 ? `
                    <div class="mt-4">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Évolution des prix (12 mois)</h4>
                        <div class="overflow-x-auto">
                            <div class="flex items-end gap-2" style="min-width: 600px; height: 200px;">
                                ${data.monthly_data.map((m, i) => {
                                    const maxPrice = Math.max(...data.monthly_data.map(d => d.avg_price));
                                    const height = maxPrice > 0 ? (m.avg_price / maxPrice) * 180 : 0;
                                    const isLast = i === data.monthly_data.length - 1;
                                    const isPrediction = false;
                                    return `
                                        <div class="flex flex-col items-center flex-1">
                                            <span class="text-xs text-gray-400 mb-1">${m.avg_price.toFixed(2)}$</span>
                                            <div class="w-full rounded-t ${isPrediction ? 'bg-blue-400' : (isLast ? 'bg-blue-500' : 'bg-blue-300')}"
                                                 style="height: ${height}px; min-height: 4px;"
                                                 title="${m.month}: ${m.avg_price.toFixed(2)}$"></div>
                                            <span class="text-xs text-gray-500 mt-1">${m.month.substring(5)}</span>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>`;

            container.innerHTML = html;
        }

        productSelect.addEventListener('change', loadPrediction);
        storeSelect.addEventListener('change', loadPrediction);
    })();
    </script>
    @endpush
</x-app-layout>
