<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Watchlist') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Search to Add --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Stock to Watchlist</h3>

                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="GET" action="{{ route('watchlist.index') }}" class="flex gap-4">
                    <input type="text" name="query" value="{{ $query }}" placeholder="Search stocks by name or symbol..."
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-700">
                        Search
                    </button>
                </form>
            </div>

            {{-- Search Results --}}
            @if($query && count($searchResults))
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
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($searchResults as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $result['1. symbol'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result['2. name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result['3. type'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                <form method="POST" action="{{ route('watchlist.store') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="symbol" value="{{ $result['1. symbol'] }}">
                                                    <input type="hidden" name="company_name" value="{{ $result['2. name'] }}">
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-medium">Add to Watchlist</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif($query && !count($searchResults))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500">No results found for "{{ $query }}".</div>
                </div>
            @endif

            {{-- Watchlist Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Your Watchlist</h3>

                    @if($items->isEmpty())
                        <p class="text-gray-500">Your watchlist is empty. Add stocks above to start tracking them.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Alert</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['symbol'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['company_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                @if($item['current_price'])
                                                    ${{ number_format($item['current_price'], 2) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                @if($item['change'] !== null)
                                                    <span class="{{ $item['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $item['change'] >= 0 ? '+' : '' }}{{ number_format($item['change'], 2) }}
                                                        ({{ $item['change_percent'] }})
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center">
                                                @if($item['alert_price'])
                                                    <div class="flex items-center justify-center gap-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['alert_triggered'] ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                                            {{ ucfirst($item['alert_condition']) }} ${{ number_format($item['alert_price'], 2) }}
                                                            @if($item['alert_triggered'])
                                                                — Triggered
                                                            @endif
                                                        </span>
                                                        <form method="POST" action="{{ route('watchlist.removeAlert', $item['id']) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-gray-400 hover:text-red-600 text-xs" title="Remove alert">&times;</button>
                                                        </form>
                                                    </div>
                                                @endif
                                                <form method="POST" action="{{ route('watchlist.updateAlert', $item['id']) }}" class="flex items-center justify-center gap-1 mt-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="alert_condition" class="rounded-md border-gray-300 text-xs py-1 px-1 w-20">
                                                        <option value="above" {{ $item['alert_condition'] === 'above' ? 'selected' : '' }}>Above</option>
                                                        <option value="below" {{ $item['alert_condition'] === 'below' ? 'selected' : '' }}>Below</option>
                                                    </select>
                                                    <input type="number" name="alert_price" step="0.01" min="0.01" placeholder="Price"
                                                        value="{{ $item['alert_price'] ?? '' }}"
                                                        class="rounded-md border-gray-300 text-xs py-1 px-2 w-24">
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">Set</button>
                                                </form>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-3">
                                                <a href="{{ route('stocks.index', ['symbol' => $item['symbol'], 'name' => $item['company_name']]) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 font-medium">Trade</a>
                                                <form method="POST" action="{{ route('watchlist.destroy', $item['id']) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
