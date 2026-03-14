<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'farm_name',
        'farmer_name',
        'phone',
        'location_text',
        'length',
        'width',
        'area',
        'has_well',
        'has_electricity',
        'description',
        'image_path',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'area' => 'decimal:2',
        'has_well' => 'boolean',
        'has_electricity' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
