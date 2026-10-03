@props([
    'title' => 'Yatzy Game Scorer',
    'description' => 'Yatzy Game Scorer by Costs to Expect',
    'noindex' => false,
    'bodyClass' => '',
])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="{{ $description }}">
    <meta name="author" content="Dean Blackborough">
    @if ($noindex)
        <meta name="robots" content="noindex,follow">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#057176">
    <title>{{ $title }}</title>
    {{ $head ?? '' }}
    <link rel="icon" sizes="48x48" href="{{ asset('images/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/'.config('app.version.css').'/app.css') }}">
</head>
<body class="min-h-screen bg-paper font-sans text-stone-900 antialiased {{ $bodyClass }}">
<x-icon-sprite />

{{ $slot }}

<script src="{{ asset('js/ui.js') }}?v={{ config('app.version.js') }}" defer></script>
@stack('scripts')
</body>
</html>
