<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FOX PANEL</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-slate-100">
<div class="min-h-screen flex">

    {{-- SIDEBAR STÂNGA --}}
    <aside class="w-64 bg-slate-950 border-r border-slate-800">
        <div class="h-16 flex items-center px-4 border-b border-slate-800">
            <span class="text-orange-500 font-bold text-xl">FOX PANEL</span>
        </div>

        <nav class="mt-4 text-sm space-y-1 px-2">
            {{-- DASHBOARD --}}
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                🏠 Dashboard
            </a>

            {{-- VOD CHANNELS --}}
            <div class="mt-4">
                <div class="px-3 py-2 text-xs uppercase text-slate-500">VOD CHANNELS</div>

                <a href="{{ route('vod-channels.create') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('vod-channels.create') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Create Vod Channel
                </a>

                <a href="{{ route('vod-channels.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('vod-channels.index') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Vod Channels
                </a>

                <a href="{{ route('video-categories.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('video-categories.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Video Categories
                </a>

                {{-- VIDEO LIBRARY --}}
                <a href="{{ route('videos.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('videos.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Video Library
                </a>

                {{-- ENCODING QUEUE --}}
                <a href="{{ route('encoding-jobs.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('encoding-jobs.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Encoding Queue
                </a>
            </div>

            {{-- LIVE CHANNELS (placeholder) --}}
            <div class="mt-4">
                <div class="px-3 py-2 text-xs uppercase text-slate-500">LIVE CHANNELS</div>
                <span class="block px-5 py-2 text-slate-500 text-xs">
                    Add Live Stream / Live Streams – coming soon
                </span>
            </div>
        </nav>
    </aside>

    {{-- ZONA PRINCIPALĂ --}}
    <main class="flex-1 bg-slate-900">
        <header class="h-16 border-b border-slate-800 flex items-center justify-between px-8">
            <div class="text-sm text-slate-400">
                Dashboard
            </div>
            <div class="text-sm text-slate-300">
                Logged in as <span class="font-semibold">{{ Auth::user()->name ?? 'Admin' }}</span>
            </div>
        </header>

        <section class="p-8">
            @yield('content')
        </section>
    </main>

</div>
</body>
</html>
