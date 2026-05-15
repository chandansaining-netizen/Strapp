<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\{User, Room, Message, CallLog, WaitingUser, GuestSession};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
      public function showLogin() {
        if (session('admin_logged_in')) return redirect()->route('admin.dashboard');
        return view('admin.login');
    }

    public function login(Request $request) {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->where('role', 'admin')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid admin credentials']);
        }

        session(['admin_logged_in' => true, 'admin_id' => $user->id, 'admin_name' => $user->name]);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request) {
        $request->session()->forget(['admin_logged_in', 'admin_id', 'admin_name']);
        return redirect()->route('admin.login');
    }

    public function dashboard() {
        $stats = [
            'total_users'       => User::where('role', 'user')->count(),
            'online_users'      => User::where('is_online', true)->count(),
            'active_rooms'      => Room::where('status', 'active')->count(),
            'active_video'      => Room::where('status', 'active')->where('type', 'video')->count(),
            'active_audio'      => Room::where('status', 'active')->where('type', 'audio')->count(),
            'active_message'    => Room::where('status', 'active')->where('type', 'message')->count(),
            'total_messages'    => Message::count(),
            'total_calls'       => CallLog::count(),
            'guests_active'     => GuestSession::where('last_active', '>=', now()->subMinutes(5))->count(),
            'waiting'           => WaitingUser::count(),
            'recent_rooms'      => Room::latest()->take(10)->get(),
        ];
        return view('admin.dashboard', compact('stats'));
    }

    public function liveUsers() {
        $rooms = Room::where('status', 'active')
            ->with(['messages'])
            ->latest()
            ->paginate(20);
        return view('admin.live-users', compact('rooms'));
    }

    public function messages(Request $request) {
        $query = Message::with('sender')->latest();
        if ($request->room_code) {
            $query->where('room_code', $request->room_code);
        }
        $messages = $query->paginate(50);
        return view('admin.messages', compact('messages'));
    }

    public function calls() {
        $calls = CallLog::latest()->paginate(30);
        return view('admin.calls', compact('calls'));
    }

    public function users() {
        $users = User::where('role', 'user')->latest()->paginate(30);
        return view('admin.users', compact('users'));
    }

    public function roomMessages(string $roomCode) {
        $room     = Room::where('room_code', $roomCode)->firstOrFail();
        $messages = Message::where('room_code', $roomCode)->with('sender')->latest()->get();
        return view('admin.room-messages', compact('room', 'messages'));
    }
}
