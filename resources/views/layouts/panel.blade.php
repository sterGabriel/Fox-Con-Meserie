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
    <link rel="stylesheet" href="{{ asset('assets/css/fox-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-topnav.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-subheader.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-cards.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-tables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/fox-light-theme.css') }}">
    <style>
        * { scrollbar-width: thin; scrollbar-color: #999 #e8e8e8; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #e8e8e8; }
        ::-webkit-scrollbar-thumb { background: #999; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #666; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-slideIn { animation: slideIn 0.3s ease-out; }
    </style>
</head>
<body style="margin: 0; padding: 0; background: #f4f5f7;">
<div style="display: flex; min-height: 100vh;">

    {{-- FOX SIDEBAR --}}
    <x-fox-sidebar />

    {{-- MAIN CONTENT AREA --}}
    <main style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
        {{-- FOX TOP NAVIGATION --}}
        @include('components.top-navigation')

        {{-- FOX SUB-HEADER (Server selector) --}}
        @include('components.sub-header')

        {{-- PAGE CONTENT --}}
        @php($fullWidth = View::hasSection('full_width'))
        <section style="flex: 1; overflow-y: auto; padding: {{ $fullWidth ? '0' : '24px' }}; background: #f4f5f7;">
            <div class="animate-slideIn" style="width: 100%; max-width: {{ $fullWidth ? 'none' : '1400px' }}; margin: {{ $fullWidth ? '0' : '0 auto' }};">
                @yield('content')
            </div>
        </section>
    </main>

</div>
<script src="{{ asset('assets/js/fox-sidebar.js') }}"></script>
</body>
</html>
