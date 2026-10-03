@props(['config', 'public' => false])
@php
    // The first paint, the script draws the rest from the same data and keeps it up to date
    $totals = $config['sheet']['score'] ?? ['upper' => 0, 'bonus' => 0, 'lower' => 0, 'total' => 0];
    $turns = \App\Support\ScoreRules::turns($config['sheet']);
    $name = $config['player']['name'];
    $tone = $config['tones']->{$config['player']['id']} ?? 0;
    $max_turns = $config['turns'];
@endphp

{{-- The score sheet has its own bar, the totals have to stay in view while scoring --}}
<nav class="border-b border-stone-200 bg-white" aria-label="Score sheet">
    <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-2 px-2 sm:px-4">
        @if (! $public)
            <a href="{{ $config['urls']['back'] }}" class="-ml-1 inline-flex min-h-11 items-center gap-1 rounded-xl pl-1 pr-3 text-sm font-bold text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-brand-600"><x-icon name="chevron-left" class="h-5 w-5" />Game</a>
        @else
            <x-brand :href="route('landing')" class="-ml-1 pl-1" />
        @endif
        <div class="flex min-w-0 items-center gap-2.5">
            <x-avatar :name="$name" :index="$tone" class="h-8 w-8 text-sm" />
            <h1 class="truncate text-base font-extrabold tracking-tight">Player: {{ $name }}</h1>
        </div>
        <button type="button" id="help-toggle" aria-expanded="false" aria-controls="help" class="rounded-full p-2.5 text-stone-600 hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-2 focus-visible:outline-brand-600" aria-label="How to score"><x-icon name="help" class="h-6 w-6" /></button>
    </div>
</nav>

<div id="help" hidden class="border-b border-brand-100 bg-brand-50">
    <div class="mx-auto max-w-5xl px-4 py-3.5 text-sm text-stone-800">
        <p class="font-extrabold text-brand-900">How to score</p>
        @if ($config['corrections'])
            <p class="mt-1 max-w-2xl">Tap a combination, say how it went and you&rsquo;re done. Made a mistake? Tap <strong>Undo</strong> straight after, or tap the score later to change or clear it. Nothing here is permanent until the game is finished.</p>
        @else
            <p class="mt-1 max-w-2xl">Tap a combination, say how it went and you&rsquo;re done. Use <strong>Scratch</strong> when you can&rsquo;t score a combination, it takes the turn for zero. A score is saved as soon as you tap it, so check it before you do.</p>
        @endif
    </div>
</div>

{{-- Totals stay in view while you score, so you always see what a score did --}}
<header class="sticky top-0 z-20 border-b border-stone-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-5xl items-end gap-3 px-4 pb-2.5 pt-3 sm:gap-6">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wider text-stone-600">Total <span id="turn-label" class="font-semibold normal-case tracking-normal text-stone-600">&middot; {{ $turns }} of {{ $max_turns }} turns</span></p>
            <p id="total" class="mt-0.5 inline-block origin-left text-4xl font-extrabold leading-none tabular-nums" aria-live="polite">{{ $totals['total'] }}</p>
        </div>
        <dl class="ml-auto flex items-end gap-3.5 text-right sm:gap-6">
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-stone-600">Upper</dt><dd id="upper" class="text-base font-bold tabular-nums">{{ $totals['upper'] }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-stone-600">Bonus</dt><dd id="bonus" class="text-base font-bold tabular-nums">{{ $totals['bonus'] }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-stone-600">Lower</dt><dd id="lower" class="text-base font-bold tabular-nums">{{ $totals['lower'] }}</dd></div>
        </dl>
        <button type="button" id="status" class="-mr-1 inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-full px-2.5 text-xs font-bold text-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600" aria-live="polite">
            <x-icon name="check-circle" class="h-5 w-5" /><span class="sr-only sm:not-sr-only">Saved</span>
        </button>
    </div>
    <div id="turn-bar" class="flex gap-0.5" aria-hidden="true">
        @for ($turn = 0; $turn < $max_turns; $turn++)
            <span class="h-[3px] flex-1 {{ $turn < $turns ? 'bg-brand-600' : 'bg-stone-200' }}"></span>
        @endfor
    </div>
    <div id="banner" hidden class="border-t border-amber-200 bg-amber-50">
        <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-2 text-sm text-amber-900">
            <x-icon name="alert" class="h-5 w-5 shrink-0" />
            <p id="banner-text" class="flex-1 font-semibold"></p>
            <button type="button" id="banner-retry" class="min-h-10 rounded-lg bg-amber-100 px-4 font-bold hover:bg-amber-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">Retry</button>
        </div>
    </div>
</header>

<div class="mx-auto max-w-5xl px-4 pb-20 pt-5 lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start lg:gap-8">

    <main class="space-y-6">
        <noscript>
            <x-alert type="warning" title="JavaScript is needed to score">The score sheet draws itself and saves each score as you tap it, switch JavaScript on and reload.</x-alert>
        </noscript>

        @if ($config['complete'])
            <x-alert type="info" title="This game is finished">The scores are locked. Open the game to see how everyone did.</x-alert>
        @endif

        <div id="done" hidden class="rounded-3xl bg-gradient-to-br from-brand-700 to-brand-900 p-5 text-white shadow-lift sm:p-6">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><x-icon name="trophy" class="h-7 w-7" /></span>
                <div>
                    <p class="text-xl font-extrabold tracking-tight">That&rsquo;s all {{ $max_turns }} turns!</p>
                    <p class="mt-1 text-sm text-brand-100">You scored <strong id="done-score" class="text-white">{{ $totals['total'] }}</strong>. <span id="done-waiting"></span></p>
                </div>
            </div>
            @if (! $public)
                <form action="{{ $config['urls']['complete'] }}" method="POST">
                    @csrf
                    <button type="submit" id="complete" class="mt-4 min-h-12 w-full rounded-2xl bg-white py-3.5 text-sm font-extrabold text-brand-800 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Complete the game</button>
                </form>
            @else
                <p class="mt-3 text-sm text-brand-100">Tell the person running the game, they finish it for everyone.</p>
            @endif
        </div>

        <section aria-labelledby="upper-heading">
            <div class="flex items-baseline justify-between">
                <h2 id="upper-heading" class="text-lg font-extrabold tracking-tight">Upper section</h2>
                <p class="text-xs text-stone-600">Total of the matching dice</p>
            </div>

            <div class="card mt-3 p-4 sm:p-4">
                <div class="flex items-baseline justify-between gap-3">
                    <p class="text-sm font-extrabold">Upper bonus <span class="font-semibold text-stone-600">50 points</span></p>
                    <p class="text-sm tabular-nums"><strong id="bonus-count" class="text-base font-extrabold">{{ min($totals['upper'], 63) }}</strong><span class="text-stone-600"> / 63</span></p>
                </div>
                <div class="mt-2.5 h-2.5 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-label="Upper bonus" aria-valuemin="0" aria-valuemax="63" aria-valuenow="{{ min($totals['upper'], 63) }}" id="bonus-progress"><div id="bonus-bar" class="h-full rounded-full bg-brand-600 transition-[width] duration-500 motion-reduce:transition-none" style="width: {{ min(100, round($totals['upper'] / 63 * 100)) }}%"></div></div>
                <p class="mt-3 flex items-start gap-2 text-sm text-stone-700"><span id="tip-icon" class="mt-0.5 text-brand-700"><x-icon name="bulb" class="h-5 w-5" /></span><span id="tip-text"></span></p>
            </div>

            <ul id="upper-list" class="card-list mt-3"></ul>
        </section>

        <section aria-labelledby="lower-heading">
            <h2 id="lower-heading" class="text-lg font-extrabold tracking-tight">Lower section</h2>
            <ul id="lower-list" class="card-list mt-3"></ul>
        </section>
    </main>

    <aside class="mt-8 lg:sticky lg:top-28 lg:mt-0" aria-labelledby="everyone-heading">
        <div class="flex items-baseline justify-between">
            <h2 id="everyone-heading" class="text-lg font-extrabold tracking-tight">Everyone</h2>
            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-stone-600"><span class="relative flex h-2 w-2" aria-hidden="true"><span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 motion-safe:animate-ping"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>Live</p>
        </div>
        <ul id="everyone" class="card-list mt-3" aria-live="off"></ul>
        <p class="mt-2.5 px-1 text-xs text-stone-600">Scores update by themselves, you never need to refresh.</p>
    </aside>
</div>

<x-footer />

<script type="application/json" id="sheet-config">@json($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
@push('scripts')
    <script src="{{ asset('js/score-sheet.js') }}?v={{ config('app.version.js') }}" defer></script>
@endpush
