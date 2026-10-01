<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone_number',
        'appointment_date',
        'appointment_time',
        'selected_staff',
        'selected_services',
        'service_name',
        'payment_method',
        'status',
        'category_id',
        'client_id',
        'service_id',
        'reason',
        'staff_id',
        'upload_picture',
        'staff_ids'
    ];

    protected $casts = [
        'selected_staff' => 'array',
        'selected_services' => 'array',
        'staff_ids' => 'array'
    ];
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the products associated with the appointment.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'appointment_products')
                    ->withPivot('quantity', 'price', 'subtotal')
                    ->withTimestamps();
    }
}

