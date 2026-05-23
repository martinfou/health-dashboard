<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🔥 Suivi calorique — {{ $date->isoFormat('dddd D MMMM YYYY') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Navigation dates --}}
            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('grocery.meal-plan.tracking', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}"
                   class="text-blue-500 hover:underline text-sm">← Hier</a>
                <span class="text-lg font-bold text-gray-800 dark:text-gray-200">
                    @if($date->isToday())
                        📆 Aujourd'hui
                    @elseif($date->isYesterday())
                        📆 Hier
                    @elseif($date->isTomorrow())
                        📆 Demain
                    @else
                        📆 {{ $date->isoFormat('dddd D MMMM') }}
                    @endif
                </span>
                <a href="{{ route('grocery.meal-plan.tracking', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}"
                   class="text-blue-500 hover:underline text-sm">Demain →</a>
            </div>

            {{-- Calorie progress bar --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-500">🔥 Calorie target: 1,900 kcal</span>
                    <span class="text-sm font-bold {{ $summary['total_calories'] > 1900 ? 'text-red-500' : ($summary['total_calories'] > 1600 ? 'text-yellow-500' : 'text-green-500') }}">
                        {{ number_format($summary['total_calories']) }} / 1,900 kcal
                        @if($summary['total_calories'] > 1900) ⚠️ @endif
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="h-4 rounded-full transition-all duration-500
                        {{ $summary['calorie_progress'] > 100 ? 'bg-red-500' : ($summary['calorie_progress'] > 80 ? 'bg-yellow-400' : 'bg-green-500') }}"
                         style="width: {{ min(100, $summary['calorie_progress']) }}%">
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>0</span>
                    <span>950</span>
                    <span>1,900</span>
                </div>

                {{-- Macros mini-display --}}
                <div class="grid grid-cols-4 gap-2 mt-4 text-center text-xs">
                    <div>
                        <span class="block text-lg font-bold text-blue-500">{{ round($summary['total_protein']) }}g</span>
                        <span class="text-gray-400">Protéines</span>
                    </div>
                    <div>
                        <span class="block text-lg font-bold text-yellow-500">{{ round($summary['total_carbs']) }}g</span>
                        <span class="text-gray-400">Glucides</span>
                    </div>
                    <div>
                        <span class="block text-lg font-bold text-red-400">{{ round($summary['total_fat']) }}g</span>
                        <span class="text-gray-400">Lipides</span>
                    </div>
                    <div>
                        <span class="block text-lg font-bold text-green-500">{{ round($summary['total_fiber']) }}g</span>
                        <span class="text-gray-400">Fibres</span>
                    </div>
                </div>
            </div>

            @if($summary['remaining_calories'] > 0 && $summary['total_count'] == 0)
            <div class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <p class="text-4xl mb-4">🍽️</p>
                <p class="text-gray-500 dark:text-gray-400">
                    Aucun repas planifié pour cette journée.
                </p>
                <p class="text-sm text-gray-400 mt-2">
                    Génère d'abord un plan repas dans
                    <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline">🍽️ Plan repas</a>
                </p>
            </div>
            @endif

            {{-- Meal slots --}}
            @php
                $slotIcons = ['breakfast' => '🌅', 'lunch' => '☀️', 'dinner' => '🌙', 'snack' => '🍪'];
                $slotLabels = ['breakfast' => 'Déjeuner', 'lunch' => 'Dîner', 'dinner' => 'Souper', 'snack' => 'Collation'];
            @endphp

            @foreach(['breakfast', 'lunch', 'dinner', 'snack'] as $slot)
                @php
                    $slotMeals = $summary['meals']->where('meal_slot', $slot);
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-3 {{ $slotMeals->where('eaten', true)->count() > 0 ? 'border-l-4 border-green-400' : 'border-l-4 border-gray-300 dark:border-gray-600' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $slotIcons[$slot] ?? '🍽️' }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $slotLabels[$slot] ?? $slot }}</span>
                        </div>
                        <span class="text-sm text-gray-500">
                            @php
                                $slotCals = $slotMeals->where('eaten', true)->sum('calories');
                                $slotTotal = $slotMeals->sum('calories');
                            @endphp
                            @if($slotTotal > 0)
                                <span class="{{ $slotCals > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }}">
                                    {{ $slotCals }} / {{ $slotTotal }} kcal
                                </span>
                            @endif
                        </span>
                    </div>

                    @forelse($slotMeals as $meal)
                        <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">{{ $meal['icon'] ?? '🍽️' }}</span>
                                <div>
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 {{ $meal['eaten'] ? 'line-through text-gray-400' : '' }}">
                                        {{ $meal['recipe_name'] }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-2">
                                        {{ $meal['calories'] }} kcal · P{{ round($meal['protein_g']) }} G{{ round($meal['carbs_g']) }} L{{ round($meal['fat_g']) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                @if($meal['eaten'])
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">
                                        ✅ Mangé
                                    </span>
                                    <form action="{{ route('grocery.meal-plan.uneat', $meal['id']) }}" method="POST" class="inline ml-1">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-red-500" title="Annuler">↩️</button>
                                    </form>
                                @else
                                    <form action="{{ route('grocery.meal-plan.eat', $meal['id']) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full hover:bg-blue-200 transition">
                                            ✅ Marquer mangé
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-2 text-center">—</p>
                    @endforelse
                </div>
            @endforeach

            {{-- Suggestions based on remaining calories --}}
            @if(count($suggestions) > 0 && $summary['remaining_calories'] > 200)
            <div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg shadow-sm p-4 mb-6">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">
                    💡 Suggestions — Il te reste {{ $summary['remaining_calories'] }} kcal aujourd'hui
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach(array_slice($suggestions, 0, 6) as $s)
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded p-2 text-sm">
                        <span>{{ $s['icon'] }}</span>
                        <div class="flex-1">
                            <span class="text-gray-800 dark:text-gray-200">{{ $s['name'] }}</span>
                            <span class="text-xs text-gray-400 block">
                                {{ $s['kcal'] }} kcal · P{{ round($s['proteines']) }}g
                            </span>
                        </div>
                        <span class="text-xs text-green-600 font-medium whitespace-nowrap">
                            ✅ {{ $s['kcal'] }} ≤ {{ $summary['remaining_calories'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="text-center space-x-4 mt-6">
                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline text-sm">
                    🍽️ Plan repas complet
                </a>
                <a href="{{ route('grocery.meal-plan.history') }}" class="text-blue-500 hover:underline text-sm">
                    📜 Historique
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
