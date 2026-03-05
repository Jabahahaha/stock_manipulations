<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Search Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('stocks.index') }}" class="flex gap-4">
                        <input type="text" name="query" value="{{ $query }}" placeholder="Search stocks by name or symbol..."
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            {{-- Search Results --}}
            @if($query && count($results))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Search Results</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($results as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $result['1. symbol'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result['2. name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result['3. type'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result['4. region'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('stocks.index', ['symbol' => $result['1. symbol'], 'name' => $result['2. name'], 'query' => $query]) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 font-medium">View Quote</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif($query && !count($results))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500">No results found for "{{ $query }}".</div>
                </div>
            @endif

            {{-- API error --}}
            @if($symbol && !$quote)
                <div class="bg-yellow-50 border border-yellow-200 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-yellow-800">Could not fetch quote for "{{ $symbol }}". This symbol may not be supported (only US stocks are available on the free tier), or the API may be temporarily unavailable.</div>
                </div>
            @endif

            {{-- Live Quote + Buy/Sell --}}
            @if($quote)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $quote['symbol'] }}</h3>
                                <p class="text-gray-500">{{ $quote['company_name'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-gray-900">${{ number_format($quote['price'], 2) }}</p>
                                <p class="text-sm {{ $quote['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $quote['change'] >= 0 ? '+' : '' }}{{ number_format($quote['change'], 2) }}
                                    ({{ $quote['change_percent'] }})
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Buy Form --}}
                            <div class="border border-green-200 bg-green-50/30 rounded-lg p-4" x-data="{ qty: 0, price: {{ $quote['price'] }} }">
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

                            {{-- Sell Form --}}
                            <div class="border border-red-200 bg-red-50/30 rounded-lg p-4">
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
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
