<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Health Dashboard') }} — @yield('title', 'Dashboard')</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Styles --}}
    @if(app()->environment('testing') || app()->runningUnitTests())
        <link rel="stylesheet" href="https://test-styles.example.com/app.css">
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    {{-- Extra head --}}
    {{ $head ?? '' }}
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        {{-- Navigation --}}
        <nav class="border-b border-gray-100 bg-white dark:bg-gray-800 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-lg font-bold text-gray-800 dark:text-gray-200">
                            🏥 Health Dashboard
                        </a>
                        <div class="hidden sm:flex sm:space-x-6 sm:ml-10">
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Dashboard</a>
                            <a href="{{ route('grocery') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">🛒 Circulaires</a>
                            <a href="{{ route('grocery.price-intel') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">💹 Prix</a>
                            <a href="{{ route('grocery.meal-plan') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">🍽️ Repas</a>
                            <a href="{{ route('grocery.history') }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">📈 Historique</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Header --}}
        @isset($header)
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        {{-- Content --}}
        <main>
            {{ $slot }}
        </main>
    </div>
</body>
</html>
