@extends('layouts.admin')
@section('title','Live Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Live Active Rooms</h4>
    <span class="badge bg-success">Auto-refresh every 10s</span>
</div>
<div class="table-responsive">
    <table class="table table-dark table-hover">
        <thead><tr>
            <th>Room</th><th>Type</th><th>User 1</th><th>User 2</th><th>Started</th><th>Actions</th>
        </tr></thead>
        <tbody>
        @forelse($rooms as $room)
        <tr>
            <td><code>{{ $room->room_code }}</code></td>
            <td><span class="badge {{ $room->type==='video'?'bg-primary':($room->type==='audio'?'bg-success':'bg-info') }}">{{ ucfirst($room->type) }}</span></td>
            <td>{{ $room->user1_id ? 'User #'.$room->user1_id : 'Guest' }}</td>
            <td>{{ $room->user2_id ? 'User #'.$room->user2_id : 'Guest' }}</td>
            <td>{{ $room->created_at->diffForHumans() }}</td>
            <td>
                @if($room->type==='message')
                <a href="{{ route('admin.room.messages', $room->room_code) }}" class="btn btn-xs btn-outline-info btn-sm">💬 View</a>
                @else
                <span class="text-muted small">Live {{ $room->type }}</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No active rooms right now</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $rooms->links() }}
@endsection
@push('scripts')
<script>setTimeout(()=>location.reload(),10000);</script>
@endpush