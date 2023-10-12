<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Yatzy Game Scorer by Costs to Expect">
        <meta name="author" content="Dean Blackborough">
        <title>Yatzy Game Scorer: New Player</title>
        <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" />
    </head>
    <body>
        <x-offcanvas active="players" />

        <div class="col-lg-8 mx-auto p-3 py-md-5">

            <main>
                <form action="{{ route('player.create.action') }}" method="POST" class="col-12 col-md-4 col-lg-4 mx-auto p-2">
                    <div class="mb-3">
                        <h2>New Player</h2>
                        <p>Add a new player, they will be selectable as a player in all new games.</p>

                        @csrf

                        <div class="mt-3 mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control @if($errors !== null && array_key_exists('name', $errors)) is-invalid @endif" id="name" aria-describedby="name-help" required value="{{ old('name') }}" />
                            <div id="name-help" class="form-text">Please enter the name of the new player.</div>
                            @if($errors !== null && array_key_exists('name', $errors))
                                <div class="invalid-feedback">
                                    @foreach ($errors['name']['errors'] as $error)
                                        {{ $error }}
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <input type="hidden" name="description" value="{{ old('description', 'New player - Added via the Yatzy App') }}" />
                    <button type="submit" class="btn btn-primary w-100">Add Player</button>
                </form>
            </main>
            <x-footer />
        </div>
        <script src="{{ asset('node_modules/bootstrap/dist/js/bootstrap.js') }}" defer></script>
    </body>
</html>
