<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Portfolio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Account Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500">Account Value</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($accountValue, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500">Portfolio Value</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalValue, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 {{ $totalGainLoss >= 0 ? 'border-green-500' : 'border-red-500' }}">
                    <p class="text-sm text-gray-500">Total Gain/Loss</p>
                    <p class="text-2xl font-bold {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totalGainLoss >= 0 ? '+' : '' }}${{ number_format($totalGainLoss, 2) }}
                        <span class="text-sm font-normal">({{ number_format($totalGainLossPercent, 2) }}%)</span>
                    </p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-300">
                    <p class="text-sm text-gray-500">Cash Balance</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($cashBalance, 2) }}</p>
                </div>
            </div>

            {{-- Holdings Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Holdings</h3>

                    @if($portfolioItems->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-semibold text-gray-900">No holdings yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Your portfolio is empty. Start building it by purchasing your first stock.</p>
                            <div class="mt-4">
                                <a href="{{ route('stocks.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
                                    Search & Buy Stocks
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gain/Loss</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($portfolioItems as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['symbol'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['company_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item['quantity'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($item['average_cost'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                @if($item['current_price'])
                                                    ${{ number_format($item['current_price'], 2) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                @if($item['current_value'])
                                                    ${{ number_format($item['current_value'], 2) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                @if($item['gain_loss'] !== null)
                                                    <span class="{{ $item['gain_loss'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $item['gain_loss'] >= 0 ? '+' : '' }}${{ number_format($item['gain_loss'], 2) }}
                                                        <br>
                                                        <span class="text-xs">({{ number_format($item['gain_loss_percent'], 2) }}%)</span>
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                <a href="{{ route('stocks.index', ['symbol' => $item['symbol'], 'name' => $item['company_name']]) }}"
                                                    class="text-indigo-600 hover:text-indigo-900 font-medium">Trade</a>
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
