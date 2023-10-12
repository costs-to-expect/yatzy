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

                    <form action="{{ route('create-password.action') }}" method="POST" class="col-12 col-md-4 col-lg-3 mx-auto p-2">

                        @csrf

                        <h4 class="text-center">Create an Account</h4>

                        @if ($failed !== null)
                            <p class="alert alert-danger">We were unable to create your account, the API returned the
                                following error "{{ $failed }}". Please check our <a href="https://status.costs-to-expect.com">status</a>
                                page and try again later.</p>
                        @endif

                        <div class="mt-3 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control @if($errors !== null && array_key_exists('password', $errors)) is-invalid @endif" id="password" aria-describedby="password-help" required value="{{ old('password') }}" />
                            <div id="password-help" class="form-text">Please enter a password, at least 12 characters please, <em>your password will be hashed</em>.</div>
                            @if($errors !== null && array_key_exists('password', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['password'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm password</label>
                            <input type="password" name="password_confirmation" class="form-control @if($errors !== null && array_key_exists('password_confirmation', $errors)) is-invalid @endif" id="password_confirmation" aria-describedby="password_confirmation-help" required value="{{ old('password_confirmation') }}" />
                            <div id="password_confirmation-help" class="form-text">Please enter your password again</div>
                            @if($errors !== null && array_key_exists('password_confirmation', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['password_confirmation'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <input type="hidden" name="token" value="{{ old('token', $token) }}" />
                        <input type="hidden" name="email" value="{{ old('email', $email) }}" />
                        <button type="submit" class="btn btn-primary w-100">Set Password</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
