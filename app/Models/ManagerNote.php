<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerNote extends Model
{
    protected $fillable = [
        'manager_id',
        'message',
        'is_read'
    ];

    public function manager()
    {
        return $this->belongsTo(Manager::class);
    }
} 