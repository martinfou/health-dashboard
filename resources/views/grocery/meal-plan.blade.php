<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🍽️ Plan repas de la semaine — basé sur les spéciaux
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Weekly schedule --}}
            {{-- Recently used recipes notice --}}
            @if(!empty($recentlyUsed) && count($recentlyUsed) > 0)
            <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3 text-sm">
                <div class="flex items-start gap-2">
                    <span>🔄</span>
                    <div>
                        <p class="font-medium text-yellow-800 dark:text-yellow-200">
                            {{ count($recentlyUsed) }} recette(s) exclue(s) cette semaine (déjà faites récemment)
                        </p>
                        <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                            {{ implode(', ', $recentlyUsed) }}
                        </p>
                        <a href="{{ route('grocery.meal-plan.history') }}" class="text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">
                            📜 Voir l'historique →
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if(!empty($schedule))
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                        📅 Menu de la semaine
                    </h3>
                    <div class="text-sm text-gray-500 space-x-4">
                        <span>💰 Total: <strong class="text-green-600">{{ number_format($totalCost, 2) }}$</strong></span>
                        <span>💪 Protéines: <strong class="text-blue-600">{{ number_format($totalProtein) }}g</strong></span>
                        @if($totalSavings > 0)
                            <span>💰 Économies: <strong class="text-red-500">{{ number_format($totalSavings, 2) }}$</strong></span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($schedule as $day => $meal)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-t-4 
                        {{ $loop->first ? 'border-green-500' : ($loop->index < 5 ? 'border-blue-400' : 'border-yellow-400') }}">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $day }}</span>
                            <span class="text-xl">{{ $meal['icon'] }}</span>
                        </div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-2">{{ $meal['name'] }}</h4>
                        <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <p>💪 {{ $meal['proteines'] }}g protéines | 🔥 {{ $meal['kcal'] }} kcal</p>
                            <p class="text-green-600 font-semibold">💰 ~{{ number_format($meal['estimated_cost'], 2) }}$</p>
                            @if($meal['savings'] > 0)
                                <p class="text-red-500">💰 Économie: {{ number_format($meal['savings'], 2) }}$</p>
                            @endif
                        </div>

                        {{-- Ingredients — on sale highlighted in green, full price in gray --}}
                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 mb-1.5">Ingrédients:</p>
                            <div class="flex flex-wrap gap-1">
                                @php
                                    $matchedProductNames = $meal['matched_deals']->pluck('product')->map(fn($p) => mb_strtolower($p))->toArray();
                                @endphp
                                @foreach($meal['ingredients'] as $ingredient)
                                    @php
                                        $onSale = collect($meal['matched_deals'])->first(function ($d) use ($ingredient) {
                                            return str_contains(mb_strtolower($d->product), mb_strtolower($ingredient))
                                                || str_contains(mb_strtolower($ingredient), mb_strtolower($d->product));
                                        });
                                    @endphp
                                    @if($onSale)
                                        <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded font-medium" title="{{ $onSale->store->name ?? '' }}: {{ number_format($onSale->price, 2) }}$">
                                            ✅ {{ $ingredient }}
                                        </span>
                                    @else
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-1.5 py-0.5 rounded line-through">
                                            {{ $ingredient }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Deals detail --}}
                        @if($meal['matched_deals']->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 mb-1">Spéciaux utilisés:</p>
                            @foreach($meal['matched_deals'] as $md)
                            <span class="text-xs text-blue-600 dark:text-blue-300 block">
                                🏪 {{ $md->store->name ?? '' }} — {{ $md->product }} @ {{ number_format($md->price, 2) }}$
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="mb-8 text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <p class="text-4xl mb-4">🍽️</p>
                <p class="text-gray-500 dark:text-gray-400">
                    Aucun spécial actif pour générer un plan repas.
                </p>
                <p class="text-sm text-gray-400 mt-2">
                    Importe d'abord les circulaires avec
                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">php artisan grocery:import</code>
                </p>
            </div>
            @endif

            {{-- All recipes matched --}}
            @if(isset($matchedRecipes) && $matchedRecipes->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🍳 Toutes les recettes possibles cette semaine
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($matchedRecipes as $recipe)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 
                        {{ $recipe['match_ratio'] >= 0.6 ? 'border-l-4 border-green-400' : ($recipe['match_ratio'] >= 0.3 ? 'border-l-4 border-yellow-400' : '') }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">{{ $recipe['icon'] }}</span>
                                <div>
                                    <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $recipe['name'] }}</h4>
                                    <p class="text-xs text-gray-500">
                                        💪 {{ $recipe['proteines'] }}g · 🔥 {{ $recipe['kcal'] }} kcal
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full
                                    {{ $recipe['match_pct'] >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : ($recipe['match_pct'] >= 40 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300') }}">
                                    {{ $recipe['match_pct'] }}% match
                                </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($recipe['ingredients'] as $ing)
                                    @php
                                        $onSale = $recipe['matched_deals']->first(function ($d) use ($ing) {
                                            return str_contains(mb_strtolower($d->product), mb_strtolower($ing))
                                                || str_contains(mb_strtolower($ing), mb_strtolower($d->product));
                                        });
                                    @endphp
                                    @if($onSale)
                                        <span class="text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded">✅ {{ $ing }}</span>
                                    @else
                                        <span class="text-xs bg-gray-50 dark:bg-gray-700 text-gray-400 px-1.5 py-0.5 rounded">{{ $ing }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        @if($recipe['estimated_cost'] > 0)
                            <div class="mt-2 text-xs text-gray-500">
                                💰 Coût estimé: <strong class="text-green-600">{{ number_format($recipe['estimated_cost'], 2) }}$</strong>
                                @if($recipe['savings'] > 0)
                                    <span class="text-red-500">(Économie: {{ number_format($recipe['savings'], 2) }}$)</span>
                                @endif
                            </div>
                        @endif

                        @if($recipe['matched_deals']->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($recipe['matched_deals'] as $md)
                                <span class="text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 px-1 py-0.5 rounded">
                                    {{ $md->store->name ?? '' }}: {{ $md->product }} @ {{ number_format($md->price, 2) }}$
                                </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Combined shopping list from meal plan --}}
            @if(isset($allMatchedDeals) && $allMatchedDeals->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🛍️ Liste d'épicerie combinée
                </h3>
                @php
                    $groceryList = collect($allMatchedDeals)
                        ->groupBy(fn($d) => $d instanceof \App\Models\GroceryDeal ? ($d->store->name ?? 'Autre') : 'Autre');
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($groceryList as $storeName => $items)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-3">🏪 {{ $storeName }}</h4>
                        <ul class="space-y-2">
                            @foreach($items as $item)
                            <li class="flex justify-between items-center text-sm">
                                <span>
                                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 mr-2"
                                        onchange="this.nextElementSibling.classList.toggle('line-through'); this.nextElementSibling.classList.toggle('text-gray-400');">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $item->product }}</span>
                                    @if(isset($item->is_bio) && $item->is_bio) <span class="text-xs text-green-500">🌱</span> @endif
                                </span>
                                @if($item instanceof \App\Models\GroceryDeal && $item->savings() > 0)
                                    <span class="text-xs font-bold text-red-500">-{{ number_format($item->savings(), 2) }}$</span>
                                @endif
                                <span class="font-bold text-green-600">{{ number_format($item->price ?? 0, 2) }}$</span>
                            </li>
                            @endforeach
                        </ul>
                        @php
                            $storeTotal = $items->sum(fn($d) => (float)($d->price ?? 0));
                        @endphp
                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between text-sm font-bold">
                            <span class="text-gray-500">Total {{ $storeName }}</span>
                            <span class="text-green-600">{{ number_format($storeTotal, 2) }}$</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endif

            <div class="text-center space-x-4">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    🛒 Voir les circulaires
                </a>
                <a href="{{ route('grocery.shopping-list') }}" class="text-blue-500 hover:underline text-sm">
                    🛍️ Liste d'épicerie →
                </a>
                <a href="{{ route('grocery.meal-plan.history') }}" class="text-blue-500 hover:underline text-sm">
                    📜 Historique
                </a>
                <a href="{{ route('grocery.price-intel') }}" class="text-blue-500 hover:underline text-sm">
                    💹 Price Intelligence
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
