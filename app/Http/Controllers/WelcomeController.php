<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\WaitingUser;
use App\Models\User;
use App\Models\GuestSession;

class WelcomeController extends Controller
{
     public function index()
    {
        $stats = [
            'total_active'   => Room::where('status', 'active')->count() * 2
                               + WaitingUser::count(),
            'video_active'   => Room::where('status','active')->where('type','video')->count() * 2,
            'audio_active'   => Room::where('status','active')->where('type','audio')->count() * 2,
            'message_active' => Room::where('status','active')->where('type','message')->count() * 2,
        ];
        return view('welcome', compact('stats'));
    }
}
