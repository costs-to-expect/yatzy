<x-layouts.app :title="config('app.game.name').' Game Scorer: Delete Yatzy account'" active="account">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Delete Yatzy account</h1>
        <p class="mt-2 text-stone-700">We will immediately create a background task to delete your data, the task should start within a minute, once it completes all your Yatzy data will be gone and your session will be deleted.</p>
        <p class="mt-2 text-stone-700">Please review the tables below to see what will be deleted and what will remain.</p>

        <x-data-table class="mt-6" title="Data that will be deleted" :rows="[
            ['Yatzy games', 'Your open and complete games', 'API'],
            ['Share tokens', 'Public share tokens for open games', 'Yatzy'],
            ['Game log', 'The logs containing all game actions', 'API'],
            ['Sessions', 'Session information', 'Yatzy'],
        ]" />

        <x-data-table class="mt-6" title="Data that will not be deleted" :rows="[
            ['Account', 'Your Costs to Expect account, one account for all our apps', 'API'],
            ['Players', 'Players you created, usable across all our game scoring apps', 'API'],
            ['Other apps', 'You will still have access to all the other Costs to Expect apps and none of your data will be touched', 'API & other Costs to Expect apps'],
        ]" />

        <form action="{{ route('account.delete-yatzy-account.action') }}" method="POST" class="mt-8 flex flex-col gap-3 sm:flex-row">
            @csrf
            <button type="submit" class="btn btn-danger">Confirm delete (cannot be undone)</button>
            <a href="{{ route('account') }}" class="btn btn-quiet">Cancel</a>
        </form>
    </div>
</x-layouts.app>
