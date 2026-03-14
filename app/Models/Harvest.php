<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Harvest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'harvest_name',
        'farmer_name',
        'phone',
        'location_text',
        'ready_status',
        'ready_date',
        'description',
        'image_path',
    ];

    protected $casts = [
        'ready_date' => 'date',
    ];

    protected $appends = ['ready_in_days'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReadyInDaysAttribute(): ?int
    {
        if ($this->ready_status !== 'future' || ! $this->ready_date) {
            return 0;
        }

        return max(0, Carbon::today()->diffInDays($this->ready_date, false));
    }
}
