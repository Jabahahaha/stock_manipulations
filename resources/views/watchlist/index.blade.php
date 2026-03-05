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

                @if($errors->any())
                    <div class="mb-4">
                        <x-notification type="error">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </x-notification>
                    </div>
                @endif

                <form method="GET" action="{{ route('watchlist.index') }}" class="flex gap-4">
                    <input type="text" name="query" value="{{ $query }}" placeholder="Search stocks by name or symbol..."
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
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
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Your Watchlist</h3>
                        <span id="refresh-timer" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500"></span>
                    </div>

                    @if($items->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-semibold text-gray-900">No stocks watched</h3>
                            <p class="mt-1 text-sm text-gray-500">Search for stocks above and add them to keep an eye on prices and set alerts.</p>
                        </div>
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
                                        <tr data-id="{{ $item['id'] }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['symbol'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['company_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right" data-field="price">
                                                @if($item['current_price'])
                                                    ${{ number_format($item['current_price'], 2) }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right" data-field="change">
                                                @if($item['change'] !== null)
                                                    <span class="{{ $item['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ $item['change'] >= 0 ? '+' : '' }}{{ number_format($item['change'], 2) }}
                                                        ({{ $item['change_percent'] }})
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-center" data-field="alert">
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

    @if(!$items->isEmpty())
    @push('scripts')
    <script>
        (function () {
            const INTERVAL = 60;
            let countdown = INTERVAL;
            const timerEl = document.getElementById('refresh-timer');

            function formatNumber(n) {
                return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function updatePrices() {
                fetch('{{ route("watchlist.prices") }}')
                    .then(r => r.json())
                    .then(items => {
                        items.forEach(item => {
                            const row = document.querySelector(`tr[data-id="${item.id}"]`);
                            if (!row) return;

                            // Update price
                            const priceCell = row.querySelector('[data-field="price"]');
                            if (priceCell) {
                                priceCell.innerHTML = item.current_price !== null
                                    ? '$' + formatNumber(item.current_price)
                                    : '<span class="text-gray-400">N/A</span>';
                            }

                            // Update change
                            const changeCell = row.querySelector('[data-field="change"]');
                            if (changeCell) {
                                if (item.change !== null) {
                                    const color = item.change >= 0 ? 'text-green-600' : 'text-red-600';
                                    const sign = item.change >= 0 ? '+' : '';
                                    changeCell.innerHTML = `<span class="${color}">${sign}${formatNumber(item.change)} (${item.change_percent})</span>`;
                                } else {
                                    changeCell.innerHTML = '<span class="text-gray-400">N/A</span>';
                                }
                            }

                            // Update alert badge if triggered
                            if (item.alert_triggered) {
                                const alertCell = row.querySelector('[data-field="alert"]');
                                if (alertCell) {
                                    const badge = alertCell.querySelector('.rounded-full');
                                    if (badge && !badge.classList.contains('bg-yellow-100')) {
                                        badge.classList.remove('bg-blue-100', 'text-blue-800');
                                        badge.classList.add('bg-yellow-100', 'text-yellow-800');
                                        if (!badge.textContent.includes('Triggered')) {
                                            badge.textContent = badge.textContent.trim() + ' — Triggered';
                                        }
                                    }
                                }
                            }
                        });

                        countdown = INTERVAL;
                    })
                    .catch(() => { countdown = INTERVAL; });
            }

            setInterval(() => {
                countdown--;
                if (timerEl) {
                    timerEl.textContent = 'Next update in ' + countdown + 's';
                }
                if (countdown <= 0) {
                    updatePrices();
                }
            }, 1000);

            if (timerEl) {
                timerEl.textContent = 'Next update in ' + countdown + 's';
            }
        })();
    </script>
    @endpush
    @endif
</x-app-layout>
