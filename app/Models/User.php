<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
   protected $fillable = ['name','email','password','role','display_name','gender','is_online','last_active'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = ['is_online' => 'boolean', 'last_active' => 'datetime'];

    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims() { return []; }

    public function messages() {
        return $this->hasMany(Message::class, 'sender_user_id');
    }
    public function sentFriendRequests() {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }
    public function receivedFriendRequests() {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }
    public function isAdmin() { return $this->role === 'admin'; }

}
