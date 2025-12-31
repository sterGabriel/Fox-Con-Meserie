@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <h1 style="margin:0;font-size:24px;font-weight:800;">Edit Category</h1>

        <a href="{{ route('video-categories.index') }}" style="font-size:13px;color:#2563eb;text-decoration:none;">
            ← Back to Categories
        </a>
    </div>

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

    <div class="fox-table-container" style="padding:20px;">
        <form action="{{ route('video-categories.update', $category) }}" method="POST">
            @csrf
            @method('PATCH')

            <div style="margin-bottom:12px;">
                <label for="category_name" style="display:block;font-size:12px;font-weight:700;color:#666;margin-bottom:6px;">Name</label>
                <input id="category_name" type="text" name="name" value="{{ old('name', $category->name) }}" required
                       style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;">
            </div>

            <div style="margin-bottom:14px;">
                <label for="category_description" style="display:block;font-size:12px;font-weight:700;color:#666;margin-bottom:6px;">Description (optional)</label>
                <textarea id="category_description" name="description" rows="3"
                          style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;resize:vertical;">{{ old('description', $category->description) }}</textarea>
            </div>

            <div style="margin-bottom:14px;">
                <label for="category_source_path" style="display:block;font-size:12px;font-weight:700;color:#666;margin-bottom:6px;">Source path (optional, must be in /media)</label>
                <input id="category_source_path" type="text" name="source_path" value="{{ old('source_path', $category->source_path) }}"
                       placeholder="ex: /media/MUZICA"
                       style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#333;">
                <div style="margin-top:6px;font-size:12px;color:#666;">Este folosit pentru mutare/copiere din Rename MUZICA și pentru scan/import pe categorie.</div>
            </div>

            <button type="submit"
                    style="background:var(--fox-blue);color:#fff;border:0;padding:10px 14px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">
                Save changes
            </button>
        </form>
    </div>
@endsection
