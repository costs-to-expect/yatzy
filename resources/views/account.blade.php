<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yatzy Game Scorer: Account</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <x-offcanvas active="account" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">
            <main>
                <h2>Your account</h2>

                <p class="lead">Manage your account below, profile updates coming soon.</p>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Name</strong>: {{ $user['name'] }}
                    </li>
                    <li class="list-group-item">
                        <strong>Email</strong>: {{ $user['email'] }}
                    </li>
                </ul>

                @if ($job !== null)
                    @if ($job === 'delete-yatzy-account')
                        <div class="alert alert-dark mt-5" role="alert">
                            <h4 class="alert-heading">Delete started!</h4>
                            <p>A job has been added to delete your Yatzy account, we should be done in a minute or two!</p>
                            <p>You have been logged out, if you refresh you will be back at the login screen.</p>
                            <p>You will get an email when your account has been deleted.</p>
                        </div>
                    @endif

                    @if ($job === 'delete-account')
                        <div class="alert alert-dark mt-5" role="alert">
                            <h4 class="alert-heading">Delete started!</h4>
                            <p>A job has been added to delete your account, we should be done in a minute or two!</p>
                            <p>You have been logged out, if you refresh you will be back at the login screen.</p>
                            <p>You will get an email when your account has been deleted.</p>
                        </div>
                    @endif
                @endif

                <h3 class="mt-5">Delete Yatzy account</h3>

                <p class="lead">You can delete your Yatzy account <a href="{{ route('account.confirm-delete-yatzy-account') }}">here</a>.</p>

                <h3 class="mt-5">Delete Costs to Expect account</h3>

                <p class="lead">You can delete your entire Costs to Expect account <a href="{{ route('account.confirm-delete-account') }}">here</a>.</p>
            </main>
            <x-footer />
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
