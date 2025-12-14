@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Create Video</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('videos.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="title">Title</label>
            <input id="title" name="title" type="text" class="form-control"
                   value="{{ old('title') }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="file_path">File Path</label>
            <input id="file_path" name="file_path" type="text" class="form-control"
                   value="{{ old('file_path') }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="video_category_id">Video Category</label>
            <select id="video_category_id" name="video_category_id" class="form-control">
                <option value="">-- no category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ old('video_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Video
        </button>
    </form>
@endsection
@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Create Video</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('videos.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="title">Title</label>
            <input id="title" name="title" type="text" class="form-control"
                   value="{{ old('title') }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="file_path">File Path</label>
            <input id="file_path" name="file_path" type="text" class="form-control"
                   value="{{ old('file_path') }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="video_category_id">Video Category</label>
            <select id="video_category_id" name="video_category_id" class="form-control">
                <option value="">-- no category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ old('video_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Video
        </button>
    </form>
@endsection
@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Edit Video [{{ $video->id }}]</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('videos.update', $video) }}">
        @csrf
        @method('PATCH')

        <div class="form-group mb-3">
            <label for="title">Title</label>
            <input id="title" name="title" type="text" class="form-control"
                   value="{{ old('title', $video->title) }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="file_path">File Path</label>
            <input id="file_path" name="file_path" type="text" class="form-control"
                   value="{{ old('file_path', $video->file_path) }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="video_category_id">Video Category</label>
            <select id="video_category_id" name="video_category_id" class="form-control">
                <option value="">-- no category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ (string)old('video_category_id', $video->video_category_id) === (string)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>

        <a href="{{ route('videos.index') }}" class="btn btn-secondary ms-2">
            Back
        </a>
    </form>
@endsection
