<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'hair_length',
        'price',
        'category_id',
        'price_men',
        'price_women',
        'profile_image',
        'duration',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class); // Ensure this method is defined
    }

    public function appointments()
{
    return $this->hasMany(Appointment::class, 'service_id');
}

}
