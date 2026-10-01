<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Staff extends Authenticatable
{
    use HasFactory;


    protected $fillable = [
        'profile_picture',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'username',
        'password',
        'gender',
        'date_of_birth',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

public function categories()
{
    return $this->belongsToMany(Category::class, 'category_staff');
    
}

public function getNameAttribute()
{
    return "{$this->first_name} {$this->last_name}";
}

public function appointments()
{
    return $this->hasMany(Appointment::class, 'staff_id');
}

public function schedules()
{
    return $this->hasMany(StaffSchedule::class);
}

public function dayOffRequests()
{
    return $this->hasMany(DayOffRequest::class);
}

}

