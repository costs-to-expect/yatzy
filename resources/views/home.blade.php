@php
    use App\Support\GameBoard;

    $game = config('app.game');
    $has_players = count($players) > 0;
    $player_errors = $errors['players']['errors'] ?? [];

    // First visit: nobody has been added yet. A failed start (a name that was taken) shows the form again.
    $state = match (true) {
        ! $has_players => 'first',
        count($boards) > 0 => 'busy',
        $player_errors !== [] && count($boards) === 0 => 'first',
        default => 'idle',
    };

    $board = $boards[$selected] ?? null;
    $standings = $board['players'] ?? [];
    $leader = $standings[0] ?? null;
    $gap = count($standings) > 1 ? $standings[0]['score'] - $standings[1]['score'] : 0;

    // The players the next game starts with: whoever played the last game
    $picked = array_column($last_game['players'] ?? [], 'id');
@endphp
<x-layouts.app title="{{ $game['name'] }} Game Scorer: Home" active="home">
    <div @class(['grid gap-6', 'lg:grid-cols-5' => $state !== 'first'])>

        <div @class(['lg:col-span-3' => $state !== 'first', 'mx-auto w-full max-w-xl' => $state === 'first'])>

            @if ($state === 'busy')
                {{-- ============ Game night ============ --}}
                <section aria-labelledby="busy-heading">
                    <div class="card">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="inline-flex items-center gap-2.5 text-sm font-semibold text-emerald-700">
                                <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span></span>
                                <span>Game in progress @if ($board['since']) &middot; {{ $board['since'] }}@endif</span>
                            </p>

                            @if (count($boards) > 1)
                                <nav class="flex gap-1 rounded-full bg-stone-100 p-1 text-sm font-bold" aria-label="Open games">
                                    @foreach ($boards as $position => $open)
                                        <a href="{{ route('home', ['game' => $open['id']]) }}" @if ($position === $selected) aria-current="true" @endif
                                           @class([
                                               'inline-flex min-h-10 items-center rounded-full px-4 focus-visible:outline-2 focus-visible:outline-brand-600',
                                               'bg-white text-stone-900 shadow-sm' => $position === $selected,
                                               'text-stone-600 hover:text-stone-900' => $position !== $selected,
                                           ])>{{ $open['label'] }}</a>
                                    @endforeach
                                </nav>
                            @endif
                        </div>

                        <h1 id="busy-heading" class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Who&rsquo;s scoring?</h1>
                        <p class="mt-1.5 text-stone-600">
                            Tap a player to carry on scoring.
                            @if ($leader && $leader['leader'])
                                {{ $leader['name'] }} is ahead by {{ $gap }}.
                            @endif
                        </p>

                        <x-player-tiles :standings="$standings" :game-id="$board['id']" class="mt-6" />

                        <x-game-actions :game-id="$board['id']" />
                    </div>
                </section>

            @elseif ($state === 'idle')
                {{-- ============ No game running ============ --}}
                <section aria-labelledby="idle-heading">
                    <div class="card text-center sm:p-10">
                        <div class="flex justify-center"><x-art /></div>
                        <h1 id="idle-heading" class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $last_game ? 'Ready for another game?' : 'Ready for the first game?' }}</h1>

                        @if ($last_game)
                            @php($last_names = array_column($last_game['players'], 'name'))
                            @php($last_when = $last_game['when'] === null ? null : (in_array($last_game['when'], ['Today', 'Yesterday'], true) ? strtolower($last_game['when']) : 'on '.$last_game['when']))
                            <p class="mt-1.5 text-stone-600">Last played{{ $last_when ? ' '.$last_when : '' }} with {{ GameBoard::names($last_names) }}.</p>

                            <form action="{{ route('game.create.action') }}" method="POST">
                                @csrf
                                <input type="hidden" name="name" value="{{ $game['game_name'] }}">
                                <input type="hidden" name="description" value="{{ $game['game_description'] }}">
                                @foreach ($last_game['players'] as $last_player)
                                    <input type="hidden" name="players[]" value="{{ $last_player['id'] }}">
                                @endforeach
                                <button type="submit" class="btn btn-primary btn-block mt-6 py-4 text-base sm:w-auto sm:min-w-80">Play again with {{ GameBoard::ampersands($last_names) }}</button>
                            </form>
                            <p class="mt-3 text-sm text-stone-600">Same players, fresh score sheets.</p>
                        @else
                            <p class="mt-1.5 text-stone-600">Choose who is playing in the next game and start scoring.</p>
                        @endif
                    </div>
                </section>

            @else
                {{-- ============ First visit ============ --}}
                <section aria-labelledby="first-heading">
                    <div class="card sm:p-10">
                        <div class="flex justify-center"><x-art /></div>
                        <h1 id="first-heading" class="mt-4 text-center text-3xl font-extrabold tracking-tight sm:text-4xl">Let&rsquo;s get started!</h1>
                        <p class="mt-1.5 text-center text-stone-600">Enter each of your players on a new line in the box below. You can add more players whenever you like.</p>

                        <form action="{{ route('start') }}" method="POST" class="mt-6">
                            @csrf
                            <label for="players" class="sr-only">Players</label>
                            <textarea name="players" id="players" rows="4" required
                                      @class(['form-control', 'form-control-error' => $player_errors !== []])
                                      placeholder="Ada&#10;Ben&#10;Cleo"
                                      aria-describedby="players-help @if ($player_errors !== []) players-error @endif"
                                      @if ($player_errors !== []) aria-invalid="true" @endif>{{ old('players') }}</textarea>
                            <p id="players-help" class="form-help">Please enter all players, one name per line.</p>
                            @if ($player_errors !== [])
                                <p id="players-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                            @endif
                            <button type="submit" class="btn btn-primary btn-block mt-4 py-4 text-base">Start the first game</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        @if ($state !== 'first')
            <aside class="space-y-6 lg:col-span-2">
                <section class="card p-5 sm:p-5" aria-labelledby="next-heading">
                    <h2 id="next-heading" class="text-lg font-extrabold tracking-tight">Next game</h2>
                    <p class="mt-0.5 text-sm text-stone-600">Pick who&rsquo;s playing.</p>

                    <form action="{{ route('game.create.action') }}" method="POST"
                          data-picker data-picker-min="{{ $game['min_players'] }}" data-picker-label="Start game with">
                        @csrf
                        <input type="hidden" name="name" value="{{ $game['game_name'] }}">
                        <input type="hidden" name="description" value="{{ $game['game_description'] }}">

                        <x-player-picker :players="$players" :tones="$tones" :picked="$picked" legend="Players in the next game" class="mt-4" />

                        @if ($player_errors !== [])
                            <p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ implode(' ', $player_errors) }}</span></p>
                        @endif

                        @php($count = count($picked))
                        <button type="submit" data-picker-submit class="btn btn-primary btn-block mt-5" @disabled($count < $game['min_players'])>
                            @if ($count < $game['min_players'])
                                {{ $game['min_players'] === 1 ? 'Choose the players to start' : 'Choose at least '.$game['min_players'].' players' }}
                            @else
                                Start game with {{ $count }} {{ $count === 1 ? 'player' : 'players' }}
                            @endif
                        </button>
                    </form>
                </section>

                <section aria-labelledby="history-heading">
                    <div class="flex items-baseline justify-between">
                        <h2 id="history-heading" class="text-lg font-extrabold tracking-tight">Last games</h2>
                        <a href="{{ route('games') }}" class="-mr-2 inline-flex min-h-11 items-center rounded-xl px-2 text-sm font-bold text-brand-700 hover:text-brand-900">All games</a>
                    </div>

                    @if (count($history) > 0)
                        <ul class="card-list mt-3">
                            @foreach ($history as $item)
                                <li>
                                    <a href="{{ route('game.show', ['game_id' => $item['id']]) }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-stone-50 focus-visible:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><x-icon name="trophy" class="h-5 w-5" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-bold">{{ $item['winner'] }} <span class="font-medium text-stone-600">won with</span> {{ $item['score'] }}</span>
                                            <span class="block truncate text-xs text-stone-600">@if ($item['when']){{ $item['when'] }} &middot; @endif{{ $item['others'] !== '' ? $item['others'] : 'Played on their own' }}</span>
                                        </span>
                                        <span class="text-stone-400"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="card-list mt-3 px-4 py-5 text-sm text-stone-600">
                            <p class="font-bold text-stone-800">No finished games yet.</p>
                            <p class="mt-1">As soon as you finish a game it shows up here, with who won.</p>
                        </div>
                    @endif
                </section>
            </aside>
        @endif
    </div>

    @if ($state === 'busy')
        <x-game-dialogs :board="$board" :standings="$standings" :share-tokens="$share_tokens" :leader="$leader" />
    @endif
</x-layouts.app>
