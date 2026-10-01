<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name']; 

    public function staff()
{
    return $this->belongsToMany(Staff::class, 'category_staff');
}

// Adjust as necessary

public function services()
{
    return $this->hasMany(Service::class);
}

}
