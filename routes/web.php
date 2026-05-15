<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{WelcomeController, Admin\AdminController};

// Welcome
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// App pages (SPA-style, frontend handles)
Route::get('/video-call', fn() => view('video-call'))->name('video.call');
Route::get('/audio-call', fn() => view('audio-call'))->name('audio.call');
Route::get('/messaging',  fn() => view('messaging'))->name('messaging');
Route::get('/auth',       fn() => view('auth.login'))->name('auth');

// Admin web routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminController::class, 'login'])->name('login.post');
    Route::post('logout',[AdminController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/',             [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('live',          [AdminController::class, 'liveUsers'])->name('live');
        Route::get('messages',      [AdminController::class, 'messages'])->name('messages');
        Route::get('calls',         [AdminController::class, 'calls'])->name('calls');
        Route::get('users',         [AdminController::class, 'users'])->name('users');
        Route::get('room/{code}',   [AdminController::class, 'roomMessages'])->name('room.messages');
    });
});