<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use App\Models\GuestSession;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class MessageController extends Controller
{
    
private function getIdentity(Request $request): array
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return ['user_id' => $user->id, 'guest_token' => null, 'name' => $user->display_name ?? $user->name];
        } catch (\Exception $e) {
            $token = $request->header('X-Guest-Token');
            $guest = GuestSession::where('session_token', $token)->first();
            return ['user_id' => null, 'guest_token' => $token, 'name' => $guest?->display_name ?? 'Stranger'];
        }
    }

    public function send(Request $request)
    {
        $request->validate([
            'room_code' => 'required|string',
            'type'      => 'required|in:text,image,video',
            'content'   => 'nullable|string',
            'file'      => 'nullable|file|max:51200', // 50MB max
        ]);

        $identity = $this->getIdentity($request);

        // Verify user is in this room
        $room = Room::where('room_code', $request->room_code)
            ->where('status', 'active')
            ->where(function($q) use ($identity) {
                if ($identity['user_id']) {
                    $q->where('user1_id', $identity['user_id'])
                      ->orWhere('user2_id', $identity['user_id']);
                } else {
                    $q->where('user1_guest_token', $identity['guest_token'])
                      ->orWhere('user2_guest_token', $identity['guest_token']);
                }
            })->first();

        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        $filePath = null;

        // Only store files for registered users
        if ($request->hasFile('file') && $identity['user_id']) {
            $filePath = $request->file('file')->store('messages/' . $request->room_code, 'public');
        }

        $messageData = [
            'room_code'         => $request->room_code,
            'sender_user_id'    => $identity['user_id'],
            'sender_guest_token'=> $identity['guest_token'],
            'type'              => $request->type,
            'content'           => $request->content,
            'file_path'         => $filePath,
        ];

        // Only persist to DB if sender is logged in
        if ($identity['user_id']) {
            $message = Message::create($messageData);
        }

        $broadcastData = [
            'room_code'  => $request->room_code,
            'type'       => $request->type,
            'content'    => $request->content,
            'file_path'  => $filePath ? asset('storage/' . $filePath) : null,
            'file_data'  => (!$identity['user_id'] && $request->hasFile('file'))
                            ? base64_encode(file_get_contents($request->file('file')->path()))
                            : null,
            'file_mime'  => $request->hasFile('file') ? $request->file('file')->getMimeType() : null,
            'sender'     => $identity['name'],
            'is_guest'   => is_null($identity['user_id']),
            'created_at' => now()->toIso8601String(),
        ];
        
        broadcast(new MessageSent($request->room_code, $broadcastData));

        return response()->json(['ok' => true, 'message' => $broadcastData]);
    }

    public function history(Request $request, string $roomCode)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json(['messages' => []]);
        }

        $messages = Message::where('room_code', $roomCode)
            ->where('sender_user_id', $user->id)
            ->with('sender')
            ->latest()
            ->take(100)
            ->get();

        return response()->json(['messages' => $messages]);
    }
}
