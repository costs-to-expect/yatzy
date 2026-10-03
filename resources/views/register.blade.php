<x-layouts.guest :title="config('app.game.name').' Game Scorer: Register'" :noindex="true">
    <x-auth-card title="Create an account" subtitle="One account for the game scorer and everything else from Costs to Expect.">
        @if ($failed !== null)
            <x-alert type="error" class="mb-5">We were unable to create your account, the API returned the following error &ldquo;{{ $failed }}&rdquo;. Please check our <a href="https://status.costs-to-expect.com" class="text-link">status</a> page and try again later.</x-alert>
        @endif

        <form action="{{ route('register.action') }}" method="POST" class="space-y-5">
            @csrf
            <x-field name="name" label="Name" help="Please enter a name, any name will do." :bag="$errors" :value="old('name')" autocomplete="name" required autofocus />
            <x-field name="email" type="email" label="Email" help="Please enter your email address, we will never share it." :bag="$errors" :value="old('email')" autocomplete="email" required />
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p class="mt-6 border-t border-stone-100 pt-5 text-sm text-stone-700">Already registered? <a href="{{ route('sign-in.view') }}" class="text-link">Sign in</a>.</p>
    </x-auth-card>
</x-layouts.guest>
