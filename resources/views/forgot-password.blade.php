<x-layouts.guest :title="config('app.game.name').' Game Scorer: Forgot password'" :noindex="true">
    <x-auth-card title="Forgot your password?" subtitle="We will email you a link to create a new one.">
        @if ($failed !== null)
            <x-alert type="error" class="mb-5">We were unable to start your password reset, the API returned the following error &ldquo;{{ $failed }}&rdquo;. Please check our <a href="https://status.costs-to-expect.com" class="text-link">status</a> page and try again later.</x-alert>
        @endif

        <form action="{{ route('forgot-password.action') }}" method="POST" class="space-y-5">
            @csrf
            <x-field name="email" type="email" label="Email" help="Please enter the email address for your account." :bag="$errors" :value="old('email')" autocomplete="email" required autofocus />
            <button type="submit" class="btn btn-primary btn-block">Reset password</button>
        </form>

        <p class="mt-6 border-t border-stone-100 pt-5 text-sm"><a href="{{ route('sign-in.view') }}" class="text-link">Back to sign-in</a></p>
    </x-auth-card>
</x-layouts.guest>
