<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CAL MARO – IPTV Panel</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32x32.svg') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16x16.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/favicon/apple-touch-icon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('assets/favicon/favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- ENTERPRISE THEME loaded inline to bypass Tailwind purge --}}
    <link rel="stylesheet" href="{{ asset('assets/css/enterprise-theme.css') }}?v=2">
</head>
<body style="margin: 0; padding: 0; background: #f4f5f7;">

{{-- UNIFIED NAVBAR COMPONENT (includes sidebar, top nav, sub-header, and main content wrapper) --}}
@include('components.navbar')

</body>
</html>
