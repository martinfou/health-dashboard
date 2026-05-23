<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🛍️ Liste d'épicerie intelligente
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(empty($shoppingList['categories']))
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <p class="text-5xl mb-4">🛍️</p>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Aucun article dans la liste</p>
                    <p class="text-sm text-gray-400 mt-2">
                        Générez d'abord un <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline">plan repas</a>
                        pour voir les ingrédients suggérés.
                    </p>
                </div>
            @else
                {{-- Summary --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Articles</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $shoppingList['items_count'] ?? 0 }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Coût estimé</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($shoppingList['total_cost'] ?? 0, 2) }}$</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Économies</p>
                        <p class="text-2xl font-bold text-red-500">{{ number_format($shoppingList['total_savings'] ?? 0, 2) }}$</p>
                    </div>
                </div>

                {{-- Shopping list by category --}}
                <div class="space-y-6">
                    @foreach($shoppingList['categories'] as $category)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-800 dark:text-gray-200">
                                {{ $category['name'] }}
                            </h3>
                            <div class="text-sm text-gray-500 space-x-3">
                                @if($category['savings'] > 0)
                                    <span class="text-red-500">-{{ number_format($category['savings'], 2) }}$</span>
                                @endif
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($category['total'], 2) }}$</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach($category['items'] as $item)
                            <div class="flex items-center justify-between py-1 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition group">
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="checkbox" 
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 item-checkbox"
                                        data-price="{{ $item['best_price'] }}"
                                        onchange="updateTotals(); this.nextElementSibling.classList.toggle('line-through'); this.nextElementSibling.classList.toggle('text-gray-400');">
                                    <span class="text-sm text-gray-800 dark:text-gray-200">{{ $item['name'] }}</span>
                                    @if($item['is_bio'])
                                        <span class="text-xs text-green-500">🌱</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3 text-sm">
                                    @if($item['on_sale'])
                                        <span class="inline-flex items-center gap-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded-full">
                                            🔥 Spécial
                                        </span>
                                        <span class="text-xs text-gray-400 line-through">{{ number_format($item['regular_price'] ?? 0, 2) }}$</span>
                                    @endif
                                    <span class="font-bold {{ $item['on_sale'] ? 'text-green-600' : 'text-gray-800 dark:text-gray-200' }}">
                                        {{ number_format($item['best_price'], 2) }}$
                                    </span>
                                    <span class="text-xs text-gray-400 hidden sm:inline">{{ $item['store'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Toolbar --}}
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <span id="checked-count">0</span>/<span id="total-count">{{ $shoppingList['items_count'] ?? 0 }}</span> cochés
                            · Total: <strong id="checked-total" class="text-green-600">0,00$</strong>
                        </div>
                        <div class="space-x-2">
                            <button onclick="clearAllChecks()" class="text-xs text-gray-500 hover:text-red-500 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded">
                                🗑️ Tout décocher
                            </button>
                            <button onclick="saveChecklist()" class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 border border-blue-300 dark:border-blue-600 rounded">
                                💾 Sauvegarder
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Suggested deals --}}
                @if(isset($suggestedDeals) && $suggestedDeals->isNotEmpty())
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                        💡 Autres spéciaux à considérer
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($suggestedDeals as $deal)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $deal->product }}</p>
                                <p class="text-xs text-gray-500">🏪 {{ $deal->store->name }} · {{ $deal->category }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">{{ number_format($deal->price, 2) }}$</p>
                                @if($deal->savings() > 0)
                                    <p class="text-xs text-red-500">-{{ number_format($deal->savings(), 2) }}$</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif

            <div class="mt-8 text-center space-x-4">
                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline text-sm">
                    🍽️ Générer un plan repas
                </a>
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    🛒 Voir les circulaires
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function updateTotals() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            let checked = 0;
            let total = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    checked++;
                    total += parseFloat(cb.dataset.price || 0);
                }
            });

            document.getElementById('checked-count').textContent = checked;
            document.getElementById('checked-total').textContent = total.toFixed(2).replace('.', ',') + '$';

            // Save to localStorage
            saveChecklist();
        }

        function saveChecklist() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const state = {};
            checkboxes.forEach((cb, i) => {
                state['item_' + i] = cb.checked;
            });
            localStorage.setItem('shopping-list-checked', JSON.stringify(state));
        }

        function loadChecklist() {
            const saved = localStorage.getItem('shopping-list-checked');
            if (!saved) return;

            try {
                const state = JSON.parse(saved);
                const checkboxes = document.querySelectorAll('.item-checkbox');
                checkboxes.forEach((cb, i) => {
                    const key = 'item_' + i;
                    if (state[key]) {
                        cb.checked = true;
                        cb.nextElementSibling.classList.add('line-through', 'text-gray-400');
                    }
                });
                updateTotals();
            } catch(e) {}
        }

        function clearAllChecks() {
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = false;
                cb.nextElementSibling.classList.remove('line-through', 'text-gray-400');
            });
            updateTotals();
        }

        // Restore saved state on load
        document.addEventListener('DOMContentLoaded', loadChecklist);

        // Update totals initially
        document.addEventListener('DOMContentLoaded', updateTotals);
    </script>
    @endpush
</x-app-layout>
