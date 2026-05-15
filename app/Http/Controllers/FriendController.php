<?php

namespace App\Http\Controllers;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class FriendController extends Controller
{
     public function sendRequest(Request $request)
    {
        $request->validate(['receiver_id' => 'required|exists:users,id']);
        $sender = JWTAuth::parseToken()->authenticate();

        $existing = FriendRequest::where('sender_id', $sender->id)
            ->where('receiver_id', $request->receiver_id)->first();

        if ($existing) {
            return response()->json(['message' => 'Request already sent'], 409);
        }

        $fr = FriendRequest::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $request->receiver_id,
        ]);

        return response()->json(['message' => 'Friend request sent', 'request' => $fr]);
    }

    public function respond(Request $request, int $requestId)
    {
        $request->validate(['action' => 'required|in:accept,reject']);
        $user = JWTAuth::parseToken()->authenticate();

        $fr = FriendRequest::where('id', $requestId)
            ->where('receiver_id', $user->id)->firstOrFail();

        $fr->update(['status' => $request->action === 'accept' ? 'accepted' : 'rejected']);

        return response()->json(['message' => 'Done', 'request' => $fr]);
    }

    public function pending()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $requests = FriendRequest::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('sender')
            ->get();
        return response()->json(['requests' => $requests]);
    }

}
