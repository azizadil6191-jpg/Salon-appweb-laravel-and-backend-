<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $table = 'notes';

    public function manager()
    {
        return $this->belongsTo(Manager::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (!$note->manager_id && auth()->check()) {
                $note->manager_id = auth()->id();
            }
        });
    }
} 