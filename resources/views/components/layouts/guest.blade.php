@props([
    'title' => 'Yatzy Game Scorer',
    'description' => 'Yatzy Game Scorer by Costs to Expect',
    'noindex' => false,
    'width' => 'max-w-md',
])
{{-- The signed-out shell, the landing page and the sign-in, register and password pages --}}
<x-layouts.base :title="$title" :description="$description" :noindex="$noindex">
    <x-slot:head>{{ $head ?? '' }}</x-slot:head>

    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4">
            <x-brand :href="route('landing')" />
            <nav class="flex items-center gap-1" aria-label="Account">
                <a href="{{ route('sign-in.view') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-2 focus-visible:outline-brand-600">Sign in</a>
                <a href="{{ route('register.view') }}" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Register</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto {{ $width }} px-4 py-8 sm:py-12">
        {{ $slot }}
    </main>

    <x-footer />
</x-layouts.base>
