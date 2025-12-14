@extends('layouts.panel')

@section('content')
    <h1 class="text-2xl font-semibold mb-2">Browse Server Files</h1>
    <p class="text-sm text-slate-400 mb-4">
        Selectează fișierul video de pe server. La click pe „Select”, calea va fi trimisă înapoi în formularul „Add Video”.
    </p>

    <div class="mb-3 text-xs text-slate-400">
        Current path: <code>{{ $currentPath }}</code>
    </div>

    <div class="mb-4">
        @if ($currentPath !== $basePath)
            <a href="{{ route('file-browser.index', ['path' => $parentPath]) }}" class="text-sm text-blue-600">
                ↑ Go up (..)
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-900/80">
                <tr>
                    <th class="px-4 py-3 text-left text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-slate-400">Type</th>
                    <th class="px-4 py-3 text-left text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dirs as $dir)
                    <tr class="border-t border-slate-800">
                        <td class="px-4 py-2">
                            📁
                            <a href="{{ route('file-browser.index', ['path' => $dir['path']]) }}">
                                {{ $dir['name'] }}
                            </a>
                        </td>
                        <td class="px-4 py-2">Directory</td>
                        <td class="px-4 py-2 text-slate-500">—</td>
                    </tr>
                @endforeach

                @foreach ($files as $file)
                    <tr class="border-t border-slate-800">
                        <td class="px-4 py-2">🎬 {{ $file['name'] }}</td>
                        <td class="px-4 py-2">File</td>
                        <td class="px-4 py-2">
                            <button
                                type="button"
                                class="px-3 py-1 text-xs rounded bg-black text-white"
                                onclick="selectFile('{{ $file['path'] }}')"
                            >
                                Select
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        function selectFile(path) {
            if (window.opener && window.opener.document) {
                const input = window.opener.document.querySelector('input[name="file_path"]');
                if (input) {
                    input.value = path;
                }
                window.close();
            } else {
                alert('Nu am găsit formularul părinte.');
            }
        }
    </script>
@endsection
