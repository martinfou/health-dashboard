<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📜 Historique des plans repas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Stats cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $totalPlans }}</p>
                    <p class="text-sm text-gray-500">Semaines planifiées</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-green-600">{{ $uniqueRecipes }}</p>
                    <p class="text-sm text-gray-500">Recettes différentes</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 text-center">
                    <p class="text-3xl font-bold text-purple-600">{{ $usages->total() }}</p>
                    <p class="text-sm text-gray-500">Repas planifiés au total</p>
                </div>
            </div>

            {{-- Most used recipes --}}
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3">
                    🏆 Recettes les plus utilisées
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    @if(count($stats) > 0)
                        <div class="space-y-2">
                            @foreach($stats as $stat)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $stat['recipe_name'] }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-gray-400">Dernière: {{ \Carbon\Carbon::parse($stat['last_used'])->format('d M') }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $stat['times_used'] >= 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $stat['times_used'] }}×
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-sm text-center py-4">Aucune recette enregistrée encore.</p>
                    @endif
                </div>
            </div>

            {{-- Timeline --}}
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3">
                    📅 Chronologie détaillée
                </h3>

                @php
                    $grouped = $usages->groupBy('week_label');
                @endphp

                <div class="space-y-4">
                    @forelse($grouped as $week => $weekUsages)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                            <h4 class="font-semibold text-sm text-gray-600 dark:text-gray-400 mb-3">
                                📆 Semaine du {{ $weekUsages->first()->used_on->startOfWeek()->format('d M') }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2">
                                @foreach($weekUsages->sortBy('used_on') as $usage)
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2 text-center">
                                        <span class="text-xs text-gray-400 block">{{ $usage->used_on->format('D d') }}</span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $usage->recipe_name }}</span>
                                        @if($usage->context && ($usage->context['estimate_cost'] ?? 0) > 0)
                                            <span class="text-xs text-green-600 block mt-1">{{ number_format($usage->context['estimate_cost'], 2) }}$</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                            <p class="text-4xl mb-4">📜</p>
                            <p class="text-gray-500 dark:text-gray-400">Aucun historique de plan repas.</p>
                            <p class="text-sm text-gray-400 mt-2">
                                Génère d'abord un plan repas dans
                                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline">🍽️ Plan repas</a>
                            </p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $usages->links() }}
                </div>
            </div>

            <div class="text-center space-x-4">
                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline text-sm">
                    🍽️ Plan repas actuel
                </a>
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    🛒 Circulaires
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
