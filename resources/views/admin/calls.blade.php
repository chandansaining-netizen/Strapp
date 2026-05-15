@extends('layouts.admin')
@section('title','Call Logs')
@section('content')
<h4 class="fw-bold mb-4">Call Logs</h4>
<div class="table-responsive">
    <table class="table table-dark table-hover">
        <thead><tr><th>Time</th><th>Room</th><th>Type</th><th>User 1</th><th>User 2</th><th>Duration</th></tr></thead>
        <tbody>
        @forelse($calls as $call)
        <tr>
            <td><small>{{ $call->created_at->format('M d H:i') }}</small></td>
            <td><code>{{ $call->room_code }}</code></td>
            <td><span class="badge {{ $call->type==='video'?'bg-primary':'bg-success' }}">{{ ucfirst($call->type) }}</span></td>
            <td>{{ $call->user1_id ? 'User #'.$call->user1_id : 'Guest' }}</td>
            <td>{{ $call->user2_id ? 'User #'.$call->user2_id : 'Guest' }}</td>
            <td>{{ gmdate('H:i:s', $call->duration_seconds) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No calls yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $calls->links() }}
@endsection