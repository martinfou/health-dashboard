<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📈 Historique des prix — Spéciaux
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Filters --}}
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('grocery.history') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Produit</label>
                        <select name="product" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <option value="">— Tous —</option>
                            @foreach($products as $p)
                            <option value="{{ $p }}" {{ $product == $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Enseigne</label>
                        <select name="store_id" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <option value="">— Toutes —</option>
                            @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            Filtrer
                        </button>
                    </div>
                    @if($product || $storeId)
                    <div>
                        <a href="{{ route('grocery.history') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            ✕ Réinitialiser
                        </a>
                    </div>
                    @endif
                </form>
            </div>

            {{-- Data --}}
            @php
                $grouped = $history->groupBy(fn($h) => $h->store->name . '|' . $h->product);
            @endphp

            @forelse($grouped as $key => $records)
                @php
                    $parts = explode('|', $key);
                    $storeName = $parts[0];
                    $productName = $parts[1];
                    $sorted = $records->sortBy('scraped_at');
                    $count = $sorted->count();
                    $avgPrice = $sorted->avg('sale_price');
                    $minPrice = $sorted->min('sale_price');
                    $maxPrice = $sorted->max('sale_price');
                    $lastPrice = $sorted->last()->sale_price;
                    $trend = $count > 1 ? $lastPrice - $sorted->first()->sale_price : 0;
                @endphp
                <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-200">
                                {{ $productName }}
                                <span class="text-sm font-normal text-gray-500">@ {{ $storeName }}</span>
                            </h3>
                        </div>
                        <div class="text-right text-sm">
                            <span class="text-gray-500">{{ $count }} observations</span>
                            <br>
                            <span class="font-bold {{ $trend <= 0 ? 'text-green-600' : 'text-red-500' }}">
                                Dernier: {{ number_format($lastPrice, 2) }}$
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4 text-center text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded p-2">
                            <span class="block text-xs text-gray-500">Moyen</span>
                            <span class="font-bold">{{ number_format($avgPrice, 2) }}$</span>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded p-2">
                            <span class="block text-xs text-green-600">Meilleur</span>
                            <span class="font-bold text-green-600">{{ number_format($minPrice, 2) }}$</span>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded p-2">
                            <span class="block text-xs text-red-500">Pire</span>
                            <span class="font-bold text-red-500">{{ number_format($maxPrice, 2) }}$</span>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded p-2">
                            <span class="block text-xs text-blue-600">Tendance</span>
                            <span class="font-bold {{ $trend <= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ $trend > 0 ? '+' : '' }}{{ number_format($trend, 2) }}$
                            </span>
                        </div>
                    </div>
                    @if($count > 1)
                    <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-400 mb-1">Historique des prix:</p>
                        <div class="flex items-end gap-1 h-16">
                            @php
                                $maxVal = $sorted->max('sale_price') ?: 1;
                                $barCount = min($count, 20);
                                $step = max(1, intdiv($count, $barCount));
                                $bars = [];
                                for ($i = 0; $i < $count; $i += $step) {
                                    $bars[] = $sorted->values()[$i] ?? $sorted->last();
                                }
                            @endphp
                            @foreach($bars as $bar)
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-blue-500 dark:bg-blue-400 rounded-t"
                                     style="height: {{ max(4, ($bar->sale_price / $maxVal) * 56) }}px;">
                                </div>
                                <span class="text-xs text-gray-400 mt-0.5" style="font-size: 8px;">
                                    {{ $bar->scraped_at->format('m/d') }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <p class="text-gray-400">Aucun historique de prix trouvé.</p>
                    <p class="text-sm text-gray-500 mt-2">
                        Lance <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">python3 scripts/price-intel.py --update</code>
                        pour commencer à collecter l'historique.
                    </p>
                </div>
            @endforelse

            <div class="mt-6">
                {{ $history->links() }}
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm mr-4">
                    🛒 Voir les circulaires →
                </a>
                <a href="{{ route('grocery.price-intel') }}" class="text-blue-500 hover:underline text-sm ml-4">
                    💹 Price Intelligence →
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
