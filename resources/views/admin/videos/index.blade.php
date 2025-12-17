@extends('layouts.panel')

@section('content')
    <h1 class="mb-4">Video Library</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM PT. SETARE CATEGORIE LA MAI MULTE VIDEOURI --}}
    <form method="POST" action="{{ route('videos.bulk-category') }}">
        @csrf

        <div class="d-flex align-items-center mb-3" style="gap: 10px;">
            <span>Set category for selected:</span>

            <select name="video_category_id" class="form-control" style="width: 220px; max-width: 50%;">
                <option value="">-- no category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-primary">
                Apply to selected
            </button>

            <a href="{{ route('videos.create') }}" class="btn btn-sm btn-secondary ms-auto">
                + Create Video
            </a>
        </div>

        <table class="table table-striped table-sm">
            <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="check-all">
                </th>
                <th style="width: 60px">ID</th>
                <th>Title</th>
                <th>File Path</th>
                <th style="width: 180px">Category</th>
                <th style="width: 120px">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($videos as $video)
                <tr>
                    <td>
                        <input type="checkbox" name="video_ids[]" value="{{ $video->id }}">
                    </td>
                    <td>{{ $video->id }}</td>
                    <td>{{ $video->title }}</td>
                    <td>{{ $video->file_path }}</td>
                    <td>{{ $video->category->name ?? '— no category —' }}</td>
                    <td>
                        <a href="{{ route('videos.edit', $video) }}" class="btn btn-sm btn-outline-secondary">
                            Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-3">
                        No videos found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </form>

    <script>
        // bifa "select all"
        document.getElementById('check-all')?.addEventListener('change', function (e) {
            const checked = e.target.checked;
            document.querySelectorAll('input[name="video_ids[]"]').forEach(cb => cb.checked = checked);
        });
    </script>
@endsection
