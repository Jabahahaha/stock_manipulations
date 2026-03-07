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
