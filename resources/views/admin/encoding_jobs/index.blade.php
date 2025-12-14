@extends('layouts.app')

@section('content')
    <div class="container mt-4">

        <h1 class="mb-3">
            Encoding Queue
        </h1>

        <p class="text-muted mb-4">
            Aici vezi toate joburile de encodare (un film după altul). Mai târziu de aici vom porni / opri manual
            encodarea și vom vedea progresul în timp real.
        </p>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                Encoding Jobs
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0 table-sm">
                    <thead>
                    <tr>
                        <th style="width: 60px">ID</th>
                        <th>Channel</th>
                        <th>Video</th>
                        <th style="width: 120px">Status</th>
                        <th style="width: 80px">Progress</th>
                        <th style="width: 180px">Created</th>
                        <th style="width: 180px">Started</th>
                        <th style="width: 180px">Finished</th>
                        <th>Error</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>
                                @if ($job->channel)
                                    [#{{ $job->channel->id }}] {{ $job->channel->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($job->video)
                                    [#{{ $job->video->id }}] {{ $job->video->title }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ strtoupper($job->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $job->progress }}%
                            </td>
                            <td>{{ $job->created_at }}</td>
                            <td>{{ $job->started_at ?? '—' }}</td>
                            <td>{{ $job->finished_at ?? '—' }}</td>
                            <td class="text-danger" style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $job->error_message ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-3 text-muted">
                                No encoding jobs yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($jobs, 'links'))
                <div class="card-footer">
                    {{ $jobs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
