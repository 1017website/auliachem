<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="fi min-h-screen">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }}</title>

    @filamentStyles
    @vite('resources/css/filament/admin/theme.css')
</head>
<body class="auliachem-auth-body fi-body antialiased">
    {{ $slot }}

    @filamentScripts
    @livewire('notifications')
</body>
</html>
