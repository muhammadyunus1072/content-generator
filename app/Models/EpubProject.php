<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class EpubProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'epub_path',
        'status',
        'aspect_ratio',
        'voice_name',
        'tts_rate',
        'tts_pitch',
        'source_language',
        'target_language',
        'output_video_path',
        'total_scenes',
        'rendered_scenes',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_scenes' => 'integer',
            'rendered_scenes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(EpubScene::class)->orderBy('sequence_order');
    }

    public function getEpubUrlAttribute(): ?string
    {
        if (! $this->epub_path) {
            return null;
        }

        if (str_starts_with($this->epub_path, 'http://') || str_starts_with($this->epub_path, 'https://')) {
            return $this->epub_path;
        }

        return Storage::disk('public')->url($this->epub_path);
    }

    public function getOutputVideoUrlAttribute(): ?string
    {
        if (! $this->output_video_path) {
            return null;
        }

        if (str_starts_with($this->output_video_path, 'http://') || str_starts_with($this->output_video_path, 'https://')) {
            return $this->output_video_path;
        }

        return Storage::disk('public')->url($this->output_video_path);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }

        if ($this->total_scenes === 0) {
            return 0;
        }

        return (int) round(($this->rendered_scenes / $this->total_scenes) * 100);
    }
}
