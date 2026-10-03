@props(['href' => '/', 'game' => 'yatzy', 'name' => 'Yatzy', 'tagline' => 'Game Scorer'])
{{-- The logo tile and the name. A game is told apart by its mark and name, never its colour, so Scrabble and
     Carcassonne only change the two values. --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-xl py-1 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600']) }}>
    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm"><x-game-mark :game="$game" class="h-5 w-5" /></span>
    <span class="text-lg font-extrabold tracking-tight">{{ $name }}<span class="ml-2 hidden text-sm font-semibold text-stone-500 sm:inline">{{ $tagline }}</span></span>
</a>
