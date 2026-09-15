<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EpubScene extends Model
{
    use HasFactory;

    protected $fillable = [
        'epub_project_id',
        'sequence_order',
        'original_image_path',
        'image_path',
        'original_script_text',
        'script_text',
        'audio_path',
        'audio_duration',
        'subtitle_path',
        'video_path',
        'status',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence_order' => 'integer',
            'audio_duration' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(EpubProject::class, 'epub_project_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (! $this->audio_path) {
            return null;
        }

        if (str_starts_with($this->audio_path, 'http://') || str_starts_with($this->audio_path, 'https://')) {
            return $this->audio_path;
        }

        return Storage::disk('public')->url($this->audio_path);
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        if (str_starts_with($this->video_path, 'http://') || str_starts_with($this->video_path, 'https://')) {
            return $this->video_path;
        }

        return Storage::disk('public')->url($this->video_path);
    }

    public function getSubtitleUrlAttribute(): ?string
    {
        if (! $this->subtitle_path) {
            return null;
        }

        if (str_starts_with($this->subtitle_path, 'http://') || str_starts_with($this->subtitle_path, 'https://')) {
            return $this->subtitle_path;
        }

        return Storage::disk('public')->url($this->subtitle_path);
    }
}
