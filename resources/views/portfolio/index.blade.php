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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Account Value</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($accountValue, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Portfolio Value</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalValue, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total Gain/Loss</p>
                    <p class="text-2xl font-bold {{ $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totalGainLoss >= 0 ? '+' : '' }}${{ number_format($totalGainLoss, 2) }}
                        <span class="text-sm font-normal">({{ number_format($totalGainLossPercent, 2) }}%)</span>
                    </p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Cash Balance</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($cashBalance, 2) }}</p>
                </div>
            </div>

            {{-- Holdings Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Holdings</h3>

                    @if($portfolioItems->isEmpty())
                        <p class="text-gray-500">You don't own any stocks yet. <a href="{{ route('stocks.index') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Search and buy stocks</a> to get started.</p>
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
