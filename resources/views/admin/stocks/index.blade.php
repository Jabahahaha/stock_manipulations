<x-admin-layout>
    <x-slot name="header">Stocks</x-slot>

    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Total Stocks Traded</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStocks) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Most Traded</p>
                <p class="text-2xl font-bold text-gray-900">{{ $mostTraded?->symbol ?? '—' }}</p>
                @if($mostTraded)
                    <p class="text-xs text-gray-400">{{ number_format($mostTraded->transactions_count) }} trades</p>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500">Highest Volume</p>
                <p class="text-2xl font-bold text-gray-900">{{ $highestVolume?->symbol ?? '—' }}</p>
                @if($highestVolume && $highestVolume->total_volume)
                    <p class="text-xs text-gray-400">${{ number_format($highestVolume->total_volume, 2) }}</p>
                @endif
            </div>
        </div>

        {{-- Search --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <form method="GET" action="{{ route('admin.stocks.index') }}" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by symbol or company name..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
                    Search
                </button>
                <a href="{{ route('admin.stocks.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-50">
                    Reset
                </a>
            </form>
        </div>

        {{-- Stocks Table --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if($stocks->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">No stocks found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Trades</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Volume</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($stocks as $stock)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $stock->symbol }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $stock->company_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($stock->transactions_count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($stock->total_volume ?? 0, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('admin.stocks.show', $stock) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $stocks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
