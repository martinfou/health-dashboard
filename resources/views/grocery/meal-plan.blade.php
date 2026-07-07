<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🍽️ Plan repas de la semaine — basé sur les spéciaux
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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

            {{-- Today's quick calorie summary --}}
            @if(isset($todaySummary) && $todaySummary['total_count'] > 0)
            <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 text-sm">
                        🔥 Aujourd'hui — Suivi calorique
                    </h4>
                    <a href="{{ route('grocery.meal-plan.tracking') }}" class="text-blue-500 hover:underline text-xs">
                        Voir le détail →
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-500
                                {{ $todaySummary['calorie_progress'] > 100 ? 'bg-red-500' : ($todaySummary['calorie_progress'] > 80 ? 'bg-yellow-400' : 'bg-green-500') }}"
                                 style="width: {{ min(100, $todaySummary['calorie_progress']) }}%">
                            </div>
                        </div>
                    </div>
                    <span class="text-sm font-bold whitespace-nowrap
                        {{ $todaySummary['total_calories'] > 1900 ? 'text-red-500' : 'text-green-600' }}">
                        {{ $todaySummary['total_calories'] }} / 1,900 kcal
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ $todaySummary['eaten_count'] }}/{{ $todaySummary['total_count'] }} repas
                    </span>
                </div>
            </div>
            @endif

            {{-- Weekly schedule --}}
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
                        <form action="{{ route('grocery.meal-plan.regenerate') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-500 hover:text-blue-700 text-xs underline"
                                    onclick="return confirm('Regénérer le plan repas?')">
                                🔄 Regénérer
                            </button>
                        </form>
                    </div>
                </div>

                @php
                    $slotIcons = ['breakfast' => '🌅', 'lunch' => '☀️', 'dinner' => '🌙', 'snack' => '🍪'];
                    $slotLabels = ['breakfast' => 'Déjeuner', 'lunch' => 'Dîner', 'dinner' => 'Souper', 'snack' => 'Collation'];
                @endphp

                <div class="space-y-4">
                    @foreach($schedule as $day => $meals)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        {{-- Day header with calorie total --}}
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $day }}</span>
                                <span class="text-xs text-gray-400">
                                    {{ count($meals) }} repas
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-xs">
                                @if(isset($dailyTotals[$day]))
                                <span class="text-green-600 font-semibold">
                                    🔥 {{ $dailyTotals[$day]['total_calories'] }} kcal
                                </span>
                                <span class="text-blue-500">💪 {{ $dailyTotals[$day]['total_protein'] }}g</span>
                                <span class="text-yellow-500">🥖 {{ round($dailyTotals[$day]['total_carbs']) }}g</span>
                                @endif
                            </div>
                        </div>

                        {{-- Meals for this day --}}
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach($meals as $meal)
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-3
                                    {{ $meal['assigned_slot'] === 'dinner' ? 'bg-green-50/50 dark:bg-green-900/5' : '' }}">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">
                                            {{ $slotIcons[$meal['assigned_slot']] ?? '🍽️' }}
                                            {{ $slotLabels[$meal['assigned_slot']] ?? $meal['assigned_slot'] }}
                                        </span>
                                        <span class="text-lg">{{ $meal['icon'] }}</span>
                                    </div>
                                    <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $meal['name'] }}</h4>
                                    <div class="flex gap-2 mt-1 text-xs text-gray-500">
                                        <span>🔥 {{ $meal['kcal'] }} kcal</span>
                                        <span>💪 {{ $meal['proteines'] }}g</span>
                                        @if($meal['estimated_cost'] > 0)
                                        <span class="text-green-600">💰 {{ number_format($meal['estimated_cost'], 2) }}$</span>
                                        @endif
                                    </div>

                                    {{-- Ingredients --}}
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($meal['ingredients'] as $ingredient)
                                            @php
                                                $onSale = collect($meal['matched_deals'])->first(function ($d) use ($ingredient) {
                                                    return str_contains(mb_strtolower($d->product), mb_strtolower($ingredient))
                                                        || str_contains(mb_strtolower($ingredient), mb_strtolower($d->product));
                                                });
                                            @endphp
                                            @if($onSale)
                                                <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded font-medium">
                                                    ✅ {{ $ingredient }}
                                                </span>
                                            @else
                                                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-1.5 py-0.5 rounded line-through">
                                                    {{ $ingredient }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Deals detail --}}
                                    @if($meal['matched_deals']->isNotEmpty())
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-300">
                                        @foreach($meal['matched_deals'] as $md)
                                        <span class="block">🏪 {{ $md->store->name ?? '' }}: {{ $md->product }} @ {{ number_format($md->price, 2) }}$</span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
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
                                        @if(isset($recipe['suggested_slot']))
                                            · {{ $slotLabels[$recipe['suggested_slot']] ?? $recipe['suggested_slot'] }}
                                        @endif
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

                        <div class="mt-2 flex flex-wrap gap-1">
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

            <div class="text-center space-x-4 mt-4">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">🛒 Circulaires</a>
                <a href="{{ route('grocery.meal-plan.tracking') }}" class="text-blue-500 hover:underline text-sm font-semibold">
                    🔥 Suivi calorique
                </a>
                <a href="{{ route('grocery.meal-plan.history') }}" class="text-blue-500 hover:underline text-sm">📜 Historique</a>
                <a href="{{ route('grocery.price-intel') }}" class="text-blue-500 hover:underline text-sm">💹 Prix</a>
            </div>
        </div>
    </div>
</x-app-layout>
