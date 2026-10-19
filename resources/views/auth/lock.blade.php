<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('ICD Admin locked') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.pwa')
</head>
<body class="font-sans antialiased" style="min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: #f4f5f7;">
    {{-- The lock screen: the page asked for opens again once unlocked --}}
    @include('layouts.partials.lock-panel', ['overlay' => false])
</body>
</html>
