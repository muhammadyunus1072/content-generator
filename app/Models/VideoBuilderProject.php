<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'title',
    'aspect_ratio',
    'main_source_type',
    'main_video_path',
    'main_video_url',
    'assets_config',
    'output_video_path',
    'save_to_library',
    'saved_video_id',
    'status',
    'error_message',
])]
class VideoBuilderProject extends Model
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
            'assets_config' => 'array',
            'save_to_library' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the video builder project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video library entry linked to this project if saved.
     */
    public function savedVideo(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'saved_video_id');
    }

    /**
     * Get the public URL for the output video.
     */
    public function getOutputUrlAttribute(): ?string
    {
        if (! $this->output_video_path) {
            return null;
        }

        return Storage::disk('public')->url($this->output_video_path);
    }
}
