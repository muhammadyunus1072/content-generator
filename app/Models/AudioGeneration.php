<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'title',
    'text',
    'provider',
    'voice',
    'voice_name',
    'rate',
    'pitch',
    'audio_path',
    'subtitle_path',
    'subtitle_cues',
    'duration_seconds',
    'file_size_bytes',
    'options',
])]
class AudioGeneration extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtitle_cues' => 'array',
            'options' => 'array',
            'duration_seconds' => 'float',
            'file_size_bytes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (! $this->audio_path) {
            return null;
        }

        return Storage::disk('public')->url($this->audio_path);
    }

    public function getSubtitleUrlAttribute(): ?string
    {
        if (! $this->subtitle_path) {
            return null;
        }

        return Storage::disk('public')->url($this->subtitle_path);
    }

    public function getFormattedDurationAttribute(): string
    {
        $mins = floor($this->duration_seconds / 60);
        $secs = floor(fmod($this->duration_seconds, 60));

        return sprintf('%02d:%02d', $mins, $secs);
    }
}
