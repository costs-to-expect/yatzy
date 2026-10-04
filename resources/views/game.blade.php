@php
    use App\Support\GameBoard;

    $config = config('app.game');
    $complete = $game['complete'] === 1;
    $leader = $standings[0] ?? null;
    $board = ['id' => $game['id']];
    $scores = $game['game']['scores'] ?? [];
    $winner = $game['game']['winner'] ?? ($scores[0] ?? null);

    $when = GameBoard::when($started);
    $played = $when === null ? null : (in_array($when, ['Today', 'Yesterday'], true) ? strtolower($when) : 'on '.$when);
@endphp
<x-layouts.app :title="$config['name'].' Game Scorer: Game'" :active="$complete ? 'games' : 'home'">
    <div class="mx-auto max-w-3xl">
        <a href="{{ $complete ? route('games') : route('home') }}" class="-ml-3 inline-flex min-h-11 items-center gap-1 rounded-xl pl-2 pr-3 text-sm font-bold text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-brand-600"><x-icon name="chevron-left" class="h-5 w-5" />{{ $complete ? 'All games' : 'Home' }}</a>

        <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Game overview</h1>
        <p class="mt-1 text-stone-600">
            @if ($complete)
                @if ($winner)<strong class="text-stone-900">{{ $winner['player_name'] }}</strong> won with {{ $winner['score'] }}.@endif
                @if ($played) Played {{ $played }}.@endif
            @else
                Tap a player to carry on scoring. Each player has a link to share, anyone who has it can score for them.
            @endif
        </p>

        @if (! $complete)
            <div class="card mt-6">
                <x-player-tiles :standings="$standings" :game-id="$game['id']" />
                <x-game-actions :game-id="$game['id']" />
            </div>
            <x-game-dialogs :board="$board" :standings="$standings" :share-tokens="$share_tokens" :leader="$leader" />
        @else
            <ul class="card-list mt-6">
                @foreach ($scores as $__score)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <span class="w-5 text-center text-sm font-bold text-stone-500">{{ $loop->iteration }}</span>
                        <x-avatar :name="$__score['player_name']" :index="$tones[$__score['player_id']] ?? 0" class="h-10 w-10 text-base" />
                        <span class="min-w-0 flex-1 truncate font-bold">{{ $__score['player_name'] }}@if ($winner && $winner['player_id'] === $__score['player_id']) <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800"><x-icon name="trophy" class="h-3.5 w-3.5" />Winner</span>@endif</span>
                        <span class="text-2xl font-extrabold tabular-nums">{{ $__score['score'] }}<span class="sr-only"> points</span></span>
                        <a href="{{ route('game.score-sheet', ['game_id' => $game['id'], 'player_id' => $__score['player_id']]) }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-600">Score sheet<span class="sr-only"> for {{ $__score['player_name'] }}</span></a>
                    </li>
                @endforeach
            </ul>

            <p class="mt-4 text-sm text-stone-600">We are working on a game log and statistics, as soon as we add them they will appear here.</p>
        @endif
    </div>
</x-layouts.app>
