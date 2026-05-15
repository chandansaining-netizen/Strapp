<?php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{roomCode}', function ($user, $roomCode) {
    return true; // Public channels for rooms
});

Broadcast::channel('public.stats', function () {
    return true;
});

Broadcast::channel('waiting.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});