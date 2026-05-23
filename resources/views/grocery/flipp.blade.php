<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🇫🇷 Flipp — Comparateur de prix tous magasins
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Search bar + filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🔍 Rechercher un produit</label>
                        <div class="flex gap-2">
                            <input type="text" id="search-input" placeholder="Ex: poulet, beurre, lait, café..."
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <button id="search-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                                Rechercher
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie</label>
                        <select id="category-select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Toutes les catégories</option>
                            @foreach($categories as $cat => $count)
                                <option value="{{ $cat }}">{{ $cat }} ({{ $count }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Sort options --}}
                <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-xs text-gray-500">Trier par:</span>
                    <button class="sort-btn text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20" data-sort="price" data-active="true">
                        💰 Prix
                    </button>
                    <button class="sort-btn text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20" data-sort="savings">
                        💸 Économie
                    </button>
                    <button class="sort-btn text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20" data-sort="store">
                        🏪 Magasin
                    </button>
                </div>
            </div>

            {{-- Results --}}
            <div id="results-container" class="space-y-3">
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <p class="text-5xl mb-4">🇫🇷</p>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Recherchez un produit pour comparer les prix</p>
                    <p class="text-sm text-gray-400 mt-2">
                        Comparez les spéciaux de Maxi, Super C, IGA, Costco et plus
                    </p>
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
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const categorySelect = document.getElementById('category-select');
        const container = document.getElementById('results-container');
        let currentSort = 'price';

        // Sort buttons
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.sort-btn').forEach(b => {
                    b.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400');
                    b.dataset.active = 'false';
                });
                this.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400');
                this.dataset.active = 'true';
                currentSort = this.dataset.sort;
                if (searchInput.value.trim()) search();
            });
        });

        function search() {
            const q = searchInput.value.trim();
            const category = categorySelect.value;

            const params = new URLSearchParams();
            if (q) params.set('q', q);
            if (category) params.set('category', category);
            params.set('sort', currentSort);

            fetch(`/grocery/flipp/search?${params}`)
                .then(r => r.json())
                .then(data => renderResults(data, q))
                .catch(() => {
                    container.innerHTML = `
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <p class="text-4xl mb-4">⚠️</p>
                            <p class="text-red-400">Erreur de recherche</p>
                        </div>`;
                });
        }

        function renderResults(data, query) {
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <p class="text-5xl mb-4">🔍</p>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">${query ? 'Aucun résultat pour "' + query + '"' : 'Aucun produit trouvé'}</p>
                        <p class="text-sm text-gray-400 mt-2">Essayez d\'autres termes de recherche</p>
                    </div>`;
                return;
            }

            let html = `<div class="text-sm text-gray-500 mb-3">${data.length} produit(s) trouvé(s)</div>`;

            data.forEach(item => {
                const bestPrice = item.best_price.toFixed(2).replace('.', ',');
                const hasMulti = item.all_availabilities.length > 1;
                const hasSavings = item.max_savings > 0;

                html += `
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-800 dark:text-gray-200">${item.product}</h3>
                                ${item.categories ? item.categories.map(c => `<span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-1.5 py-0.5 rounded">${c}</span>`).join('') : ''}
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-green-600">${bestPrice}$</span>
                                <span class="text-xs text-gray-400 block">Meilleur prix</span>
                            </div>
                        </div>

                        {{-- All availabilities --}}
                        <div class="space-y-1">
                            ${item.all_availabilities.map(a => {
                                const isBest = a.price === item.best_price;
                                const savingsText = a.savings > 0 ? `<span class="text-xs text-red-500">-${a.savings.toFixed(2).replace('.', ',')}$ (-${a.savings_pct}%)</span>` : '';
                                return `
                                    <div class="flex items-center justify-between py-1 px-2 rounded ${isBest ? 'bg-green-50 dark:bg-green-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'}">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm">🏪 ${a.store}</span>
                                            ${a.is_bio ? '<span class="text-xs text-green-500">🌱</span>' : ''}
                                            ${a.store_brand ? `<span class="text-xs text-gray-400">[${a.store_brand}]</span>` : ''}
                                        </div>
                                        <div class="flex items-center gap-3 text-sm">
                                            ${a.regular_price ? `<span class="text-xs text-gray-400 line-through">${a.regular_price.toFixed(2).replace('.', ',')}$</span>` : ''}
                                            <span class="font-bold ${isBest ? 'text-green-600' : 'text-gray-800 dark:text-gray-200'}">
                                                ${a.price.toFixed(2).replace('.', ',')}$
                                            </span>
                                            ${savingsText}
                                            ${a.valid_until ? `<span class="text-xs text-gray-400">Jusqu'au ${a.valid_until}</span>` : ''}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>`;
            });

            container.innerHTML = html;
        }

        searchBtn.addEventListener('click', search);
        searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') search(); });
        categorySelect.addEventListener('change', () => { if (searchInput.value.trim()) search(); });
    })();
    </script>
    @endpush
</x-app-layout>
