<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Cashier extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'cashiers';
    protected $fillable = [
        'username',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'password',
        'profile_image',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed', // Ensures password is securely hashed
    ];
}
