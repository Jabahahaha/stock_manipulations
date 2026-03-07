<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Traders') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Search --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('traders.index') }}" class="flex gap-4">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search traders by name..."
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-indigo-500">
                        Search
                    </button>
                </form>
            </div>

            {{-- Traders List --}}
            @if($traders->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                        <h3 class="mt-3 text-sm font-semibold text-gray-900">No traders found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try a different search term.</p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($traders as $trader)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex items-center gap-4">
                                @if($trader->avatar)
                                    <img src="{{ Storage::url($trader->avatar) }}" alt="{{ $trader->name }}" class="h-12 w-12 rounded-full object-cover">
                                @else
                                    <span class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-sm font-bold">{{ $trader->initials() }}</span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('traders.show', $trader) }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-600">
                                        {{ $trader->name }}
                                    </a>
                                    <div class="flex gap-4 text-xs text-gray-500 mt-1">
                                        <span>{{ $trader->followers_count }} {{ Str::plural('follower', $trader->followers_count) }}</span>
                                        <span>{{ $trader->following_count }} following</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('traders.show', $trader) }}" class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    View Profile
                                </a>
                                @if(Auth::user()->isFollowing($trader))
                                    <form method="POST" action="{{ route('traders.unfollow', $trader) }}" class="flex-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-3 py-2 border border-red-300 rounded-md text-xs font-semibold text-red-600 hover:bg-red-50">
                                            Unfollow
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('traders.follow', $trader) }}" class="flex-1">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-2 bg-indigo-600 rounded-md text-xs font-semibold text-white hover:bg-indigo-500">
                                            Follow
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $traders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
