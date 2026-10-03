<x-layouts.app :title="config('app.game.name').' Game Scorer: Players'" active="players">
    <div class="mx-auto max-w-3xl">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Players</h1>
                <p class="mt-1 text-stone-600">Everyone who can play in a game. Statistics are coming soon.</p>
            </div>
            <a href="{{ route('player.create.view') }}" class="btn btn-primary"><x-icon name="user-plus" class="h-5 w-5" />New player</a>
        </div>

        @if (count($players) > 0)
            <ul class="card-list mt-6">
                @foreach ($players as $__player)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <x-avatar :name="$__player['name']" :index="$tones[$__player['id']] ?? 0" class="h-10 w-10 text-base" />
                        <span class="min-w-0 flex-1 truncate font-bold">{{ $__player['name'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="card mt-6 text-center">
                <h2 class="text-xl font-extrabold tracking-tight">You haven&rsquo;t added any players yet.</h2>
                <p class="mt-1 text-stone-600">You need some players before you can start a game.</p>
                <a href="{{ route('player.create.view') }}" class="btn btn-primary mt-5">Add a player</a>
            </div>
        @endif
    </div>
</x-layouts.app>
