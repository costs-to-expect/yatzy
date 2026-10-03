<x-layouts.app :title="config('app.game.name').' Game Scorer: New Player'" active="players">
    <div class="mx-auto max-w-md">
        <div class="card sm:p-8">
            <h1 class="text-3xl font-extrabold tracking-tight">New player</h1>
            <p class="mt-1.5 text-stone-600">Add a new player, they will be selectable as a player in all new games.</p>

            <form action="{{ route('player.create.action') }}" method="POST" class="mt-6">
                @csrf
                <x-field name="name" label="Name" help="Please enter the name of the new player." :bag="$errors" :value="old('name')" required autofocus />
                <input type="hidden" name="description" value="{{ old('description', 'New player - Added via the Yatzy App') }}">
                <button type="submit" class="btn btn-primary btn-block mt-6">Add player</button>
            </form>
        </div>
    </div>
</x-layouts.app>
