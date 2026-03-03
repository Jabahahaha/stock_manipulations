<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Watchlist') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Add to Watchlist --}}
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

                <form method="POST" action="{{ route('watchlist.store') }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <label for="symbol" class="block text-sm font-medium text-gray-700">Symbol</label>
                        <input type="text" name="symbol" id="symbol" required placeholder="e.g. AAPL"
                            class="mt-1 block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            style="text-transform: uppercase;">
                    </div>
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" name="company_name" id="company_name" required placeholder="e.g. Apple Inc"
                            class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Add
                    </button>
                </form>
            </div>

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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                @if($item['alert_price'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['alert_triggered'] ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                                        {{ ucfirst($item['alert_condition']) }} ${{ number_format($item['alert_price'], 2) }}
                                                        @if($item['alert_triggered'])
                                                            — Triggered
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">None</span>
                                                @endif
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
