@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Video Categories</h1>

        <a href="{{ route('vod-channels.index') }}"
           class="text-sm text-slate-400 hover:text-slate-200">
            ← Back to Vod Channels
        </a>
    </div>

    {{-- Mesaje flash --}}
    @if(session('success'))
        <div class="mb-4 rounded-xl bg-emerald-900/40 border border-emerald-700 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-900/40 border border-red-700 px-4 py-3 text-sm text-red-100">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM: ADD CATEGORY --}}
    <div class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold mb-4">Add Category</h2>

        <form action="{{ route('video-categories.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">
                    Name
                </label>
                <input type="text"
                       name="name"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                       value="{{ old('name') }}"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">
                    Description (optional)
                </label>
                <textarea name="description"
                          rows="3"
                          class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100">{{ old('description') }}</textarea>
            </div>

            <div class="pt-2">
                {{-- BUTON VIZIBIL --}}
                <button type="submit"
                        style="background:#f97316;color:white;border:none;padding:8px 16px;border-radius:6px;font-size:14px;cursor:pointer;">
                    Save Category
                </button>
            </div>
        </form>
    </div>

    {{-- EXISTING CATEGORIES --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold mb-4">Existing Categories ({{ $categories->count() }})</h2>

        @if($categories->isEmpty())
            <p class="text-sm text-slate-400">No categories yet. <a href="{{ route('video-categories.create') }}" class="text-blue-400 hover:text-blue-300">Create one</a></p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800/50 border-b border-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold w-12">ID</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold w-40">Name</th>
                        <th class="px-4 py-3 text-left text-slate-400 font-semibold flex-1">Description</th>
                        <th class="px-4 py-3 text-center text-slate-400 font-semibold w-32">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                    @foreach($categories as $category)
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="px-4 py-3 text-slate-500">{{ $category->id }}</td>
                            <td class="px-4 py-3 text-slate-200 font-semibold">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ Str::limit($category->description ?? '—', 60) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex gap-2 justify-center">
                                    <a href="{{ route('video-categories.edit', $category) }}"
                                       class="px-3 py-1 text-xs text-blue-400 hover:text-blue-300 transition">
                                        Edit
                                    </a>

                                    <form action="{{ route('video-categories.destroy', $category) }}"
                                          method="POST"
                                          style="display:inline"
                                          onsubmit="return confirm('Delete &quot;{{ $category->name }}&quot;? All videos will lose this category.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1 text-xs text-red-400 hover:text-red-300 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
