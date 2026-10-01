<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Owner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'owners';

    protected $fillable = [
        'fullname',
        'email',
        'number',
        'profile_picture',
        'username',
        'password',
        'date_of_birth',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed', // Ensures password is securely hashed
    ];
}
