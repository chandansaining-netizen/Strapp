@extends('layouts.admin')
@section('title','Room Messages')
@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.messages') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h4 class="fw-bold mb-0">Room: <code>{{ $room->room_code }}</code></h4>
    <span class="badge {{ $room->status==='active'?'bg-success':'bg-secondary' }}">{{ ucfirst($room->status) }}</span>
</div>
<div style="max-height:70vh;overflow-y:auto;background:#0f172a;border-radius:12px;padding:20px;">
    @forelse($messages as $msg)
    <div class="d-flex flex-column {{ $msg->sender_user_id ? 'align-items-end' : 'align-items-start' }} mb-3">
        <div style="background:{{ $msg->sender_user_id?'#6366f1':'#1e293b' }};border-radius:12px;padding:10px 16px;max-width:60%;">
            @if($msg->type==='text') {{ $msg->content }}
            @elseif($msg->file_path && $msg->type==='image')
                <img src="{{ asset('storage/'.$msg->file_path) }}" style="max-width:200px;border-radius:8px;">
            @else <em class="text-muted">{{ ucfirst($msg->type) }} file</em>
            @endif
        </div>
        <small class="text-muted mt-1">{{ $msg->sender?->display_name ?? 'Guest' }} · {{ $msg->created_at->format('H:i:s') }}</small>
    </div>
    @empty
    <p class="text-center text-muted">No messages in this room</p>
    @endforelse
</div>
@endsection