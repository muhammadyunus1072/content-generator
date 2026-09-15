<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AudioMix extends Model
{
    use HasFactory;

    protected $fillable = [
        'scene_board_id',
        'layer_type',
        'audio_path',
        'volume',
        'start_offset',
        'fade_in',
        'fade_out',
        'loop',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume' => 'float',
            'start_offset' => 'float',
            'fade_in' => 'float',
            'fade_out' => 'float',
            'loop' => 'boolean',
        ];
    }

    public function sceneBoard(): BelongsTo
    {
        return $this->belongsTo(SceneBoard::class);
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
}
