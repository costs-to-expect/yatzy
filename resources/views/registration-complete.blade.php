<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yatzy Game Scorer: Register</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body class="d-flex align-items">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="game-title text-center">
                        <h1 class="display-1">Yatzy</h1>
                        <h2 class="display-6">Game Scorer</h2>
                        powered by <a href="https://api.costs-to-expect.com">
                            <img src="{{ asset('images/logo.png') }}" width="64" height="64" alt="Costs to Expect Logo" title="Powered by the Costs to Expect API">
                            <span class="d-none">C</span>osts to Expect API
                        </a>
                    </div>

                    <div class="col-12 col-md-4 col-lg-3 mx-auto p-2">

                        <h4 class="text-center pt-3">All Done!</h4>

                        <p class="lead">Your account is ready, you are free to
                            <a href="{{ route('sign-in.view') }}">sign-in</a>
                            immediately and start scoring your Yatzy games, enjoy!</p>

                        <p>If you have any suggestions, reach out to us on
                            <a href="https://github.com/costs-to-expect/yatzy/issues">GitHub</a>, we are
                            always looking for help with improving our scorer.</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
