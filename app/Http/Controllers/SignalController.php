<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\WebRTCSignal;
use Tymon\JWTAuth\Facades\JWTAuth;

class SignalController extends Controller
{
 public function signal(Request $request)
    {
        $request->validate([
            'room_code' => 'required|string',
            'signal'    => 'required|string',
            'type'      => 'required|in:offer,answer,ice-candidate',
        ]);

        // Build a consistent identity string for this sender
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $from = 'user_' . $user->id;
        } catch (\Exception $e) {
            $guestToken = $request->header('X-Guest-Token');
            $from       = 'guest_' . $guestToken;
        }

        broadcast(new WebRTCSignal(
            roomCode: $request->room_code,
            signal:   $request->signal,
            type:     $request->type,
            from:     $from
        ));

        return response()->json(['ok' => true]);
    }
}
