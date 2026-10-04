<x-layouts.guest :title="config('app.game.name').' Game Scorer: Create new password'" :noindex="true">
    <x-auth-card title="Create a new password" subtitle="Choose a new password for your account.">
        @if ($failed !== null)
            <x-alert type="error" class="mb-5">We were unable to set your new password, the API returned the following error &ldquo;{{ $failed }}&rdquo;. You can <a href="{{ route('forgot-password.view') }}" class="text-link">request a new link</a> or check our <a href="https://status.costs-to-expect.com" class="text-link">status</a> page.</x-alert>
        @endif
        @if ($errors !== null && (array_key_exists('encrypted_token', $errors) || array_key_exists('email', $errors)))
            <x-alert type="error" class="mb-5">The link you followed is not valid, you can <a href="{{ route('forgot-password.view') }}" class="text-link">request a new link</a>.</x-alert>
        @endif

        <form action="{{ route('create-new-password.action') }}" method="POST" class="space-y-5">
            @csrf
            <x-field name="password" type="password" label="Password" help="Please enter a password, at least 12 characters please, your password will be hashed." :bag="$errors" autocomplete="new-password" required autofocus />
            <x-field name="password_confirmation" type="password" label="Confirm password" help="Please enter your password again." :bag="$errors" autocomplete="new-password" required />
            <input type="hidden" name="encrypted_token" value="{{ $encrypted_token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="btn btn-primary btn-block">Set password</button>
        </form>
    </x-auth-card>
</x-layouts.guest>
