<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🛒 Circulaires & Spéciaux de la semaine
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Each store --}}
            @foreach($stores as $store)
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                        🏪 {{ $store->name }}
                    </h3>
                    @if($store->flyer_url)
                    <a href="{{ $store->flyer_url }}" target="_blank" 
                       class="text-sm text-blue-500 hover:underline ml-2">
                        Voir la circulaire →
                    </a>
                    @endif
                    <span class="text-sm text-gray-500 ml-auto">
                        @if($store->currentDeals->count())
                        {{ $store->currentDeals->count() }} spéciaux
                        @endif
                    </span>
                </div>

                @if($store->currentDeals->isEmpty())
                    <p class="text-gray-400 italic">Aucun spécial cette semaine</p>
                @else
                    @php
                        $grouped = $store->currentDeals->groupBy('category');
                        $icons = ['fruits'=>'🍎','legumes'=>'🥦','viande'=>'🥩','poisson'=>'🐟',
                                  'laitier'=>'🧀','surgeles'=>'🧊','epicerie'=>'🥫',
                                  'snacks'=>'🍪','boissons'=>'🥤','entretien'=>'🧹'];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($grouped as $cat => $deals)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3">
                            <h4 class="font-semibold text-sm text-gray-600 dark:text-gray-300 mb-2">
                                {{ $icons[$cat] ?? '📦' }} {{ ucfirst($cat) }}
                            </h4>
                            <ul class="space-y-1">
                                @foreach($deals as $deal)
                                <li class="text-sm {{ $deal->is_bio ? 'bg-green-50 dark:bg-green-900/20 -mx-2 px-2 rounded' : '' }}">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $deal->product }}</span>
                                    @if($deal->store_brand)
                                        <span class="text-xs text-gray-400">[{{ $deal->store_brand }}]</span>
                                    @endif
                                    @if($deal->is_bio) <span class="text-xs text-green-500">🌱bio</span> @endif
                                    <br>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($deal->price, 2) }}$
                                    </span>
                                    @if($deal->unit)
                                        <span class="text-xs text-gray-400">/{{ $deal->unit }}</span>
                                    @endif
                                    @if($deal->savings() > 0)
                                        <span class="text-xs text-red-500">
                                            Économie {{ number_format($deal->savings(), 2) }}$
                                            (-{{ $deal->savingsPercent() }}%)
                                        </span>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endforeach

            {{-- Best deals --}}
            @if($bestDeals->isNotEmpty())
            <div class="mt-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    💰 Meilleures économies de la semaine
                </h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-2">Produit</th>
                                <th class="pb-2">Enseigne</th>
                                <th class="pb-2">Prix régulier</th>
                                <th class="pb-2">Prix spécial</th>
                                <th class="pb-2">Économie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bestDeals as $deal)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-2">{{ $deal->product }}</td>
                                <td>{{ $deal->store->name }}</td>
                                <td class="text-gray-500">{{ number_format($deal->regular_price, 2) }}$</td>
                                <td class="font-bold text-green-600">{{ number_format($deal->price, 2) }}$</td>
                                <td class="text-red-500">
                                    -{{ number_format($deal->savings(), 2) }}$
                                    (-{{ $deal->savingsPercent() }}%)
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Grocery nav --}}
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <div class="flex flex-wrap items-center justify-center gap-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400 font-medium">Outils:</span>
                    <a href="{{ route('grocery.price-intel') }}" class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">💹 Prix</a>
                    <a href="{{ route('grocery.meal-plan') }}" class="px-3 py-1.5 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition">🍽️ Repas</a>
                    <a href="{{ route('grocery.stock-up') }}" class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">🚨 Stock Up</a>
                    <a href="{{ route('grocery.heatmap') }}" class="px-3 py-1.5 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-300 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition">📊 Heatmap</a>
                    <a href="{{ route('grocery.shopping-list') }}" class="px-3 py-1.5 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition">🛍️ Liste</a>
                    <a href="{{ route('grocery.predictions') }}" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-300 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition">📈 Prédictions</a>
                    <a href="{{ route('grocery.flipp') }}" class="px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-300 rounded-lg hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition">🇫🇷 Flipp</a>
                    <a href="{{ route('grocery.loyalty') }}" class="px-3 py-1.5 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-300 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">🏆 Points</a>
                </div>
            </div>

            {{-- Nav link --}}
            <div class="mt-4 text-center">
                <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline text-sm">
                    ← Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
