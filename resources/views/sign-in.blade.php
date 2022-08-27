<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yatzy Game Scorer: Sign-in</title>
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

                    <form action="{{ route('sign-in.process') }}" method="POST" class="col-12 col-md-4 col-lg-3 mx-auto p-2">

                        @csrf

                        <div class="mt-3 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @if($errors !== null && array_key_exists('email', $errors)) is-invalid @endif" id="email" aria-describedby="email-help" required value="{{ old('email') }}" />
                            <div id="email-help" class="form-text">Please enter your email address, <em>we will never share
                                    your email address</em>.</div>
                            @if($errors !== null && array_key_exists('email', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['email'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @if($errors !== null && array_key_exists('password', $errors)) is-invalid @endif" id="password" aria-describedby="password-help" required value="" />
                            <div id="password-help" class="form-text">Please enter your password, <em>we will check this
                                    against the encrypted value in our database</em>.</div>
                            @if($errors !== null && array_key_exists('password', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['password'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me">
                            <label class="form-check-label" for="remember_me">Check to stay signed-in for longer</label>
                        </div>

                        <div class="mb-3">
                            <p>If you don't have an account with Costs to Expect, you can
                                <a href="{{ route('register.view') }}">register</a> to get
                                access to this Game Scorer and the entire Costs to Expect service.</p>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Sign-in</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
