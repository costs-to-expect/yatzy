@props(['board', 'standings', 'shareTokens', 'leader' => null])
{{-- The dialogs for a game in progress: share the public links, finish, remove a player and delete --}}
@php
    $game = config('app.game');
    $unfinished = collect($standings)->contains(fn ($player) => $player['turns'] < $game['turns']);
@endphp
<x-sheet id="share-dialog" title="Share score sheets" subtitle="Each player has their own link and anyone who has it can score for them, so only send it to that player.">
    <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
        @foreach ($standings as $player)
            @php($token = $shareTokens[$board['id']][$player['id']] ?? null)
            <li class="flex items-center gap-3 p-3">
                <x-avatar :name="$player['name']" :index="$player['tone']" class="h-10 w-10 text-base" />
                <span class="min-w-0 flex-1 truncate font-bold">{{ $player['name'] }}</span>
                @if ($token)
                    <button type="button" data-copy="{{ route('public.score-sheet', ['token' => $token]) }}" @if ($loop->first) data-autofocus @endif
                            class="inline-flex min-h-11 min-w-32 items-center justify-center gap-1.5 rounded-xl bg-brand-50 px-3.5 py-2.5 text-sm font-bold text-brand-800 hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-brand-600">
                        <x-icon name="link" class="h-4 w-4" /><span data-copy-label>Copy link</span>
                    </button>
                @else
                    <span class="text-xs text-stone-600">No link</span>
                @endif
            </li>
        @endforeach
    </ul>
    <button type="button" data-dialog-close class="btn btn-quiet btn-block mt-4">Done</button>
</x-sheet>

<x-sheet id="finish-dialog" title="Finish this game?"
         :subtitle="($leader && $leader['leader'] ? $leader['name'].' is in the lead with '.$leader['score'].'. ' : '').'Scores are locked once the game is finished.'">
    <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
        @foreach ($standings as $rank => $player)
            <li class="flex items-center gap-3 px-3 py-2.5">
                <span class="w-5 text-center text-sm font-bold text-stone-500">{{ $rank + 1 }}</span>
                <x-avatar :name="$player['name']" :index="$player['tone']" class="h-8 w-8 text-sm" />
                <span class="flex-1 font-semibold">{{ $player['name'] }}</span>
                <span class="text-lg font-extrabold tabular-nums">{{ $player['score'] }}</span>
            </li>
        @endforeach
    </ul>

    @if ($unfinished)
        <x-alert type="warning" class="mt-3">Not everyone has scored all {{ $game['turns'] }} turns yet.</x-alert>
    @endif

    <div class="mt-5 grid gap-2.5">
        <form action="{{ route('game.complete.action', ['game_id' => $board['id']]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-block">Finish game</button>
        </form>
        <form action="{{ route('game.complete.play-again.action', ['game_id' => $board['id']]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-soft btn-block">Finish and play again</button>
        </form>
        <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block">Keep playing</button>
    </div>
</x-sheet>

<x-sheet id="remove-dialog" title="Remove a player" subtitle="Their score sheet and share link are removed straight away, it can&rsquo;t be undone.">
    <ul class="divide-y divide-stone-100 rounded-2xl ring-1 ring-stone-200">
        @foreach ($standings as $player)
            <li class="flex items-center gap-3 p-3">
                <x-avatar :name="$player['name']" :index="$player['tone']" class="h-10 w-10 text-base" />
                <span class="min-w-0 flex-1 truncate font-bold">{{ $player['name'] }}</span>
                <form action="{{ route('game.player.delete', ['game_id' => $board['id'], 'player_id' => $player['id']]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger-soft min-h-11 px-4 py-2" aria-label="Remove {{ $player['name'] }} from the game">Remove</button>
                </form>
            </li>
        @endforeach
    </ul>
    <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block mt-4">Cancel</button>
</x-sheet>

<x-sheet id="delete-dialog" title="Delete this game?" subtitle="This removes the game and every score in it. It can&rsquo;t be undone.">
    <div class="grid gap-2.5">
        <form action="{{ route('game.delete.action', ['game_id' => $board['id']]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger btn-block">Delete game</button>
        </form>
        <button type="button" data-dialog-close data-autofocus class="btn btn-quiet btn-block">Cancel</button>
    </div>
</x-sheet>
