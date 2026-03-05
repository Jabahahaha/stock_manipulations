<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $symbol }}
                    <span class="text-base font-normal text-gray-500">{{ $companyName }}</span>
                </h2>
            </div>
            <a href="{{ route('stocks.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                &larr; Back to Search
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- API error --}}
            @if(!$quote)
                <div class="bg-yellow-50 border border-yellow-200 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-yellow-800">
                        Could not fetch quote for "{{ $symbol }}". This symbol may not be supported (only US stocks are available on the free tier), or the API may be temporarily unavailable.
                    </div>
                </div>
            @else
                {{-- Quote Header --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $quote['symbol'] }}</h3>
                                <p class="text-gray-500">{{ $quote['company_name'] }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-gray-900">${{ number_format($quote['price'], 2) }}</p>
                                    <p class="text-sm {{ $quote['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $quote['change'] >= 0 ? '+' : '' }}{{ number_format($quote['change'], 2) }}
                                        ({{ $quote['change_percent'] }})
                                    </p>
                                </div>
                                {{-- Watchlist Toggle --}}
                                @if($isWatched)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12.585l-4.243 2.23.81-4.726L3.134 6.77l4.745-.69L10 1.5l2.121 4.58 4.745.69-3.433 3.319.81 4.726z"/>
                                        </svg>
                                        Watching
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('watchlist.store') }}">
                                        @csrf
                                        <input type="hidden" name="symbol" value="{{ $quote['symbol'] }}">
                                        <input type="hidden" name="company_name" value="{{ $quote['company_name'] }}">
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-indigo-100 hover:text-indigo-800 transition">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 12.585l-4.243 2.23.81-4.726L3.134 6.77l4.745-.69L10 1.5l2.121 4.58 4.745.69-3.433 3.319.81 4.726z"/>
                                            </svg>
                                            Add to Watchlist
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Buy / Sell Forms --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Buy Form --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="border border-green-200 bg-green-50/30 rounded-lg p-6" x-data="{ qty: 0, price: {{ $quote['price'] }} }">
                            <h4 class="text-lg font-semibold text-green-700 mb-3">Buy</h4>
                            <form method="POST" action="{{ route('stocks.buy') }}">
                                @csrf
                                <input type="hidden" name="symbol" value="{{ $quote['symbol'] }}">
                                <input type="hidden" name="company_name" value="{{ $quote['company_name'] }}">
                                <input type="hidden" name="price" value="{{ $quote['price'] }}">

                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" name="quantity" min="0.000001" step="any" required
                                        x-model.number="qty"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <p class="text-sm text-gray-500 mb-1">
                                    Your balance: <span class="font-semibold">${{ number_format(auth()->user()->balance, 2) }}</span>
                                </p>
                                <p class="text-sm text-gray-500 mb-3" x-show="qty > 0" x-cloak>
                                    Est. total: <span class="font-semibold" x-text="'$' + (qty * price).toFixed(2)"></span>
                                </p>

                                @error('balance')
                                    <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                                @enderror

                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-green-500">
                                    Buy
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Sell Form --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="border border-red-200 bg-red-50/30 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-red-700 mb-3">Sell</h4>

                            @if($currentQuantity > 0)
                                <form method="POST" action="{{ route('stocks.sell') }}">
                                    @csrf
                                    <input type="hidden" name="symbol" value="{{ $quote['symbol'] }}">
                                    <input type="hidden" name="company_name" value="{{ $quote['company_name'] }}">
                                    <input type="hidden" name="price" value="{{ $quote['price'] }}">

                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                        <input type="number" name="quantity" min="0.000001" max="{{ $currentQuantity }}" step="any" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>

                                    <p class="text-sm text-gray-500 mb-3">
                                        You own: <span class="font-semibold">{{ $currentQuantity }} shares</span>
                                    </p>

                                    @error('quantity')
                                        <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                                    @enderror

                                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-red-500">
                                        Sell
                                    </button>
                                </form>
                            @else
                                <p class="text-gray-500">You don't own any shares of {{ $quote['symbol'] }}.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
