<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products'; // Define table name

    protected $fillable = [
        'image',
        'product_name',
        'category',
        'brand',
        'price',
        'stocks',
        'added_stock',
        'total_added_stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stocks' => 'integer',
        'added_stock' => 'integer',
        'total_added_stock' => 'integer'
    ];
    
    /**
     * Accessor to get the full image URL if stored in storage.
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Deduct stock when an order is placed
    public function deductStock($quantity)
    {
        if ($this->stocks >= $quantity) {
            $this->stocks -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function getSoldQuantityAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }
}
