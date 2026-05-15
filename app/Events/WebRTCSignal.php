<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $roomCode,
        public string $signal,
        public string $type,
        public string $from     
    )
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // return [
        //     new PrivateChannel('channel-name'),
        // ];
          return [new Channel('room.' . $this->roomCode)];
    }


public function broadcastWith(): array
    {
        return [
            'room_code' => $this->roomCode,
            'signal'    => $this->signal,
            'type'      => $this->type,
            'from'      => $this->from,
        ];
    }

    public function broadcastAs(): string { return 'webrtc.signal'; }
}
