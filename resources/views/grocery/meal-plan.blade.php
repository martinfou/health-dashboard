<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🍽️ Plan repas de la semaine — basé sur les spéciaux
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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
                        </div>

                        @if(!empty($meal['matched_deals']))
                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 mb-1">Ingrédients en spécial:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($meal['matched_deals'] as $md)
                                <span class="text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-1.5 py-0.5 rounded">
                                    {{ $md->product }}
                                </span>
                                @endforeach
                            </div>
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

            {{-- All meal ideas available --}}
            @if($availableMeals->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🍳 Tous les plats possibles cette semaine
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($availableMeals as $meal)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 flex">
                        <div class="text-2xl mr-3 flex-shrink-0">{{ $meal['icon'] }}</div>
                        <div>
                            <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ $meal['name'] }}</h4>
                            <p class="text-xs text-gray-500">
                                💪 {{ $meal['proteines'] }}g · 🔥 {{ $meal['kcal'] }} kcal · 💰 ~{{ number_format($meal['estimated_cost'], 2) }}$
                            </p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($meal['matched_deals'] as $md)
                                <span class="text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 px-1.5 py-0.5 rounded">
                                    <span class="font-medium">{{ $md->store->name }}</span>: {{ $md->product }} @ {{ number_format($md->price, 2) }}$
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Grocery list --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🛍️ Liste d'épicerie combinée
                </h3>
                @php
                    $groceryList = collect($schedule)->flatMap(fn($m) => $m['matched_deals'] ?? [])
                        ->unique(fn($d) => $d->store->name . '|' . $d->product)
                        ->groupBy(fn($d) => $d->store->name);
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($groceryList as $storeName => $items)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-3">🏪 {{ $storeName }}</h4>
                        <ul class="space-y-2">
                            @foreach($items as $item)
                            <li class="flex justify-between items-center text-sm">
                                <span>
                                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 mr-2">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $item->product }}</span>
                                    @if($item->is_bio) <span class="text-xs text-green-500">🌱</span> @endif
                                </span>
                                <span class="font-bold text-green-600">{{ number_format($item->price, 2) }}$</span>
                            </li>
                            @endforeach
                        </ul>
                        @php
                            $storeTotal = $items->sum('price');
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

            <div class="text-center">
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
