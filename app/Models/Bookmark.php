<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
