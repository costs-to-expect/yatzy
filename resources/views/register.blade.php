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

                    <form action="{{ route('register.action') }}" method="POST" class="col-12 col-md-4 col-lg-3 mx-auto p-2">

                        @csrf

                        <h4 class="text-center">Create an Account</h4>

                        @if ($failed !== null)
                            <p class="alert alert-danger">We were unable to create your account, the API returned the
                                following error "{{ $failed }}". Please check our <a href="https://status.costs-to-expect.com">status</a>
                                page and try again later.</p>
                        @endif

                        <div class="mt-3 mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control @if($errors !== null && array_key_exists('name', $errors)) is-invalid @endif" id="name" aria-describedby="name-help" required value="{{ old('name') }}" />
                            <div id="name-help" class="form-text">Please enter a name, <em>any name will do</em>.</div>
                            @if($errors !== null && array_key_exists('name', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['name']['errors'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @if($errors !== null && array_key_exists('email', $errors)) is-invalid @endif" id="email" aria-describedby="email-help" required value="{{ old('email') }}" />
                            <div id="email-help" class="form-text">Please enter your email address, <em>we will never share
                                    your email address</em>.</div>
                            @if($errors !== null && array_key_exists('email', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['email']['errors'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
