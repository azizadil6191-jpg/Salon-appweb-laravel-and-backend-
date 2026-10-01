<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'total_amount',
        'amount_paid',
        'change_amount',
        'cashier_id',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
