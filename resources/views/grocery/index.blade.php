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

            {{-- Nav link --}}
            <div class="mt-8 text-center">
                <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline text-sm">
                    ← Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
