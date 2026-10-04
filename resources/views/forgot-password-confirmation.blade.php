<x-layouts.guest :title="config('app.game.name').' Game Scorer: Email sent'" :noindex="true">
    <x-auth-card title="Email sent!">
        <p class="text-lg text-stone-800">We have emailed you a link to create a new password, please give it a minute or two to make it to your inbox.</p>
        <p class="mt-3 text-stone-700">If the email doesn&rsquo;t arrive, check your junk folder and then <a href="{{ route('forgot-password.view') }}" class="text-link">try again</a>.</p>
    </x-auth-card>
</x-layouts.guest>
