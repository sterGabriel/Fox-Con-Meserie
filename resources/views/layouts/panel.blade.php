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

    <aside class="w-64 bg-slate-950 border-r border-slate-800">
        <div class="h-16 flex items-center px-4 border-b border-slate-800">
            <span class="text-orange-500 font-bold text-xl">FOX PANEL</span>
        </div>

        <nav class="mt-4 text-sm space-y-1 px-2">
            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                🏠 Dashboard
            </a>

            <div class="mt-4">
                <div class="px-3 py-2 text-xs uppercase text-slate-500">Vod Channels</div>

                <a href="{{ route('vod-channels.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('vod-channels.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Vod Channels
                </a>

                <a href="{{ route('video-categories.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('video-categories.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Video Categories
                </a>

                <a href="{{ route('videos.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('videos.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Videos
                </a>

                <a href="{{ route('encoding-jobs.index') }}"
                   class="block px-5 py-2 rounded-lg {{ request()->routeIs('encoding-jobs.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    ▸ Encoding Jobs
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 bg-slate-900 min-h-screen">
        <header class="h-16 border-b border-slate-800 flex items-center justify-between px-8">
            <div class="text-sm text-slate-400">
                {{ Route::currentRouteName() }}
            </div>
            <div class="text-sm text-slate-300">
                Logged in as <span class="font-semibold">{{ Auth::user()->name ?? 'Admin' }}</span>
            </div>
        </header>

        <section class="p-8 pb-32">
            @yield('content')
        </section>

        {{-- TEST BAR VIZIBIL --}}
        <div style="position:fixed; bottom:20px; right:20px; z-index:999999; background:#111; color:#fff; padding:12px 16px; border-radius:8px;">
            TEST BAR VIZIBIL
        </div>
    </main>

</div>
</body>
</html>
