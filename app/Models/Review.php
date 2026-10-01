<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'service_name',
        'selected_services',
        'selected_staff',
        'rating',
        'review_message',
        'client_id',
        'staff_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'selected_services' => 'array',
        'selected_staff' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'review_staff', 'review_id', 'staff_id');
    }
}
