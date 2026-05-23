<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🏆 Points de fidélité — PC Optimum & programmes
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Programs summary --}}
            @if($programs->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @foreach($programs as $programData)
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg shadow-sm p-5 border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-lg">{{ $programData['program']->name }}</h3>
                        @if($programData['program']->account_number)
                            <span class="text-xs text-gray-400">#{{ substr($programData['program']->account_number, -4) }}</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Points actuels</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($programData['total_points_balance']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Offres actives</p>
                            <p class="text-2xl font-bold text-purple-600">{{ $programData['active_offers']->count() }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Valeur des offres</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($programData['active_offers_value'], 2) }}$</p>
                        </div>
                    </div>
                    @if($programData['last_synced'])
                        <p class="text-xs text-gray-400 mt-3">Dernière synchro: {{ $programData['last_synced']->diffForHumans() }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 text-center">
                <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                    Aucun programme de fidélité configuré. Ajoutez-en un via la base de données.
                </p>
            </div>
            @endif

            {{-- Active offers --}}
            @if($programs->isNotEmpty() && $programs->first()['active_offers']->isNotEmpty())
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    🎯 Offres actives
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($programs->flatMap(fn($p) => $p['active_offers']) as $offer)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-blue-400">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $offer->description }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    🏪 {{ $offer->program->name }}
                                    @if($offer->product)
                                        · Produit: {{ $offer->product }}
                                    @elseif($offer->category)
                                        · Catégorie: {{ $offer->category }}
                                    @endif
                                    @if($offer->required_spend)
                                        · Min. {{ number_format($offer->required_spend, 2) }}$
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-blue-600">{{ number_format($offer->points_value) }} pts</p>
                                <p class="text-xs text-green-600">≈ {{ number_format($offer->points_value / 1000, 2) }}$</p>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-400">
                            Valide du {{ $offer->valid_from->format('d/m/Y') }} au {{ $offer->valid_until->format('d/m/Y') }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Deal + Points combos --}}
            @if(!empty($matches))
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">
                    💰 Combos spéciaux + points
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(array_slice($matches, 0, 20) as $match)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-l-4 border-green-400 hover:shadow-md transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $match['deal']->product }}</span>
                                    @if($match['deal']->store)
                                        <span class="text-xs text-gray-400">🏪 {{ $match['deal']->store->name }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $match['deal']->category }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-600">{{ number_format($match['deal']->price, 2) }}$</p>
                                @if($match['deal']->savings() > 0)
                                    <p class="text-xs text-red-500">-{{ number_format($match['deal']->savings(), 2) }}$</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 bg-purple-50 dark:bg-purple-900/20 rounded p-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">🏆</span>
                                <span class="text-sm font-medium text-purple-700 dark:text-purple-300">
                                    {{ $match['loyalty_offer']->description }}
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-purple-600">{{ number_format($match['potential_points']) }} pts</p>
                                <p class="text-xs text-green-600">+ {{ number_format($match['value_in_dollars'], 2) }}$ de valeur</p>
                            </div>
                        </div>

                        <div class="mt-2 text-xs text-green-600 font-medium">
                            🔥 Cet achat chez {{ $match['deal']->store->name ?? 'ce magasin' }} te donne {{ number_format($match['potential_points']) }} pts {{ $match['program']?->name ?? 'de fidélité' }} en plus!
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @elseif($programs->isNotEmpty())
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <p class="text-4xl mb-4">🏆</p>
                <p class="text-gray-500 dark:text-gray-400">
                    Aucun combo spécial+points disponible. Les offres de fidélité ne correspondent pas aux spéciaux actuels.
                </p>
            </div>
            @endif

            <div class="mt-8 text-center">
                <a href="{{ route('grocery') }}" class="text-blue-500 hover:underline text-sm">
                    ← Retour aux circulaires
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
