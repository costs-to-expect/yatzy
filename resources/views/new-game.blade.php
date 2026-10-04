@php
    $game = config('app.game');
    $player_errors = $errors['players']['errors'] ?? [];
    $picked = collect(old('players', []))->all();
@endphp
<x-layouts.app :title="$game['name'].' Game Scorer: New Game'" active="games">
    <div class="mx-auto max-w-xl">
        <div class="card sm:p-8">
            <h1 class="text-3xl font-extrabold tracking-tight">Start a new game</h1>
            <p class="mt-1.5 text-stone-600">Select the players, we will then generate score sheets for each of them.</p>

            @if (count($players) > 0)
                <form action="{{ route('game.create.action') }}" method="POST" data-picker data-picker-min="{{ $game['min_players'] }}" data-picker-label="Start game with" class="mt-6">
                    @csrf
                    <x-player-picker :players="$players" :tones="$tones" :picked="$picked" legend="Players in the game" />

                    @if ($player_errors !== [])
                        <p class="form-error" role="alert"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                    @endif

                    <input type="hidden" name="name" value="{{ $game['game_name'] }}">
                    <input type="hidden" name="description" value="{{ $game['game_description'] }}">
                    <button type="submit" data-picker-submit class="btn btn-primary btn-block mt-6">Start game</button>
                </form>
            @else
                <x-alert type="info" title="Oops, no players!" class="mt-6">
                    <p>Before you can start a game you need to add your players.</p>
                    <p><a href="{{ route('player.create.view') }}" class="text-link">Add a player</a>, as soon as you do you can start a game.</p>
                </x-alert>
            @endif
        </div>
    </div>
</x-layouts.app>
