<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'video_id',
    'clip_path',
    'start_seconds',
    'end_seconds',
    'title',
    'description',
    'metadata',
    'status',
])]
class Clip extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_seconds' => 'decimal:2',
            'end_seconds' => 'decimal:2',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the video that owns the clip.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
