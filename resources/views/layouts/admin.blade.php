<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex">
            {{-- Sidebar --}}
            <aside class="w-64 bg-gray-900 text-gray-300 min-h-screen flex-shrink-0">
                <div class="p-6">
                    <h2 class="text-white text-lg font-bold">Admin Panel</h2>
                    <p class="text-xs text-gray-500 mt-1">{{ config('app.name', 'Laravel') }}</p>
                </div>

                <nav class="mt-2">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        Users
                    </a>
                    <a href="{{ route('admin.transactions.index') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.transactions.*') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                        Transactions
                    </a>
                    <a href="{{ route('admin.stocks.index') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.stocks.*') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        Stocks
                    </a>
                    <a href="{{ route('admin.copyTrading.index') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.copyTrading.*') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                        Copy Trading
                    </a>
                    <a href="{{ route('admin.announcements.index') }}"
                       class="flex items-center gap-3 px-6 py-3 text-sm {{ request()->routeIs('admin.announcements.*') ? 'bg-gray-800 text-white border-r-2 border-indigo-500' : 'hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/></svg>
                        Announcements
                    </a>

                    <div class="border-t border-gray-700 mt-6 pt-4">
                        <a href="{{ route('portfolio.index') }}"
                           class="flex items-center gap-3 px-6 py-3 text-sm hover:bg-gray-800 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                            Back to App
                        </a>
                    </div>
                </nav>
            </aside>

            {{-- Main Content --}}
            <div class="flex-1 flex flex-col">
                {{-- Top Bar --}}
                <header class="bg-white shadow h-16 flex items-center justify-between px-6">
                    <h1 class="text-lg font-semibold text-gray-800">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </h1>
                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <span>{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600">Logout</button>
                        </form>
                    </div>
                </header>

                {{-- Flash Messages --}}
                <div class="px-6 mt-4">
                    @if(session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm mb-4">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm mb-4">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>

                {{-- Page Content --}}
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
