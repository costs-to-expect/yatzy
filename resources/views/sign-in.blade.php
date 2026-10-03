<x-layouts.guest :title="config('app.game.name').' Game Scorer: Sign-in'" :noindex="true">
    <x-auth-card title="Sign in" subtitle="Welcome back, pick up your game where you left it.">
        <form action="{{ route('sign-in.action') }}" method="POST" class="space-y-5">
            @csrf
            <x-field name="email" type="email" label="Email" help="Please enter your email address, we will never share it." :bag="$errors" :value="old('email')" autocomplete="email" required autofocus />
            <x-field name="password" type="password" label="Password" help="Please enter your password, we check it against the encrypted value in our database." :bag="$errors" autocomplete="current-password" required />

            <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-stone-800">
                <input type="checkbox" id="remember_me" name="remember_me" class="h-5 w-5 rounded border-stone-300 accent-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                Stay signed in for longer
            </label>

            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>

        <div class="mt-6 space-y-2 border-t border-stone-100 pt-5 text-sm text-stone-700">
            <p>Forgotten your password? You can <a href="{{ route('forgot-password.view') }}" class="text-link">create a new one</a>.</p>
            <p>If you don&rsquo;t have an account with Costs to Expect, you can <a href="{{ route('register.view') }}" class="text-link">register</a> to get access to this game scorer and the entire Costs to Expect service.</p>
        </div>
    </x-auth-card>
</x-layouts.guest>
