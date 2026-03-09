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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                     x-data="{
                        price: {{ $quote['price'] }},
                        change: {{ $quote['change'] }},
                        changePercent: '{{ $quote['change_percent'] }}',
                        justUpdated: false,
                        interval: null,
                        async refreshQuote() {
                            try {
                                const res = await fetch('/stocks/{{ $symbol }}/quote');
                                if (!res.ok) return;
                                const data = await res.json();
                                this.price = data.price;
                                this.change = data.change;
                                this.changePercent = data.change_percent;
                                this.justUpdated = true;
                                setTimeout(() => this.justUpdated = false, 3000);
                                document.querySelectorAll('input[name=price]').forEach(el => el.value = data.price);
                            } catch (e) {}
                        },
                        init() {
                            this.interval = setInterval(() => this.refreshQuote(), 30000);
                        },
                        destroy() { clearInterval(this.interval); }
                     }">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $quote['symbol'] }}</h3>
                                <p class="text-gray-500">{{ $quote['company_name'] }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-gray-900">$<span x-text="price.toFixed(2)">{{ number_format($quote['price'], 2) }}</span></p>
                                    <p class="text-sm" :class="change >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <span x-text="(change >= 0 ? '+' : '') + change.toFixed(2)">{{ ($quote['change'] >= 0 ? '+' : '') . number_format($quote['change'], 2) }}</span>
                                        (<span x-text="changePercent">{{ $quote['change_percent'] }}</span>)
                                    </p>
                                    <p x-show="justUpdated" x-transition.opacity class="text-xs text-indigo-500 mt-1">Price updated</p>
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

                {{-- Price History Chart --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                     x-data="{
                        range: '3M',
                        loading: true,
                        chart: null,
                        async fetchData(r) {
                            this.range = r;
                            this.loading = true;
                            try {
                                if (!window.ApexCharts) await window.loadApexCharts();
                                const res = await fetch(`/stocks/{{ $symbol }}/history?range=${r}`);
                                if (!res.ok) { this.loading = false; return; }
                                const data = await res.json();
                                const series = [{ data }];
                                if (this.chart) {
                                    this.chart.updateSeries(series);
                                } else {
                                    this.chart = new ApexCharts(this.$refs.chart, {
                                        chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
                                        series,
                                        xaxis: { type: 'datetime' },
                                        yaxis: { labels: { formatter: v => '$' + v.toFixed(2) } },
                                        tooltip: { x: { format: 'MMM dd, yyyy' }, y: { formatter: v => '$' + v.toFixed(2) } },
                                        dataLabels: { enabled: false },
                                        stroke: { curve: 'smooth', width: 2 },
                                        colors: ['#6366f1'],
                                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                                        grid: { borderColor: '#f1f1f1' },
                                    });
                                    this.chart.render();
                                }
                            } catch (e) {}
                            this.loading = false;
                        },
                        init() { this.fetchData(this.range); }
                     }">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-gray-900">Price History</h4>
                            <div class="flex gap-1">
                                <template x-for="r in ['1W', '1M', '3M', '1Y']" :key="r">
                                    <button @click="fetchData(r)"
                                        :class="range === r ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                        class="px-3 py-1 rounded-md text-xs font-medium transition"
                                        x-text="r"></button>
                                </template>
                            </div>
                        </div>
                        <div x-ref="chart" x-show="!loading || chart"></div>
                        <div x-show="loading && !chart" class="flex items-center justify-center h-[300px]">
                            <svg class="animate-spin h-8 w-8 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
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
