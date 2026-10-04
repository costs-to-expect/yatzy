@props(['title', 'subtitle' => null])
{{-- The card the sign-in, register and password pages share. The account is a Costs to Expect account, so it says so. --}}
<div class="card sm:p-8">
    <p class="mb-4 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-cte-700"><img src="{{ asset('images/logo.png') }}" alt="" width="20" height="20" class="h-5 w-5">Your Costs to Expect account</p>
    <h1 class="text-3xl font-extrabold tracking-tight">{{ $title }}</h1>
    @if ($subtitle)
        <p class="mt-1.5 text-stone-600">{{ $subtitle }}</p>
    @endif
    <div class="mt-6">{{ $slot }}</div>
</div>
