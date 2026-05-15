<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GuestSession;
use Illuminate\Support\Str;

class GuestController extends Controller
{
    public function createSession(Request $request)
    {
        $request->validate([
            'display_name' => 'nullable|string|max:50',
            'gender'       => 'nullable|in:male,female,other',
        ]);

        $token = Str::random(32) . '_' . time();

        $guest = GuestSession::create([
            'session_token' => $token,
            'display_name'  => $request->display_name ?? 'Stranger',
            'gender'        => $request->gender,
            'ip_address'    => $request->ip(),
            'last_active'   => now(),
        ]);

        return response()->json([
            'guest_token'  => $token,
            'display_name' => $guest->display_name,
            'gender'       => $guest->gender,
        ]);
    }

    public function heartbeat(Request $request)
    {
        $guest = GuestSession::where('session_token', $request->header('X-Guest-Token'))->first();
        if ($guest) {
            $guest->update(['last_active' => now()]);
        }
        return response()->json(['ok' => true]);
    }
}
