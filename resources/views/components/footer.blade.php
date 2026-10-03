{{-- The footer: the Costs to Expect lockup (the only place the CTE purple appears outside the account pages), the
     version and the support address. --}}
<footer class="mx-auto max-w-5xl px-4 pb-10 pt-2">
    <div class="flex flex-col items-center gap-2 border-t border-stone-200 pt-5 text-sm text-stone-600 sm:flex-row sm:justify-between">
        <a href="https://www.costs-to-expect.com" class="inline-flex items-center gap-2.5 rounded-lg py-1.5 font-bold text-stone-700 hover:text-cte-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-cte-500"><img src="{{ asset('images/logo.png') }}" alt="" width="28" height="28" class="h-7 w-7">A Costs to Expect app</a>
        <p>v{{ $version }} &middot; <a href="mailto:support@costs-to-expect.com" class="inline-block py-1.5 underline underline-offset-2 hover:text-stone-900">support@costs-to-expect.com</a></p>
    </div>
</footer>
