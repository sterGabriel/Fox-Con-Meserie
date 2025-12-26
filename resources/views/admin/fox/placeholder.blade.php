@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <h1 style="margin:0;font-size:24px;font-weight:800;">{{ $title ?? 'Page' }}</h1>
    </div>

    <div class="fox-table-container" style="padding:20px;">
        <div style="font-size:13px;color:#666;">
            This page exists in the FOX navigation spec, but is not implemented yet.
        </div>
    </div>
@endsection
