@props(['players', 'tones' => [], 'picked' => [], 'legend' => 'Players', 'newPlayer' => true, 'name' => 'players[]'])
{{-- Players to pick for a game: checkboxes styled as chips. A chosen player's avatar becomes a tick, so the choice never
     relies on colour alone. The checkboxes work without JavaScript, the script only keeps the start button up to date. --}}
<fieldset {{ $attributes }}>
    <legend class="sr-only">{{ $legend }}</legend>
    <div class="flex flex-wrap gap-2">
        @foreach ($players as $player)
            <label class="chip has-checked:bg-brand-700 has-checked:text-white has-checked:ring-brand-700 has-checked:hover:bg-brand-800 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand-600">
                <input type="checkbox" name="{{ $name }}" value="{{ $player['id'] }}" class="peer sr-only" @checked(in_array($player['id'], $picked, true))>
                <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-brand-700 peer-checked:inline-flex"><x-icon name="check" class="h-4 w-4" stroke-width="3" /></span>
                <x-avatar :name="$player['name']" :index="$tones[$player['id']] ?? 0" class="h-7 w-7 text-xs peer-checked:hidden" />
                {{ $player['name'] }}
            </label>
        @endforeach
        @if ($newPlayer)
            <a href="{{ route('player.create.view') }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-dashed border-stone-400 px-4 text-sm font-bold text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"><x-icon name="plus" class="h-4 w-4" />New player</a>
        @endif
    </div>
</fieldset>
