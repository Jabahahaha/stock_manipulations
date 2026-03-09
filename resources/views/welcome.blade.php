<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">

    {{-- Navigation --}}
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-800">StockTrader</span>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-700">Register</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <div class="flex items-center justify-center min-h-[calc(100vh-4rem)]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 mx-auto text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl mb-4">StockTrader</h1>
            <p class="text-lg text-gray-600 mb-8">
                Trade stocks with virtual money. Start with $10,000 and build your portfolio.
            </p>
            <div class="flex justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-gray-800 text-white rounded-md font-semibold text-sm uppercase tracking-widest hover:bg-gray-700">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 bg-gray-800 text-white rounded-md font-semibold text-sm uppercase tracking-widest hover:bg-gray-700">
                        Get Started
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 bg-white text-gray-800 border border-gray-300 rounded-md font-semibold text-sm uppercase tracking-widest hover:bg-gray-50">
                        Log In
                    </a>
                @endauth
            </div>
        </div>
    </div>

</body>
</html>
