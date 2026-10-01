<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Manager extends Authenticatable
{
    use HasFactory;

    // No need to define the table name if it follows the Laravel convention ('managers' for 'Manager' model)
    // protected $table = 'managers'; // Uncomment if you want to specify the table manually

    // Define the fillable properties (fields that can be mass-assigned)
    protected $fillable = [
        'fullname',
        'username',
        'email',
        'phone',
        'profile_picture',
        'nickname',
        'password',
        'dateofbirth',
    ];

    // Hide sensitive data
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Additional relationships can be defined here

    public function notes()
    {
        return $this->hasMany(ManagerNote::class);
    }
}
