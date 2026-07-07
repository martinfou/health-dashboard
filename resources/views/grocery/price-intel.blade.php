<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💹 Price Intelligence — Analyse des spéciaux
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Recommendations --}}
            @if($recommendations->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🎯 Top recommandations cette semaine
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($recommendations as $rec)
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg shadow-sm p-4 border border-green-200 dark:border-green-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $rec['store'] }}</span>
                                <h4 class="font-bold text-gray-800 dark:text-gray-200">{{ $rec['product'] }}</h4>
                                <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full 
                                    {{ $rec['rating'] == '🔥 EXCELLENT' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                                    {{ $rec['rating'] }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($rec['price'], 2) }}$</span>
                                @if($rec['savings'] > 0)
                                <br>
                                <span class="text-sm text-red-500">-{{ number_format($rec['savings'], 2) }}$ (-{{ $rec['savings_pct'] }}%)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Store Ranking --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🏪 Classement des enseignes
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($storeRanking as $store)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-3">{{ $store['name'] }}</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Spéciaux cette semaine</span>
                                <span class="font-bold">{{ $store['total'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-500">🔥 Excellents</span>
                                <span class="font-bold text-red-500">{{ $store['excellent'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-600">👍 Bons deals</span>
                                <span class="font-bold text-green-600">{{ $store['good'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">😐 À éviter</span>
                                <span class="font-bold text-gray-400">{{ $store['bad'] }}</span>
                            </div>
                        </div>
                        @if($store['excellent'] + $store['good'] > 0)
                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400">
                                Score: {{ $store['excellent'] * 5 + $store['good'] * 3 }}
                            </p>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Category analysis --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    📊 Analyse par catégorie
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach($categorySavings as $cat => $info)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 text-center">
                        <div class="text-2xl mb-1">{{ $info['icons'][$cat] ?? '📦' }}</div>
                        <h4 class="font-semibold text-sm text-gray-800 dark:text-gray-200">{{ ucfirst($cat) }}</h4>
                        <p class="text-xs text-gray-500 mt-1">{{ $info['count'] }} spéciaux</p>
                        @if($info['avg_score'] > 0)
                        <div class="mt-2">
                            @if($info['avg_score'] >= 4)
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-2 py-0.5 rounded-full">👍 Bons deals</span>
                            @elseif($info['avg_score'] >= 3)
                                <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">✅ Correct</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full">😐 Moyen</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- All current deals with ratings --}}
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    📋 Tous les spéciaux analysés
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 text-left text-gray-500 dark:text-gray-300">
                                <th class="px-4 py-3">Produit</th>
                                <th class="px-4 py-3">Enseigne</th>
                                <th class="px-4 py-3">Catégorie</th>
                                <th class="px-4 py-3 text-right">Prix</th>
                                <th class="px-4 py-3 text-right">Économie</th>
                                <th class="px-4 py-3 text-center">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $allDeals = [];
                                foreach($byCategory as $cat => $deals) {
                                    foreach($deals as $d) { $allDeals[] = $d; }
                                }
                                usort($allDeals, fn($a, $b) => ($b->rating['score'] ?? 0) <=> ($a->rating['score'] ?? 0));
                            @endphp
                            @forelse($allDeals as $deal)
                            <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-200">
                                    {{ $deal->product }}
                                    @if($deal->is_bio) <span class="text-xs text-green-500">🌱bio</span> @endif
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $deal->store->name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ ucfirst($deal->category) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($deal->price, 2) }}$</span>
                                    @if($deal->unit) <span class="text-xs text-gray-400">/{{ $deal->unit }}</span> @endif
                                </td>
                                <td class="px-4 py-2 text-right">
                                    @if($deal->savings() > 0)
                                    <span class="text-red-500">-{{ number_format($deal->savings(), 2) }}$</span>
                                    <span class="text-xs text-gray-400">(-{{ $deal->savingsPercent() }}%)</span>
                                    @else
                                    <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @php
                                        $ratingClass = match($deal->rating['rating']) {
                                            'excellent' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                            'good' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                            'average' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                            'weak' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'bad' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
                                            default => 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="text-xs px-2 py-1 rounded-full whitespace-nowrap {{ $ratingClass }}">
                                        {{ $deal->rating['label'] }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                    Aucun spécial actif cette semaine. Importe les circulaires via <code class="text-sm bg-gray-100 dark:bg-gray-700 px-1 rounded">php artisan grocery:import</code>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm mr-4">
                    🛒 Voir tous les spéciaux →
                </a>
                <a href="{{ route('grocery.meal-plan') }}" class="text-blue-500 hover:underline text-sm ml-4">
                    🍽️ Plan repas de la semaine →
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
