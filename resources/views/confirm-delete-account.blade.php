<x-layouts.app :title="config('app.game.name').' Game Scorer: Delete account'" active="account">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Delete account</h1>

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

        <p class="mt-6 text-stone-700">We will immediately create a background task to delete your data, the task should start within a minute, once it completes your data will be gone and your session will be deleted.</p>
        <p class="mt-2 text-stone-700">Please review the table below to see what will be deleted, nothing will remain.</p>

        <x-data-table class="mt-6" title="Data that will be deleted" :rows="[
            ['Account', 'Your Costs to Expect account', 'API'],
            ['Data', 'All the data we have stored will be deleted', 'API & all our apps'],
        ]" />

        <form action="{{ route('account.delete-account.action') }}" method="POST" class="mt-8 flex flex-col gap-3 sm:flex-row">
            @csrf
            <button type="submit" class="btn btn-danger">Confirm delete (cannot be undone)</button>
            <a href="{{ route('account') }}" class="btn btn-quiet">Cancel</a>
        </form>
    </div>
</x-layouts.app>
