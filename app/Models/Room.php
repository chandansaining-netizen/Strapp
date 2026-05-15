<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
     protected $fillable = [
        'room_code','type','user1_id','user1_guest_token',
        'user2_id','user2_guest_token','status','started_at','ended_at'
    ];
    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function messages() {
        return $this->hasMany(Message::class, 'room_code', 'room_code');
    }
    public function callLog() {
        return $this->hasOne(CallLog::class, 'room_code', 'room_code');
    }
}
