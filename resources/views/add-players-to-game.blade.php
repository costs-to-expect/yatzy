@php
    $player_errors = $errors['players']['errors'] ?? [];
@endphp
<x-layouts.app :title="config('app.game.name').' Game Scorer: Add Players to Game'" active="home">
    <div class="mx-auto max-w-xl">
        <div class="card sm:p-8">
            <h1 class="text-3xl font-extrabold tracking-tight">Add players</h1>
            <p class="mt-1.5 text-stone-600">Select any additional players to add to the game, each gets their own score sheet and share link.</p>

            @if (count($game_players) > 0)
                <h2 class="mt-6 text-sm font-extrabold uppercase tracking-wider text-stone-600">Playing now</h2>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($game_players as $__game_player)
                        <li class="inline-flex min-h-9 items-center gap-2 rounded-full bg-stone-100 px-3.5 text-sm font-bold text-stone-800">{{ $__game_player }}</li>
                    @endforeach
                </ul>
            @endif

            @if (count($players) > 0)
                <form action="{{ route('game.add-players.action', ['game_id' => $game_id]) }}" method="POST" class="mt-6">
                    @csrf
                    <h2 class="mb-2 text-sm font-extrabold uppercase tracking-wider text-stone-600">Add to the game</h2>
                    <x-player-picker :players="$players" :tones="$tones" legend="Players to add" />

                    @if ($player_errors !== [])
                        <p class="form-error" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                    @endif

                    <button type="submit" class="btn btn-primary btn-block mt-6">Add players</button>
                </form>
            @else
                <x-alert type="info" class="mt-6">
                    There are no more players to add to the game, check the <a href="{{ route('players') }}" class="text-link">players</a> list, you might need to add one.
                </x-alert>
            @endif

            <a href="{{ route('home') }}" class="btn btn-quiet btn-block mt-3">Back to the game</a>
        </div>
    </div>
</x-layouts.app>
