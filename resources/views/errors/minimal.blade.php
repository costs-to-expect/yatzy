{{-- Every error page uses this: a short message in the look of the app. It stands on its own (no components that call the
     API or read the session) so it still works when the thing that broke is the app itself. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') | Yatzy Game Scorer</title>
    <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/'.config('app.version.css', 'v1.0.0').'/app.css') }}">
</head>
<body class="flex min-h-screen items-center justify-center bg-paper px-4 font-sans text-stone-900 antialiased">
    <main class="w-full max-w-md text-center">
        <p class="text-6xl font-extrabold tracking-tight text-brand-700">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-extrabold tracking-tight">@yield('message')</h1>
        @hasSection('detail')
            <p class="mt-2 text-stone-600">@yield('detail')</p>
        @endif
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to the start</a>
            <a href="https://status.costs-to-expect.com" class="btn btn-secondary">Service status</a>
        </div>
    </main>
</body>
</html>
