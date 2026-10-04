<x-layouts.guest :title="config('app.game.name').' Game Scorer: Password created'" :noindex="true">
    <x-auth-card title="Password created!">
        <p class="text-lg text-stone-800">Your new password has been set, you are free to <a href="{{ route('sign-in.view') }}" class="text-link">sign in</a> and carry on scoring your {{ config('app.game.name') }} games.</p>
        <a href="{{ route('sign-in.view') }}" class="btn btn-primary btn-block mt-6">Sign in</a>
    </x-auth-card>
</x-layouts.guest>
