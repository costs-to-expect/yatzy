<x-layouts.app :title="config('app.game.name').' Game Scorer: Account'" active="account">
    <div class="mx-auto max-w-2xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Your account</h1>
        <p class="mt-1 text-stone-600">Your Costs to Expect account, one account for all our apps.</p>

        @if ($job !== null)
            <x-alert type="info" title="Delete started!" class="mt-6">
                @if ($job === 'delete-yatzy-account')
                    <p>A job has been added to delete your Yatzy account, we should be done in a minute or two!</p>
                @else
                    <p>A job has been added to delete your account, we should be done in a minute or two!</p>
                @endif
                <p>You have been logged out, if you refresh you will be back at the login screen.</p>
                <p>You will get an email when your account has been deleted.</p>
            </x-alert>
        @endif

        <dl class="card-list mt-6">
            <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                <dt class="text-sm font-bold text-stone-600">Name</dt>
                <dd class="min-w-0 truncate font-bold">{{ $user['name'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                <dt class="text-sm font-bold text-stone-600">Email</dt>
                <dd class="min-w-0 truncate font-bold">{{ $user['email'] }}</dd>
            </div>
        </dl>
        <p class="mt-2 px-1 text-xs text-stone-600">Profile updates are coming soon.</p>

        <a href="{{ route('sign-out') }}" class="btn btn-secondary mt-5 sm:hidden"><x-icon name="sign-out" class="h-5 w-5" />Sign out</a>

        <section class="mt-10" aria-labelledby="delete-heading">
            <h2 id="delete-heading" class="text-lg font-extrabold tracking-tight">Delete your data</h2>

            <ul class="card-list mt-3">
                <li>
                    <a href="{{ route('account.confirm-delete-yatzy-account') }}" class="flex items-center gap-3 px-4 py-4 hover:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-red-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-700"><x-icon name="trash" class="h-5 w-5" /></span>
                        <span class="min-w-0 flex-1"><span class="block font-bold">Delete Yatzy account</span><span class="block text-sm text-stone-600">Your games and score sheets. You keep your Costs to Expect account and your players.</span></span>
                        <span class="text-stone-400"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('account.confirm-delete-account') }}" class="flex items-center gap-3 px-4 py-4 hover:bg-stone-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-red-600">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-700"><x-icon name="trash" class="h-5 w-5" /></span>
                        <span class="min-w-0 flex-1"><span class="block font-bold">Delete Costs to Expect account</span><span class="block text-sm text-stone-600">Your entire account and everything in all our apps.</span></span>
                        <span class="text-stone-400"><x-icon name="chevron-right" class="h-5 w-5" /></span>
                    </a>
                </li>
            </ul>
        </section>

        <section class="mt-10" aria-labelledby="more-heading">
            <h2 id="more-heading" class="text-lg font-extrabold tracking-tight">More from Costs to Expect</h2>
            <ul class="mt-3 flex flex-wrap gap-2 text-sm font-bold">
                @foreach ([
                    'Budget' => 'https://budget.costs-to-expect.com',
                    'Budget Pro' => 'https://budget-pro.costs-to-expect.com',
                    'Expense' => 'https://app.costs-to-expect.com',
                    'Yahtzee Game Scorer' => 'https://yahtzee.game-scorer.com',
                    'Yatzy Game Scorer' => 'https://yatzy.game-scorer.com',
                    'The API' => 'https://api.costs-to-expect.com',
                    'Service status' => 'https://status.costs-to-expect.com',
                    'GitHub' => 'https://github.com/costs-to-expect',
                ] as $label => $link)
                    <li><a href="{{ $link }}" class="inline-flex min-h-10 items-center rounded-full bg-white px-4 text-cte-700 ring-1 ring-stone-200 hover:bg-cte-50 focus-visible:outline-2 focus-visible:outline-cte-500">{{ $label }}</a></li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.app>
