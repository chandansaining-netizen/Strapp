<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
 protected $fillable = ['session_token','display_name','gender','ip_address','last_active'];
    protected $casts    = ['last_active' => 'datetime'];
}
