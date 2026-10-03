@php use App\Support\GameBoard; @endphp
<x-layouts.app :title="config('app.game.name').' Game Scorer: Games'" active="games">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Games</h1>
            <p class="mt-1 text-stone-600">Every game you have finished, newest first.</p>
        </div>
        <a href="{{ route('game.create.view') }}" class="btn btn-primary"><x-icon name="plus" class="h-5 w-5" />New game</a>
    </div>

    @if (count($games) > 0)
        <ul class="mt-6 space-y-3">
            @foreach ($games as $__game)
                @php
                    $scores = $__game['game']['scores'] ?? [];
                    $winner = $__game['game']['winner'] ?? ($scores[0] ?? null);
                    $when = GameBoard::when(GameBoard::startedAt($__game));
                @endphp
                <li class="card-list">
                    <a href="{{ route('game.show', ['game_id' => $__game['id']]) }}" class="flex items-center gap-3 bg-stone-50/60 px-4 py-3.5 hover:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><x-icon name="trophy" class="h-5 w-5" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold">@if ($winner){{ $winner['player_name'] }} <span class="font-medium text-stone-600">won with</span> {{ $winner['score'] }}@else Game overview @endif</span>
                            @if ($when)<span class="block truncate text-xs text-stone-600">{{ $when }}</span>@endif
                        </span>
                        <span class="text-sm font-bold text-brand-700">Overview</span>
                        <span class="text-stone-400"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                    </a>
                    @foreach ($scores as $__closed_game_player)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="w-5 text-center text-sm font-bold text-stone-500">{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1 truncate font-semibold">{{ $__closed_game_player['player_name'] }}</span>
                            <span class="text-lg font-extrabold tabular-nums">{{ $__closed_game_player['score'] }}<span class="sr-only"> points</span></span>
                            <a href="{{ route('game.score-sheet', ['game_id' => $__game['id'], 'player_id' => $__closed_game_player['player_id']]) }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-brand-600">Score sheet<span class="sr-only"> for {{ $__closed_game_player['player_name'] }}</span></a>
                        </div>
                    @endforeach
                </li>
            @endforeach
        </ul>

        <nav class="mt-6 flex items-center justify-between gap-3" aria-label="Page pagination">
            @if ($pagination['previous'])
                <a href="{{ route('games', ['offset' => max($pagination['offset'] - $pagination['limit'], 0), 'limit' => $pagination['limit']]) }}" class="btn btn-secondary"><x-icon name="chevron-left" class="h-5 w-5" />Previous</a>
            @else
                <span class="btn btn-quiet pointer-events-none opacity-50" aria-disabled="true"><x-icon name="chevron-left" class="h-5 w-5" />Previous</span>
            @endif
            <p class="text-sm font-semibold text-stone-600">
                {{ $pagination['offset'] + 1 }} -
                {{ min($pagination['offset'] + $pagination['limit'], $pagination['total']) }}
                of
                {{ $pagination['total'] }}
            </p>
            @if ($pagination['next'])
                <a href="{{ route('games', ['offset' => $pagination['offset'] + $pagination['limit'], 'limit' => $pagination['limit']]) }}" class="btn btn-secondary">Next<x-icon name="chevron-right" class="h-5 w-5" /></a>
            @else
                <span class="btn btn-quiet pointer-events-none opacity-50" aria-disabled="true">Next<x-icon name="chevron-right" class="h-5 w-5" /></span>
            @endif
        </nav>
    @else
        <div class="card mt-6 text-center">
            <div class="flex justify-center"><x-art /></div>
            <h2 class="mt-4 text-xl font-extrabold tracking-tight">You haven&rsquo;t played any games.</h2>
            <p class="mt-1 text-stone-600">When you finish a game it shows up here, with who won.</p>
            <a href="{{ route('game.create.view') }}" class="btn btn-primary mt-5">Start a game</a>
        </div>
    @endif
</x-layouts.app>
