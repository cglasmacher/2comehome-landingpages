<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (isset($page['props']['page']['seo']['title']))
        <title>{{ $page['props']['page']['seo']['title'] }}</title>
    @else
        <title>{{ $page['props']['page']['title'] ?? config('app.name') }}</title>
    @endif

    @if (isset($page['props']['page']['seo']['description']))
        <meta name="description" content="{{ $page['props']['page']['seo']['description'] }}">
    @endif

    <link rel="icon" href="/images/favicon-2comehome.png" sizes="32x32">
    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="antialiased bg-(--color-background) text-(--color-foreground)">
    @inertia
</body>
</html>
