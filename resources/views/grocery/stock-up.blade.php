<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🚨 Alertes Stock Up — Prix au plus bas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-6 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 flex justify-between items-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Alertes déclenchées quand un prix est dans les 5% du plus bas historique (6 mois).
                </p>
                <a href="{{ route('grocery.stock-up.trigger') }}" 
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                    🔄 Vérifier maintenant
                </a>
            </div>

            @if($alerts->isEmpty())
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <p class="text-5xl mb-4">✅</p>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">Aucune alerte pour le moment</p>
                    <p class="text-sm text-gray-400 mt-2">
                        Les prix actuels ne sont pas encore au plus bas historique.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($alerts as $alert)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 border-l-4 border-red-400 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    🏪 {{ $alert->store->name ?? 'Inconnu' }}
                                </span>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-1">
                                    {{ $alert->product }}
                                </h3>
                            </div>
                            <span class="text-2xl">🚨</span>
                        </div>

                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($alert->price, 2) }}$
                            </span>
                            <span class="text-sm text-gray-400 line-through">
                                {{ number_format($alert->historical_low_price, 2) }}$
                            </span>
                        </div>

                        <div class="text-sm space-y-1">
                            <p class="text-gray-500 dark:text-gray-400">
                                📉 Plus bas historique: <strong class="text-red-500">{{ number_format($alert->historical_low_price, 2) }}$</strong>
                            </p>
                            <p class="text-gray-500 dark:text-gray-400">
                                💰 Économie: <strong class="text-green-600">{{ number_format($alert->historical_low_price - $alert->price, 2) }}$</strong>
                            </p>
                            <p class="text-gray-500 dark:text-gray-400">
                                ⏰ Déclenché: {{ $alert->triggered_at?->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        @if($alert->notified_at)
                            <p class="mt-3 text-xs text-blue-500">✓ Notification envoyée {{ $alert->notified_at->diffForHumans() }}</p>
                        @else
                            <p class="mt-3 text-xs text-yellow-500">⏳ Notification en attente</p>
                        @endif
                    </div>
                    @endforeach
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
