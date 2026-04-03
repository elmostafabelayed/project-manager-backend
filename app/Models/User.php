<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function projects() {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function proposals() {
        return $this->hasMany(Proposal::class, 'freelancer_id');
    }

    public function sentMessages() {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
