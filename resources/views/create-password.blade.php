<x-layouts.guest :title="config('app.game.name').' Game Scorer: Create password'" :noindex="true">
    <x-auth-card title="Create your password" subtitle="The last step, choose a password for your account.">
        @if ($failed !== null)
            <x-alert type="error" class="mb-5">We were unable to create your account, the API returned the following error &ldquo;{{ $failed }}&rdquo;. Please check our <a href="https://status.costs-to-expect.com" class="text-link">status</a> page and try again later.</x-alert>
        @endif

        <form action="{{ route('create-password.process.action') }}" method="POST" class="space-y-5">
            @csrf
            <x-field name="password" type="password" label="Password" help="Please enter a password, at least 12 characters please, your password will be hashed." :bag="$errors" autocomplete="new-password" required autofocus />
            <x-field name="password_confirmation" type="password" label="Confirm password" help="Please enter your password again." :bag="$errors" autocomplete="new-password" required />
            <input type="hidden" name="token" value="{{ old('token', $token) }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button type="submit" class="btn btn-primary btn-block">Set password</button>
        </form>
    </x-auth-card>
</x-layouts.guest>
