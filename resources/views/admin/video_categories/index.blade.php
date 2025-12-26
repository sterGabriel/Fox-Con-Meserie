@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <h1 style="margin:0;font-size:24px;font-weight:800;">Video Categories</h1>

        <a href="{{ route('vod-channels.index') }}" style="font-size:13px;color:#2563eb;text-decoration:none;">
            ← Back to Vod Channels
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;border-left:4px solid #16a34a;">
            <div style="font-size:13px;color:#166534;font-weight:600;">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="fox-table-container" style="padding:12px 16px;margin-bottom:16px;border-left:4px solid #dc2626;">
            <div style="font-size:13px;color:#991b1b;font-weight:700;margin-bottom:6px;">Fix the following:</div>
            <ul style="margin:0;padding-left:18px;color:#991b1b;font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ADD CATEGORY --}}
    <div class="fox-table-container" style="padding:20px;margin-bottom:20px;">
        <h2 style="margin:0 0 12px 0;font-size:16px;font-weight:800;">Add Category</h2>

        <form action="{{ route('video-categories.store') }}" method="POST">
            @csrf

            <div style="margin-bottom:12px;">
                <label for="category_name" style="display:block;font-size:12px;font-weight:700;color:#666;margin-bottom:6px;">Name</label>
                <input id="category_name" type="text" name="name" value="{{ old('name') }}" required
                       style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;">
            </div>

            <div style="margin-bottom:14px;">
                <label for="category_description" style="display:block;font-size:12px;font-weight:700;color:#666;margin-bottom:6px;">Description (optional)</label>
                <textarea id="category_description" name="description" rows="3"
                          style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;resize:vertical;">{{ old('description') }}</textarea>
            </div>

            <button type="submit"
                    style="background:var(--fox-blue);color:#fff;border:0;padding:10px 14px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">
                Save Category
            </button>
        </form>
    </div>

    {{-- EXISTING CATEGORIES --}}
    <div class="fox-table-container" style="padding:20px;">
        <h2 style="margin:0 0 12px 0;font-size:16px;font-weight:800;">Existing Categories ({{ $categories->count() }})</h2>

        @if($categories->isEmpty())
            <div style="font-size:13px;color:#666;">No categories yet.</div>
        @else
            <div style="overflow-x:auto;">
                <table class="fox-table">
                    <thead>
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th style="width:240px;">Name</th>
                        <th>Description</th>
                        <th style="width:160px;text-align:center;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td style="font-weight:700;">{{ $category->name }}</td>
                            <td style="color:#666;">{{ Str::limit($category->description ?? '—', 60) }}</td>
                            <td style="text-align:center;white-space:nowrap;">
                                <a href="{{ route('video-categories.edit', $category) }}" class="fox-action-btn edit" title="Edit">✎</a>
                                <a href="{{ route('admin.video_categories.browse', $category) }}" class="fox-action-btn start" title="Browse & Import">📁</a>
                                <form action="{{ route('video-categories.destroy', $category) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete &quot;{{ $category->name }}&quot;? All videos will lose this category.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fox-action-btn delete" title="Delete">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
