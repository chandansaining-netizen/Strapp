@extends('layouts.admin')
@section('title','Messages')
@section('content')
<h4 class="fw-bold mb-4">Message History</h4>
<form class="d-flex gap-2 mb-4">
    <input type="text" name="room_code" class="form-control bg-dark border-secondary text-light" style="max-width:200px;"
           placeholder="Filter by Room Code" value="{{ request('room_code') }}">
    <button class="btn btn-outline-light">Filter</button>
    <a href="{{ route('admin.messages') }}" class="btn btn-outline-secondary">Clear</a>
</form>
<div class="table-responsive">
    <table class="table table-dark table-hover">
        <thead><tr><th>Time</th><th>Room</th><th>Sender</th><th>Type</th><th>Content</th></tr></thead>
        <tbody>
        @forelse($messages as $msg)
        <tr>
            <td><small>{{ $msg->created_at->format('M d H:i') }}</small></td>
            <td><code>{{ $msg->room_code }}</code></td>
            <td>{{ $msg->sender?->display_name ?? 'Registered #'.$msg->sender_user_id }}</td>
            <td><span class="badge bg-secondary">{{ $msg->type }}</span></td>
            <td>
                @if($msg->type==='text') {{ Str::limit($msg->content, 80) }}
                @elseif($msg->file_path)
                    @if($msg->type==='image') <img src="{{ asset('storage/'.$msg->file_path) }}" style="height:50px;border-radius:4px;">
                    @else <span class="text-muted">📹 Video</span> @endif
                @else <span class="text-muted">Media (guest, no storage)</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-4">No messages found</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $messages->links() }}
@endsection