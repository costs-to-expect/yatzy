@props([
    'title' => 'Yatzy Game Scorer',
    'active' => null,
    'description' => 'Yatzy Game Scorer by Costs to Expect',
])
{{-- The signed-in shell: a header with the main navigation on a laptop and a tab bar where a thumb reaches on a
     phone. A page puts its content in the slot and says which tab is current. --}}
@php
    $tabs = [
        'home' => ['label' => 'Home', 'icon' => 'home', 'route' => 'home'],
        'games' => ['label' => 'Games', 'icon' => 'games', 'route' => 'games'],
        'players' => ['label' => 'Players', 'icon' => 'players', 'route' => 'players'],
        'account' => ['label' => 'Account', 'icon' => 'account', 'route' => 'account'],
    ];
@endphp
<x-layouts.base :title="$title" :description="$description" body-class="pb-24 sm:pb-0">
    <x-slot:head>{{ $head ?? '' }}</x-slot:head>

    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4">
            <x-brand :href="route('home')" />

            <nav class="hidden items-center gap-1 sm:flex" aria-label="Main">
                @foreach (['home', 'games', 'players'] as $key)
                    <a href="{{ route($tabs[$key]['route']) }}" @if ($active === $key) aria-current="page" @endif
                       @class([
                           'rounded-full px-4 py-2 text-sm focus-visible:outline-2 focus-visible:outline-brand-600',
                           'bg-brand-50 font-bold text-brand-800' => $active === $key,
                           'font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900' => $active !== $key,
                       ])>{{ $tabs[$key]['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-1 sm:flex">
                <a href="{{ route('account') }}" @if ($active === 'account') aria-current="page" @endif
                   @class([
                       'inline-flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-stone-200 hover:bg-stone-200 focus-visible:outline-2 focus-visible:outline-brand-600',
                       'bg-brand-50 text-brand-800' => $active === 'account',
                       'bg-stone-100 text-stone-700' => $active !== 'account',
                   ])
                   aria-label="Your account" title="Your Costs to Expect account"><x-icon name="account" class="h-5 w-5" /></a>
                <a href="{{ route('sign-out') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-2 focus-visible:outline-brand-600">Sign out</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6 sm:py-8">
        {{ $slot }}
    </main>

    <x-footer />

    {{-- Phone navigation: where a thumb reaches --}}
    <nav data-tab-bar class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur sm:hidden" aria-label="Main">
        <ul class="mx-auto grid max-w-md grid-cols-4">
            @foreach ($tabs as $key => $tab)
                <li>
                    <a href="{{ route($tab['route']) }}" @if ($active === $key) aria-current="page" @endif
                       @class([
                           'flex flex-col items-center gap-1 pb-2 pt-2.5 text-[11px] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-600',
                           'font-bold text-brand-800' => $active === $key,
                           'font-semibold text-stone-600' => $active !== $key,
                       ])>
                        <span @class(['rounded-full px-5 py-1', 'bg-brand-100' => $active === $key])><x-icon :name="$tab['icon']" class="h-6 w-6" /></span>
                        {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</x-layouts.base>
