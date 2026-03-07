<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $trader->name }}'s Profile
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Profile Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-6">
                    @if($trader->avatar)
                        <img src="{{ Storage::url($trader->avatar) }}" alt="{{ $trader->name }}" class="h-20 w-20 rounded-full object-cover">
                    @else
                        <span class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-2xl font-bold">{{ $trader->initials() }}</span>
                    @endif
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $trader->name }}</h3>
                        <p class="text-sm text-gray-500">Member since {{ $trader->created_at->format('M Y') }}</p>
                        <div class="flex gap-6 mt-2 text-sm text-gray-600">
                            <span><strong>{{ $trader->followers_count }}</strong> {{ Str::plural('follower', $trader->followers_count) }}</span>
                            <span><strong>{{ $trader->following_count }}</strong> following</span>
                        </div>
                    </div>
                    <div>
                        @if($isFollowing)
                            <form method="POST" action="{{ route('traders.unfollow', $trader) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-6 py-2 border border-red-300 rounded-md text-sm font-semibold text-red-600 hover:bg-red-50">
                                    Unfollow
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('traders.follow', $trader) }}">
                                @csrf
                                <button type="submit" class="px-6 py-2 bg-indigo-600 rounded-md text-sm font-semibold text-white hover:bg-indigo-500">
                                    Follow
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Copy Trading --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Copy Trading</h3>

                @if($copyTradingSetting)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">
                                You are copying {{ $trader->name }}'s trades at
                                <strong>${{ number_format($copyTradingSetting->amount_per_trade, 2) }}</strong> per trade.
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Status: <span class="{{ $copyTradingSetting->is_active ? 'text-green-600' : 'text-gray-500' }}">{{ $copyTradingSetting->is_active ? 'Active' : 'Paused' }}</span>
                            </p>
                        </div>
                        <form method="POST" action="{{ route('copy-trading.destroy', $trader) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 border border-red-300 rounded-md text-sm font-semibold text-red-600 hover:bg-red-50">
                                Stop Copying
                            </button>
                        </form>
                    </div>
                    {{-- Update amount form --}}
                    <form method="POST" action="{{ route('copy-trading.store', $trader) }}" class="mt-4 flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label for="amount_per_trade" class="block text-xs font-medium text-gray-500 mb-1">Update amount per trade ($)</label>
                            <input type="number" name="amount_per_trade" id="amount_per_trade" step="0.01" min="1"
                                   value="{{ $copyTradingSetting->amount_per_trade }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 rounded-md text-sm font-semibold text-white hover:bg-indigo-500">
                            Update
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-500 mb-4">Automatically mirror {{ $trader->name }}'s trades. Set a dollar amount to invest per trade.</p>
                    <form method="POST" action="{{ route('copy-trading.store', $trader) }}" class="flex items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label for="amount_per_trade" class="block text-xs font-medium text-gray-500 mb-1">Amount per trade ($)</label>
                            <input type="number" name="amount_per_trade" id="amount_per_trade" step="0.01" min="1" placeholder="100.00"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 rounded-md text-sm font-semibold text-white hover:bg-indigo-500">
                            Start Copying
                        </button>
                    </form>
                @endif

                @error('amount_per_trade')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Recent Trades --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Trades</h3>

                @if($recentTrades->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">This trader hasn't made any trades yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentTrades as $trade)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $trade->created_at->format('M d, Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trade->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($trade->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $trade->stock->symbol }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($trade->quantity, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($trade->price_per_share, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
