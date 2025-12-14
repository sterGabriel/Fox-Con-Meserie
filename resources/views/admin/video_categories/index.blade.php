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
        <h2 class="text-lg font-semibold mb-4">Existing Categories</h2>

        @if($categories->isEmpty())
            <p class="text-sm text-slate-400">No categories yet.</p>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80">
                <tr>
                    <th class="px-4 py-2 text-left text-slate-400">ID</th>
                    <th class="px-4 py-2 text-left text-slate-400">Name</th>
                    <th class="px-4 py-2 text-left text-slate-400">Description</th>
                    <th class="px-4 py-2 text-left text-slate-400">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                @foreach($categories as $category)
                    <tr>
                        <td class="px-4 py-2 text-slate-400">{{ $category->id }}</td>
                        <td class="px-4 py-2 text-slate-100">{{ $category->name }}</td>
                        <td class="px-4 py-2 text-slate-300">
                            {{ $category->description ?: '—' }}
                        </td>
                        <td class="px-4 py-2 text-slate-300">
                            <a href="{{ route('video-categories.edit', $category) }}"
                               style="margin-right:8px;color:#38bdf8;font-size:13px;">
                                Edit
                            </a>

                            <form action="{{ route('video-categories.destroy', $category) }}"
                                  method="POST"
                                  style="display:inline"
                                  onsubmit="return confirm('Delete this category? All channels linked to it will lose the category.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="color:#f97316;font-size:13px;background:none;border:none;cursor:pointer;">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
