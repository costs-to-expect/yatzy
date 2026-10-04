<x-layouts.guest title="Yatzy Game Scorer: Online Yatzy Score Sheets"
                 description="Score your Yatzy games online. Track upper and lower section scores, bonuses and totals for every player, with shareable score sheets so no one has to be the designated scorer."
                 width="max-w-5xl">
    <x-slot:head>
        <link rel="canonical" href="{{ url('/') }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Yatzy Game Scorer">
        <meta property="og:title" content="Yatzy Game Scorer: Online Yatzy Score Sheets">
        <meta property="og:description" content="Score your Yatzy games online. Track upper and lower section scores, bonuses and totals for every player, with shareable score sheets so no one has to be the designated scorer.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:image" content="{{ asset('images/card.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Yatzy Game Scorer: Online Yatzy Score Sheets">
        <meta name="twitter:description" content="Score your Yatzy games online. Track upper and lower section scores, bonuses and totals for every player, with shareable score sheets so no one has to be the designated scorer.">
        <meta name="twitter:image" content="{{ asset('images/card.png') }}">
    </x-slot:head>

    {{-- ============ Hero: the pitch and a score sheet to try ============ --}}
    <section class="rounded-4xl bg-gradient-to-br from-brand-50 via-white to-white p-5 ring-1 ring-brand-100 sm:p-10" aria-labelledby="hero-heading">
        <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-12">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-sm font-bold text-brand-800 shadow-card ring-1 ring-brand-100"><x-game-mark class="h-4 w-4" />Free, and no app to install</p>
                <h1 id="hero-heading" class="mt-5 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">Yatzy Game Scorer</h1>
                <p class="mt-4 max-w-xl text-lg text-stone-700">A game scorer powered by the Costs to Expect API.</p>
                <p class="mt-2 max-w-xl text-stone-600">Yep, you read that right, a game scorer, you still need to be social and play the game.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register.view') }}" class="btn btn-primary w-full sm:w-auto sm:min-w-44">Register</a>
                    <a href="{{ route('sign-in.view') }}" class="btn btn-secondary w-full sm:w-auto sm:min-w-44">Sign in</a>
                </div>
                <p class="mt-4 text-sm text-stone-600">Not sure yet? Have a go at the score sheet, it works right here.</p>
            </div>

            <section id="demo" class="overflow-hidden rounded-3xl bg-white shadow-lift ring-1 ring-stone-200" aria-labelledby="demo-heading">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 bg-stone-50 px-5 py-2.5">
                    <h2 id="demo-heading" class="inline-flex items-center gap-2 text-sm font-extrabold"><x-icon name="sparkles" class="h-4 w-4 text-brand-700" />Try the score sheet</h2>
                    <p class="text-xs text-stone-600">Nothing is saved</p>
                </div>

                <div class="flex items-end gap-3 px-5 pb-2.5 pt-3.5">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-stone-600">Total <span data-demo="turns" class="font-semibold normal-case tracking-normal">&middot; 0 of 6 rows</span></p>
                        <p data-demo="total" class="mt-0.5 inline-block origin-left text-4xl font-extrabold leading-none tabular-nums" aria-live="polite">0</p>
                    </div>
                    <dl class="ml-auto flex items-end gap-4 text-right">
                        <div><dt class="text-[10px] font-bold uppercase tracking-wider text-stone-600">Upper</dt><dd data-demo="upper" class="text-base font-bold tabular-nums">0</dd></div>
                        <div><dt class="text-[10px] font-bold uppercase tracking-wider text-stone-600">Bonus</dt><dd data-demo="bonus" class="text-base font-bold tabular-nums">0</dd></div>
                    </dl>
                </div>

                <div class="px-4 pb-3">
                    <div class="rounded-2xl bg-stone-50 p-3.5 ring-1 ring-stone-200/70">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm font-extrabold">Upper bonus <span class="font-semibold text-stone-600">50 points</span></p>
                            <p class="text-sm tabular-nums"><strong data-demo="count" class="text-base font-extrabold">0</strong><span class="text-stone-600"> / 63</span></p>
                        </div>
                        <div class="mt-2.5 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-stone-200" role="progressbar" aria-label="Upper bonus" aria-valuemin="0" aria-valuemax="63" aria-valuenow="0" data-demo="progress"><div data-demo="bar" class="h-full w-0 rounded-full bg-brand-600 transition-[width] duration-500 motion-reduce:transition-none"></div></div>
                        <p class="mt-3 flex items-start gap-2 text-sm text-stone-700"><span data-demo="tip-icon" class="mt-0.5 text-brand-700"><x-icon name="bulb" class="h-5 w-5" /></span><span data-demo="tip">Score a row to see how it works. Three of each gets you the 50 point bonus.</span></p>
                    </div>
                </div>

                <noscript><p class="border-t border-stone-100 px-5 py-4 text-sm text-stone-700">The score sheet draws itself, switch JavaScript on to try it.</p></noscript>
                <ul data-demo="list" class="divide-y divide-stone-100 border-t border-stone-100"></ul>

                <div data-demo="done" hidden class="bg-gradient-to-br from-brand-700 to-brand-900 p-5 text-white">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-icon name="trophy" class="h-7 w-7" /></span>
                        <div>
                            <p class="text-lg font-extrabold tracking-tight">That&rsquo;s the idea!</p>
                            <p class="mt-1 text-sm text-brand-100">You scored <strong data-demo="done-score" class="text-white">0</strong> in the upper section. A real game has the lower section, every player and a score sheet each.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                        <a href="{{ route('register.view') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-white px-4 text-sm font-extrabold text-brand-800 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Start a real game</a>
                        <button type="button" data-demo="again" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-white/15 px-4 text-sm font-bold text-white hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Try again</button>
                    </div>
                </div>

                <div class="flex justify-end border-t border-stone-100 px-4 py-1.5" data-demo="reset-bar" hidden>
                    <button type="button" data-demo="reset" class="min-h-11 rounded-xl px-3 text-sm font-bold text-brand-700 hover:text-brand-900 focus-visible:outline-2 focus-visible:outline-brand-600">Start over</button>
                </div>
            </section>
        </div>
    </section>

    {{-- ============ A game night, step by step ============ --}}
    <section id="walk" class="group/walk mt-16 sm:mt-24" aria-labelledby="walk-heading">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="walk-heading" class="text-3xl font-extrabold tracking-tight sm:text-4xl">How a game night goes</h2>
            <p class="mt-3 text-lg text-stone-700">One of you sets it up, everybody scores on their own phone.</p>
        </div>

        <div class="mt-10 grid gap-10 md:group-data-[js]/walk:grid-cols-2 md:group-data-[js]/walk:gap-16">
            <ol class="space-y-10 md:group-data-[js]/walk:space-y-0">
                @foreach ([
                    ['Pick who&rsquo;s playing', 'One of you signs in and chooses the players. Played last night? Play again with the same people in one tap.', 'new-game.png', 'A screen shot of choosing the players for a new game'],
                    ['Send everyone their link', 'Every player gets a link to their own score sheet. Only one player needs an account, everyone else just opens the link, no app and no sign up. Links last for the life of the game.', 'management.png', 'A screen shot of sharing a score sheet link with each player'],
                    ['Everybody scores their own sheet', 'No more designated scorer, no more &ldquo;Do I have my threes?&rdquo;. Tap a combination, say how it went and you&rsquo;re done, the totals and the upper bonus work themselves out.', 'score-sheet.png', 'A screen shot of the score sheet for Yatzy'],
                    ['Everyone sees everything', 'Every score, all the time, no need to bug anyone. The leader wears the crown, and when the last turn is played the winner is saved to your history.', 'player-scores.png', 'A screen shot of the home page showing every player\'s score in the game'],
                ] as [$title, $text, $image, $alt])
                    <li data-step @if ($loop->first) data-active @endif class="group/step md:group-data-[js]/walk:flex md:group-data-[js]/walk:min-h-[70vh] md:group-data-[js]/walk:items-center">
                        <div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-lg font-extrabold text-brand-800 ring-1 ring-brand-100 transition group-data-[active]/step:bg-brand-700 group-data-[active]/step:text-white group-data-[active]/step:ring-brand-700 motion-reduce:transition-none" aria-hidden="true">{{ $loop->iteration }}</span>
                            <h3 class="mt-3 text-2xl font-extrabold tracking-tight"><span class="sr-only">Step {{ $loop->iteration }}: </span>{!! $title !!}</h3>
                            <p class="mt-2 max-w-md text-stone-700">{!! $text !!}</p>

                            {{-- A phone under the step on a small screen, one phone beside all of them on a large one --}}
                            <div class="mt-6 flex justify-center rounded-3xl bg-brand-50 px-6 pt-6 md:group-data-[js]/walk:hidden">
                                <img src="{{ asset('images/'.$image) }}" width="300" height="600" loading="lazy" alt="{{ $alt }}" class="h-auto w-60 rounded-t-3xl shadow-lift ring-1 ring-stone-200">
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="hidden md:group-data-[js]/walk:block" aria-label="The screens, in step with the text" role="group">
                <div class="sticky top-24 mx-auto w-72">
                    <div class="rounded-[2.5rem] bg-stone-900 p-2 shadow-lift">
                        <div class="relative aspect-[1/2] overflow-hidden rounded-[2rem] bg-white">
                            @foreach (['new-game.png' => 'A screen shot of choosing the players for a new game', 'management.png' => 'A screen shot of sharing a score sheet link with each player', 'score-sheet.png' => 'A screen shot of the score sheet for Yatzy', 'player-scores.png' => 'A screen shot of the home page showing every player\'s score in the game'] as $image => $alt)
                                <img data-shot @if ($loop->first) data-active @else aria-hidden="true" @endif src="{{ asset('images/'.$image) }}" width="300" height="600" loading="lazy" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover object-top opacity-0 transition-opacity duration-300 data-[active]:opacity-100 motion-reduce:transition-none">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Ready? ============ --}}
    <section class="mt-16 rounded-4xl bg-gradient-to-br from-brand-700 to-brand-900 p-8 text-center text-white shadow-lift sm:mt-24 sm:p-12" aria-labelledby="ready-heading">
        <h2 id="ready-heading" class="text-3xl font-extrabold tracking-tight sm:text-4xl">Ready for game night?</h2>
        <p class="mx-auto mt-3 max-w-lg text-brand-100">Register, pick the players and send out the links. It takes about a minute.</p>
        <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register.view') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-white px-8 text-base font-extrabold text-brand-800 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:w-auto sm:min-w-44">Register</a>
            <a href="{{ route('sign-in.view') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-white/15 px-8 text-base font-bold text-white hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:w-auto sm:min-w-44">Sign in</a>
        </div>
    </section>

    <section class="card mt-6 flex flex-col justify-center text-center" aria-labelledby="stats-heading">
        <h2 id="stats-heading" class="text-2xl font-extrabold tracking-tight">Stats <span class="font-semibold text-stone-500">(coming soon)</span></h2>
        <p class="mt-2 text-stone-700">All the stats you could possibly want are coming soon, we are working out the best way to visualise everything.</p>
    </section>

    @push('scripts')
        <script src="{{ asset('js/landing.js') }}?v={{ config('app.version.js') }}" defer></script>
    @endpush
</x-layouts.guest>
