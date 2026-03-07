<x-admin-layout>
    <x-slot name="header">User: {{ $user->name }}</x-slot>

    <div class="space-y-6">
        {{-- User Info Card --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex items-start gap-6">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" class="h-16 w-16 rounded-full object-cover">
                @else
                    <span class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xl font-bold">{{ $user->initials() }}</span>
                @endif
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ $user->name }}
                        @if($user->is_admin)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 ml-2">Admin</span>
                        @endif
                        @if($user->is_banned)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 ml-2">Banned</span>
                        @endif
                    </h3>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <p class="text-sm text-gray-400 mt-1">Member since {{ $user->created_at->format('M d, Y') }}</p>
                </div>
                <div class="text-right">
                    @if(!$user->is_admin && $user->id !== Auth::id())
                        <form method="POST" action="{{ route('admin.users.toggleBan', $user) }}">
                            @csrf
                            @if($user->is_banned)
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-500">
                                    Unban User
                                </button>
                            @else
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-500"
                                        onclick="return confirm('Are you sure you want to ban this user?')">
                                    Ban User
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Balance</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($user->balance, 2) }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Total Trades</p>
                <p class="text-2xl font-bold text-gray-900">{{ $user->transactions_count }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Followers</p>
                <p class="text-2xl font-bold text-gray-900">{{ $user->followers_count }}</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500">Following</p>
                <p class="text-2xl font-bold text-gray-900">{{ $user->following_count }}</p>
            </div>
        </div>

        {{-- Balance Adjustment --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Adjust Balance</h3>
            <form method="POST" action="{{ route('admin.users.adjustBalance', $user) }}" class="flex items-end gap-4">
                @csrf
                <div>
                    <label for="amount" class="block text-xs font-medium text-gray-500 mb-1">Amount (use negative to deduct)</label>
                    <input type="number" name="amount" id="amount" step="0.01" required placeholder="100.00"
                           class="w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div class="flex-1">
                    <label for="reason" class="block text-xs font-medium text-gray-500 mb-1">Reason</label>
                    <input type="text" name="reason" id="reason" required placeholder="e.g. Bonus credit, correction..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-500">
                    Apply
                </button>
            </form>
        </div>

        {{-- Holdings --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Holdings</h3>
            @if($holdings->isEmpty())
                <p class="text-sm text-gray-500 text-center py-4">No holdings.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($holdings as $holding)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $holding->symbol }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $holding->company_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($holding->quantity, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($holding->average_cost, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Copy Trading Settings --}}
        @if($copyTradingSettings->isNotEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Copy Trading</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copying</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount/Trade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($copyTradingSettings as $setting)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <a href="{{ route('admin.users.show', $setting->trader) }}" class="text-indigo-600 hover:text-indigo-900">{{ $setting->trader->name }}</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($setting->amount_per_trade, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $setting->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $setting->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Recent Transactions --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h3>
            @if($recentTransactions->isEmpty())
                <p class="text-sm text-gray-500 text-center py-4">No transactions yet.</p>
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
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentTransactions as $tx)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $tx->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($tx->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $tx->stock->symbol }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($tx->quantity, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($tx->price_per_share, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">${{ number_format($tx->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
