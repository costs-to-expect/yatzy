<x-layouts.guest :title="config('app.game.name').' Game Scorer: Registration complete'" :noindex="true">
    <x-auth-card title="All done!">
        <p class="text-lg text-stone-800">Your account is ready, you are free to <a href="{{ route('sign-in.view') }}" class="text-link">sign in</a> immediately and start scoring your {{ config('app.game.name') }} games, enjoy!</p>
        <p class="mt-3 text-stone-700">If you have any suggestions, reach out to us on <a href="https://github.com/costs-to-expect/yatzy/issues" class="text-link">GitHub</a>, we are always looking for help with improving our scorer.</p>
        <a href="{{ route('sign-in.view') }}" class="btn btn-primary btn-block mt-6">Sign in</a>
    </x-auth-card>
</x-layouts.guest>
