<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingUser extends Model
{
     protected $fillable = ['type','user_id','guest_token','socket_id'];
}
