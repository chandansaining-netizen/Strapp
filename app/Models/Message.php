<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
     protected $fillable = ['room_code','sender_user_id','sender_guest_token','type','content','file_path'];

    public function sender() {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
