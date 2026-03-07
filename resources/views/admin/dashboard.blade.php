<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="space-y-6">
        {{-- Metrics Row 1: Users --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">New Users (7d)</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($newUsersThisWeek) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                <p class="text-sm text-gray-500">Banned Users</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($bannedUsers) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Total Follows</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalFollows) }}</p>
            </div>
        </div>

        {{-- Metrics Row 2: Trading --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Total Trades</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalTrades) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Total Volume</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($totalVolume, 2) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500">Volume (7d)</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($volumeThisWeek, 2) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Trades Today</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($tradesToday) }}</p>
            </div>
        </div>

        {{-- Metrics Row 3: Platform --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Stocks Tracked</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalStocks) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Active Copy Traders</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($activeCopyTraders) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Trades Today</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($tradesToday) }}</p>
            </div>
        </div>

        {{-- Recent Trades --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Trades</h3>

                @if($recentTrades->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">No trades yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentTrades as $trade)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $trade->created_at->format('M d, H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $trade->user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trade->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($trade->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $trade->stock->symbol }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($trade->quantity, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($trade->price_per_share, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($trade->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
