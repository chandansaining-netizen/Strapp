<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'room_code','type','user1_id','user1_guest_token',
        'user2_id','user2_guest_token','duration_seconds','started_at','ended_at'
    ];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];
}
