<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Events\UserMatched;
use App\Events\UserSkipped;
use App\Events\ActiveUsersUpdated;
use App\Models\Room;
use App\Models\WaitingUser;
use App\Models\GuestSession;
use App\Models\CallLog;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class MatchController extends Controller
{
  private function getIdentity(Request $request): array
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return ['user_id' => $user->id, 'guest_token' => null, 'display_name' => $user->display_name ?? $user->name];
        } catch (\Exception $e) {
            $guestToken = $request->header('X-Guest-Token');
            $guest = GuestSession::where('session_token', $guestToken)->first();
            return ['user_id' => null, 'guest_token' => $guestToken, 'display_name' => $guest?->display_name ?? 'Stranger'];
        }
    }

    public function joinQueue(Request $request)
    {
        $request->validate(['type' => 'required|in:video,audio,message']);
        $type     = $request->type;
        $identity = $this->getIdentity($request);

        // Remove from any existing queue
        $this->removeFromQueue($identity);

        // Find a waiting partner (not same user)
        $query = WaitingUser::where('type', $type);
        if ($identity['user_id']) {
            $query->where(function($q) use ($identity) {
                $q->whereNull('user_id')->orWhere('user_id', '!=', $identity['user_id']);
            });
        } else {
            $query->where('guest_token', '!=', $identity['guest_token']);
        }
        $partner = $query->oldest()->lockForUpdate()->first();

        if ($partner) {
            // Match found! Create room
            $roomCode = Str::upper(Str::random(8));

            $room = Room::create([
                'room_code'          => $roomCode,
                'type'               => $type,
                'user1_id'           => $partner->user_id,
                'user1_guest_token'  => $partner->guest_token,
                'user2_id'           => $identity['user_id'],
                'user2_guest_token'  => $identity['guest_token'],
                'status'             => 'active',
                'started_at'         => now(),
            ]);

            // Create call log for audio/video
            if (in_array($type, ['video', 'audio'])) {
                CallLog::create([
                    'room_code'        => $roomCode,
                    'type'             => $type,
                    'user1_id'         => $partner->user_id,
                    'user1_guest_token'=> $partner->guest_token,
                    'user2_id'         => $identity['user_id'],
                    'user2_guest_token'=> $identity['guest_token'],
                    'started_at'       => now(),
                ]);
            }

            // Notify partner (they will be the receiver/non-initiator)
            $partnerChannel = 'waiting.' . ($partner->user_id ? 'user.'.$partner->user_id : 'guest.'.$partner->guest_token);
            broadcast(new UserMatched($roomCode, $type, false, $partnerChannel))->toOthers();

            // Remove partner from queue
            $partner->delete();

            $this->broadcastStats();

            return response()->json([
                'matched'    => true,
                'room_code'  => $roomCode,
                'type'       => $type,
                'is_initiator' => true,
            ]);
        }

        // No partner found, join queue
        WaitingUser::create([
            'type'        => $type,
            'user_id'     => $identity['user_id'],
            'guest_token' => $identity['guest_token'],
        ]);

        $this->broadcastStats();

        return response()->json(['matched' => false, 'type' => $type]);
    }



    public function checkStatus(Request $request)
    {
        $request->validate(['type' => 'required|in:video,audio,message']);

        $identity = $this->getIdentity($request);
        $type     = $request->type;

        // ── Check if we were matched into a room ──
        $room = $this->findExistingRoom($identity, $type);

        if ($room) {
            // We are matched — remove from waiting queue (cleanup)
            $this->removeFromQueue($identity);

            return response()->json([
                'matched'      => true,
                'room_code'    => $room->room_code,
                'type'         => $type,
                'is_initiator' => $this->amIInitiator($room, $identity),
            ]);
        }

        // ── Still waiting — check if still in queue ──
        $inQueue = WaitingUser::where('type', $type)
            ->where(function ($q) use ($identity) {
                if ($identity['user_id']) {
                    $q->where('user_id', $identity['user_id']);
                } else {
                    $q->where('guest_token', $identity['guest_token']);
                }
            })->exists();

        // ── If not in queue anymore (got popped but WebSocket missed it) ──
        // Re-join the queue so we don't get lost
        if (!$inQueue) {
            WaitingUser::updateOrCreate(
                [
                    'type'        => $type,
                    'user_id'     => $identity['user_id'],
                    'guest_token' => $identity['guest_token'],
                ],
                ['updated_at' => now()]
            );
        }

        return response()->json([
            'matched'   => false,
            'in_queue'  => true,
            'type'      => $type,
        ]);
    }

     private function findExistingRoom(array $identity, string $type): ?Room
    {
        $query = Room::where('type', $type)->where('status', 'active');

        if ($identity['user_id']) {
            $query->where(function ($q) use ($identity) {
                $q->where('user1_id', $identity['user_id'])
                  ->orWhere('user2_id', $identity['user_id']);
            });
        } else {
            $query->where(function ($q) use ($identity) {
                $q->where('user1_guest_token', $identity['guest_token'])
                  ->orWhere('user2_guest_token', $identity['guest_token']);
            });
        }

        return $query->first();
    }

    /**
     * Determine if this identity is the initiator (user2) in a room.
     */
    private function amIInitiator(Room $room, array $identity): bool
    {
        if ($identity['user_id']) {
            return $room->user2_id === $identity['user_id'];
        }
        return $room->user2_guest_token === $identity['guest_token'];
    }

    public function skip(Request $request)
    {
        $request->validate(['room_code' => 'required|string']);
        $identity = $this->getIdentity($request);

        $room = Room::where('room_code', $request->room_code)->where('status', 'active')->first();
        if ($room) {
            // End the call log
            CallLog::where('room_code', $request->room_code)
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'duration_seconds' => now()->diffInSeconds($room->started_at)]);

            $room->update(['status' => 'ended', 'ended_at' => now()]);
            broadcast(new UserSkipped($request->room_code));
        }

        $this->removeFromQueue($identity);
        $this->broadcastStats();

        return response()->json(['skipped' => true]);
    }

    public function leaveQueue(Request $request)
    {
        $identity = $this->getIdentity($request);
        $this->removeFromQueue($identity);
        $this->broadcastStats();
        return response()->json(['ok' => true]);
    }

    public function stats()
    {
        return response()->json($this->getStats());
    }

    private function removeFromQueue(array $identity)
    {
        $query = WaitingUser::query();
        if ($identity['user_id']) {
            $query->where('user_id', $identity['user_id']);
        } elseif ($identity['guest_token']) {
            $query->where('guest_token', $identity['guest_token']);
        }
        $query->delete();
    }

    private function getStats(): array
    {
        return [
            'total_active'  => Room::where('status', 'active')->count() * 2,
            'video_active'  => Room::where('status', 'active')->where('type', 'video')->count() * 2,
            'audio_active'  => Room::where('status', 'active')->where('type', 'audio')->count() * 2,
            'message_active'=> Room::where('status', 'active')->where('type', 'message')->count() * 2,
            'waiting_video' => WaitingUser::where('type', 'video')->count(),
            'waiting_audio' => WaitingUser::where('type', 'audio')->count(),
            'waiting_message'=> WaitingUser::where('type', 'message')->count(),
        ];
    }

    private function broadcastStats()
    {
        broadcast(new ActiveUsersUpdated($this->getStats()));
    }
}
