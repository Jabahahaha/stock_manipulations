<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Feed') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(Auth::user()->following()->count() === 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900">No traders followed</h3>
                        <p class="mt-1 text-sm text-gray-500">Follow some traders to see their activity in your feed.</p>
                        <div class="mt-4">
                            <a href="{{ route('traders.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
                                Discover Traders
                            </a>
                        </div>
                    </div>
                </div>
            @elseif($trades->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900">No trades yet</h3>
                        <p class="mt-1 text-sm text-gray-500">The traders you follow haven't made any trades yet.</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($trades as $trade)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4 sm:p-6 flex items-start gap-4">
                                {{-- Avatar --}}
                                <a href="{{ route('traders.show', $trade->user) }}" class="shrink-0">
                                    @if($trade->user->avatar)
                                        <img src="{{ Storage::url($trade->user->avatar) }}" alt="{{ $trade->user->name }}" class="h-10 w-10 rounded-full object-cover">
                                    @else
                                        <span class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">{{ $trade->user->initials() }}</span>
                                    @endif
                                </a>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900">
                                        <a href="{{ route('traders.show', $trade->user) }}" class="font-semibold hover:text-indigo-600">{{ $trade->user->name }}</a>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $trade->type === 'buy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} mx-1">
                                            {{ $trade->type === 'buy' ? 'bought' : 'sold' }}
                                        </span>
                                        <span class="font-medium">{{ number_format($trade->quantity, 2) }}</span>
                                        shares of
                                        <a href="{{ route('stocks.show', $trade->stock->symbol) }}" class="font-semibold hover:text-indigo-600">{{ $trade->stock->symbol }}</a>
                                        at <span class="font-medium">${{ number_format($trade->price_per_share, 2) }}</span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $trade->created_at->diffForHumans() }}</p>
                                </div>

                                {{-- Total --}}
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold text-gray-900">${{ number_format($trade->total_amount, 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ $trade->stock->company_name }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $trades->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
