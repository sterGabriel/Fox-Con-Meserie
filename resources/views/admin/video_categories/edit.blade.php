@extends('layouts.panel')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Edit Category</h1>

        <a href="{{ route('video-categories.index') }}"
           class="text-sm text-slate-400 hover:text-slate-200">
            ← Back to Categories
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl bg-red-900/40 border border-red-700 px-4 py-3 text-sm text-red-100">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('video-categories.update', $category) }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">
                    Name
                </label>
                <input type="text"
                       name="name"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                       value="{{ old('name', $category->name) }}"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">
                    Description (optional)
                </label>
                <textarea name="description"
                          rows="3"
                          class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="pt-2">
                <button type="submit"
                        style="background:#f97316;color:white;border:none;padding:8px 16px;border-radius:6px;font-size:14px;cursor:pointer;">
                    Save changes
                </button>
            </div>
        </form>
    </div>
@endsection
