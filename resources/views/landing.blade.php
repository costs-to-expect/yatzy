<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
    <meta name="author" content="Dean Blackborough">
    <title>Yatzy: Game Scorer</title>

    <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet"/>
    <meta name="theme-color" content="#892b7c">
    <style>
        .bd-placeholder-img {
            font-size: 1.125rem;
            text-anchor: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        @media (min-width: 768px) {
            .bd-placeholder-img-lg {
                font-size: 3.5rem;
            }
        }

        .b-example-divider {
            height: 3rem;
            background-color: rgba(0, 0, 0, .1);
            border: solid rgba(0, 0, 0, .15);
            border-width: 1px 0;
            box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
        }

        .b-example-vr {
            flex-shrink: 0;
            width: 1.5rem;
            height: 100vh;
        }

        .bi {
            vertical-align: -.125em;
            fill: currentColor;
        }

        .nav-scroller {
            position: relative;
            z-index: 2;
            height: 2.75rem;
            overflow-y: hidden;
        }

        .nav-scroller .nav {
            display: flex;
            flex-wrap: nowrap;
            padding-bottom: 1rem;
            margin-top: -1px;
            overflow-x: auto;
            text-align: center;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .container {
            max-width: 960px;
        }

        .site-header {
            background-color: #000000;
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            backdrop-filter: saturate(180%) blur(20px);
        }
    </style>
</head>
<body>

<header class="site-header sticky-top py-1">
    <nav class="container d-flex flex-column flex-md-row justify-content-between">
        <a class="py-2 text-center" href="https://api.costs-to-expect.com" aria-label="Product">
            <img src="{{ asset('images/logo.png') }}" alt="Costs to Expect" width="48" height="48" />
        </a>
    </nav>
</header>

<main>
    <div class="position-relative overflow-hidden p-3 p-md-5 m-md-3 text-center bg-light">
        <div class="col-md-5 p-lg-5 mx-auto my-5">
            <h1 class="display-4 fw-normal">Yatzy Game Scorer</h1>
            <p class="lead fw-normal">A Game Scorer powered by the <br />Costs to Expect API.</p>
            <p class="lead fw-normal">Yep, you read that right, a game scorer, you still need
                to be social and play the game.</p>
            <a class="btn btn-outline-primary" href="{{ route('sign-in.view') }}">Sign-in</a>
            <a class="btn btn-outline-primary" href="{{ route('register.view') }}">Register</a>
        </div>
    </div>

    <div class="d-md-flex flex-md-equal w-100 my-md-3 ps-md-3">
        <div class="text-bg-dark me-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 py-3">
                <h2 class="display-5">No designated scorer</h2>
                <p class="lead">No more designated scorers, no more, "Do I have my threes?", everyone
                    gets and updates their own score sheet.</p>
            </div>
            <div class="bg-light shadow-sm mx-auto"
                 style="width: 80%; height: 500px; border-radius: 21px 21px 0 0;">
                <img src="{{ asset('images/score-sheet.png') }}" width="275" height="" alt="A screen shot of the score sheet for Yatzy" />
            </div>
        </div>
        <div class="bg-light me-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 p-3">
                <h2 class="display-5">All scores</h2>
                <p class="lead">You can see all the scores, all the time,
                    no need to bug the designated scorer.</p>
            </div>
            <div class="bg-dark shadow-sm mx-auto"
                 style="width: 80%; height: 500px; border-radius: 21px 21px 0 0;">
                <img src="{{ asset('images/player-scores.png') }}" width="275" height="" alt="A screen shot of the score sheet for Yatzy showing all player scores" />
            </div>
        </div>
    </div>

    <div class="d-md-flex flex-md-equal w-100 my-md-3 ps-md-3">
        <div class="text-bg-dark me-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 py-3">
                <h2 class="display-5">One account</h2>
                <p class="lead">Only one player needs an account, sharable public
                    score sheets for each player, tokens valid for the life of the game.</p>
            </div>
            <div class="bg-light shadow-sm mx-auto"
                 style="width: 80%; height: 400px; border-radius: 21px 21px 0 0;">
                <img src="{{ asset('images/management.png') }}" width="275" height="" alt="A screen shot of the basic admin controls" />
            </div>
        </div>
        <div class="bg-light me-md-3 pt-3 px-3 pt-md-5 px-md-5 text-center overflow-hidden">
            <div class="my-3 p-3">
                <h2 class="display-5">Stats (Coming soon)</h2>
                <p class="lead">All the stats you could possibly want are coming soon,
                    we are working out the best way to visualise everything.</p>
            </div>
        </div>
    </div>
</main>

<x-footer />

<script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>

</body>
</html>
