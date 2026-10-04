@props(['standings', 'gameId'])
{{-- Who is playing, best score first: a tile per player that opens their score sheet. Two and four players sit best
     in two columns, anything else in three. --}}
@php($game = config('app.game'))
<div {{ $attributes->class(['grid gap-3', 'sm:grid-cols-2' => in_array(count($standings), [2, 4], true), 'sm:grid-cols-3' => ! in_array(count($standings), [2, 4], true)]) }}>
    @foreach ($standings as $player)
        <a href="{{ route('game.score-sheet', ['game_id' => $gameId, 'player_id' => $player['id']]) }}"
           @class([
               'group relative grid grid-cols-[auto_1fr_auto_auto] items-center gap-x-3.5 rounded-2xl p-4 shadow-card transition hover:shadow-lift focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:transition-none sm:flex sm:flex-col sm:gap-x-0 sm:px-4 sm:py-6 sm:text-center',
               'bg-gradient-to-b from-white to-brand-50 ring-2 ring-brand-300 hover:ring-brand-400' => $player['leader'],
               'bg-white ring-1 ring-stone-200 hover:ring-brand-300' => ! $player['leader'],
           ])>
            <span class="relative row-span-2 h-14 w-14 shrink-0 sm:order-1 sm:row-span-1 sm:h-19 sm:w-19">
                <x-ring :fraction="$player['progress']" class="absolute inset-0 h-full w-full" />
                <x-avatar :name="$player['name']" :index="$player['tone']" class="absolute inset-1.25 text-xl sm:inset-1.75 sm:text-2xl" />
                @if ($player['leader'])
                    <span class="absolute -right-1.5 -top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-amber-950 ring-2 ring-white"><x-icon name="crown" class="h-3.5 w-3.5" /><span class="sr-only">Leading</span></span>
                @endif
            </span>
            <span class="col-start-2 truncate text-base font-bold sm:order-2 sm:mt-3 sm:text-lg">{{ $player['name'] }}</span>
            <span class="col-start-2 text-xs text-stone-600 sm:order-4 sm:mt-1">{{ $player['turns'] >= $game['turns'] ? 'All '.$game['turns'].' turns played' : $player['turns'].' of '.$game['turns'].' turns' }}</span>
            <span class="col-start-3 row-span-2 row-start-1 text-3xl font-extrabold tabular-nums sm:order-3 sm:mt-0.5 sm:text-4xl">{{ $player['score'] }}</span>
            <span class="col-start-4 row-span-2 row-start-1 text-stone-400 sm:hidden"><x-icon name="chevron-right" class="h-5 w-5" /></span>
            <span class="mt-5 hidden w-full rounded-xl bg-brand-50 py-2.5 text-sm font-bold text-brand-800 transition group-hover:bg-brand-700 group-hover:text-white sm:order-5 sm:block motion-reduce:transition-none">{{ $game['action'] }}</span>
        </a>
    @endforeach
</div>

