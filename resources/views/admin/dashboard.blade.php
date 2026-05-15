@extends('layouts.admin')
@section('title','Dashboard')
@section('content')
<h4 class="fw-bold mb-4">Dashboard</h4>
<div class="row g-3 mb-4">
    @foreach([
        ['Total Users','users','fa-users','indigo'],
        ['Online Now','online_users','fa-circle','green'],
        ['Active Rooms','active_rooms','fa-layer-group','blue'],
        ['Video Active','active_video','fa-video','purple'],
        ['Audio Active','active_audio','fa-phone','teal'],
        ['Chat Active','active_message','fa-message','orange'],
        ['Total Messages','total_messages','fa-envelope','pink'],
        ['Total Calls','total_calls','fa-phone-volume','cyan'],
    ] as [$label, $key, $icon, $color])
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small">{{ $label }}</span>
                <i class="fa-solid {{ $icon }} text-muted"></i>
            </div>
            <div class="stat-number">{{ $stats[$key] ?? 0 }}</div>
        </div>
    </div>
    @endforeach
</div>

<h6 class="fw-bold mb-3">Recent Rooms</h6>
<div class="table-responsive">
    <table class="table table-dark table-hover rounded">
        <thead><tr>
            <th>Room Code</th><th>Type</th><th>Status</th><th>Started</th><th>Actions</th>
        </tr></thead>
        <tbody>
        @foreach($stats['recent_rooms'] as $room)
        <tr>
            <td><code>{{ $room->room_code }}</code></td>
            <td><span class="badge {{ $room->type==='video'?'bg-primary':($room->type==='audio'?'bg-success':'bg-info') }}">{{ ucfirst($room->type) }}</span></td>
            <td><span class="badge {{ $room->status==='active'?'badge-live bg-success':'bg-secondary' }}">{{ ucfirst($room->status) }}</span></td>
            <td>{{ $room->created_at->diffForHumans() }}</td>
            <td><a href="{{ route('admin.room.messages', $room->room_code) }}" class="btn btn-sm btn-outline-light">View</a></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection