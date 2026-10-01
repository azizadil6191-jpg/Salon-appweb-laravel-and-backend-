<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentProduct extends Pivot
{
    protected $table = 'appointment_products';

    public $incrementing = true;

    protected $fillable = [
        'appointment_id',
        'product_id',
        'quantity',
        'price',
        'subtotal'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    /**
     * Get the appointment that owns the product.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the product that belongs to the appointment.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate the subtotal before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointmentProduct) {
            if (!isset($appointmentProduct->subtotal)) {
                $appointmentProduct->subtotal = $appointmentProduct->quantity * $appointmentProduct->price;
            }
        });

        static::updating(function ($appointmentProduct) {
            if ($appointmentProduct->isDirty(['quantity', 'price'])) {
                $appointmentProduct->subtotal = $appointmentProduct->quantity * $appointmentProduct->price;
            }
        });
    }

    /**
     * Validate the model attributes
     */
    protected static function booted()
    {
        static::saving(function ($appointmentProduct) {
            if ($appointmentProduct->quantity < 1) {
                throw new \InvalidArgumentException('Quantity must be at least 1');
            }
            if ($appointmentProduct->price < 0) {
                throw new \InvalidArgumentException('Price cannot be negative');
            }
        });
    }
} 