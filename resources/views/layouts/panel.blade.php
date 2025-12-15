<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🦊 FOX IPTV PANEL</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { scrollbar-width: thin; scrollbar-color: #f97316 #1e293b; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #f97316; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #ea580c; }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse-glow { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-slideIn { animation: slideIn 0.3s ease-out; }
        .animate-pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-[radial-gradient(1200px_circle_at_20%_-10%,rgba(59,130,246,0.18),transparent_45%),radial-gradient(900px_circle_at_80%_0%,rgba(34,197,94,0.10),transparent_40%)]">
<div class="min-h-screen flex">

    {{-- SIDEBAR --}}
    <aside class="w-72 bg-gradient-to-b from-slate-900 to-slate-950 border-r border-orange-500/20 shadow-2xl">
        <div class="h-20 flex items-center px-6 border-b border-orange-500/20 bg-gradient-to-r from-orange-600/10 to-transparent">
            <span class="text-orange-500 font-black text-2xl drop-shadow-lg">🦊 FOX</span>
            <span class="text-slate-400 font-semibold ml-2">IPTV</span>
        </div>

        <nav class="mt-6 text-sm space-y-2 px-3">
            <a href="{{ route('dashboard') }}"
               class="block px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg shadow-orange-600/20 transform scale-105' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white' }}">
                <span class="text-lg">📊</span> Dashboard
            </a>

            <div class="mt-6 pt-4 border-t border-slate-800">
                <div class="px-4 py-2 text-xs uppercase font-black text-orange-500 tracking-widest opacity-75">🎬 VOD</div>

                <a href="{{ route('vod-channels.index') }}"
                   class="block px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('vod-channels.*') ? 'bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg shadow-orange-600/20' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white' }}">
                    <span class="text-lg">📺</span> VOD Channels
                </a>

                <a href="{{ route('video-categories.index') }}"
                   class="block px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('video-categories.*') ? 'bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg shadow-orange-600/20' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white' }}">
                    <span class="text-lg">🏷️</span> Categories
                </a>

                <a href="{{ route('videos.index') }}"
                   class="block px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('videos.*') ? 'bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg shadow-orange-600/20' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white' }}">
                    <span class="text-lg">🎥</span> Videos
                </a>

                <a href="{{ route('encoding-jobs.index') }}"
                   class="block px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('encoding-jobs.*') ? 'bg-gradient-to-r from-orange-600 to-orange-500 text-white shadow-lg shadow-orange-600/20' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white' }}">
                    <span class="text-lg">⚙️</span> Encoding Jobs
                </a>
            </div>

            {{-- User Profile Bottom --}}
            <div class="absolute bottom-6 left-3 right-3">
                <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 hover:border-orange-500/30 transition-all">
                    <div class="text-xs text-slate-400">👤 Logged in as</div>
                    <div class="text-sm font-bold text-orange-400 mt-1">{{ Auth::user()->name ?? 'Admin' }}</div>
                </div>
            </div>
        </nav>
    </aside>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 min-h-screen overflow-hidden flex flex-col">
        {{-- HEADER --}}
        <header class="h-20 border-b border-slate-800/50 bg-gradient-to-r from-slate-900/50 to-slate-950/50 backdrop-blur-sm flex items-center justify-between px-8 sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <div class="text-3xl">{{ match(Route::currentRouteName()) {
                    'dashboard' => '📊',
                    default => preg_match('/vod-channels/', Route::currentRouteName()) ? '📺' : '📄'
                } }}</div>
                <div>
                    <div class="text-xs uppercase text-slate-500 font-semibold">Current Page</div>
                    <div class="text-lg font-bold text-slate-200">{{ ucfirst(str_replace('-', ' ', Route::currentRouteName())) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="px-4 py-2 rounded-lg bg-slate-800/50 border border-slate-700">
                    <span class="text-xs text-slate-400">🕐 Last updated:</span>
                    <span class="text-sm font-semibold text-slate-200 ml-2">{{ now()->format('H:i') }}</span>
                </div>
            </div>
        </header>

        {{-- CONTENT --}}
        <section class="flex-1 overflow-y-auto p-6">
            <div class="mx-auto max-w-7xl animate-slideIn">
                @yield('content')
            </div>
        </section>
    </main>

</div>
</body>
</html>
