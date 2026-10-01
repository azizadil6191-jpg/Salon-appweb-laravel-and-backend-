<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumable extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity_used'
    ];

    /**
     * Get the product that owns the consumable record.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
