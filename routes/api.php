<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{
    Auth\AuthController,
    GuestController,
    MatchController,
    MessageController,
    SignalController,
    FriendController
};

// Auth
Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);

// Guest
Route::post('guest/session',    [GuestController::class, 'createSession']);
Route::post('guest/heartbeat',  [GuestController::class, 'heartbeat']);

// Stats (public)
Route::get('stats', [MatchController::class, 'stats']);

// Protected by JWT OR guest token (both work)
Route::post('queue/join',  [MatchController::class, 'joinQueue']);
Route::post('queue/leave', [MatchController::class, 'leaveQueue']);
Route::post('queue/skip',  [MatchController::class, 'skip']);
Route::post('queue/status', [MatchController::class, 'checkStatus']); // ← NEW

Route::post('signal',      [SignalController::class, 'signal']);
Route::post('messages/send', [MessageController::class, 'send']);
Route::get('messages/{roomCode}/history', [MessageController::class, 'history']);

// Registered users only
Route::middleware('jwt.auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('friends/request',       [FriendController::class, 'sendRequest']);
    Route::post('friends/{id}/respond',  [FriendController::class, 'respond']);
    Route::get('friends/pending',        [FriendController::class, 'pending']);
});
