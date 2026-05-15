@extends('layouts.admin')
@section('title','Users')
@section('content')
<h4 class="fw-bold mb-4">Registered Users</h4>
<div class="table-responsive">
    <table class="table table-dark table-hover">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Gender</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
        @forelse($users as $user)
        <tr>
            <td>#{{ $user->id }}</td>
            <td>{{ $user->display_name ?? $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ ucfirst($user->gender ?? '—') }}</td>
            <td>
                @if($user->is_online)
                    <span class="badge bg-success">Online</span>
                @else
                    <span class="badge bg-secondary">Offline</span>
                @endif
            </td>
            <td><small>{{ $user->created_at->format('M d, Y') }}</small></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-4">No users yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection