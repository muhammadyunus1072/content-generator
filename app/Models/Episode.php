<?php

namespace App\Models;

use Database\Factories\EpisodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Episode extends Model
{
    /** @use HasFactory<EpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'story_id',
        'episode_number',
        'title',
        'synopsis',
        'narrative_script',
        'status',
        'voice_mode',
        'video_path',
        'audio_path',
        'subtitle_path',
        'duration_seconds',
        'webtoon_strip_path',
        'webtoon_slices',
        'webtoon_status',
        'webtoon_export_pack_path',
        'manga_pages',
        'manga_status',
        'manga_reading_direction',
        'cbz_package_path',
        'pdf_package_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'float',
            'episode_number' => 'integer',
            'webtoon_slices' => 'array',
            'manga_pages' => 'array',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class)->orderBy('scene_number');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(SceneBoard::class)->orderBy('sequence_order');
    }

    public function sceneBoards(): HasMany
    {
        return $this->boards();
    }

    /**
     * Get the active Voice Mode (multi_actor vs solo_narrator).
     * Inherits from Story/ContentTitle if not explicitly set on this episode.
     */
    public function getEffectiveVoiceMode(): string
    {
        if (! empty($this->voice_mode)) {
            return $this->voice_mode;
        }

        return $this->story?->contentTitle?->voice_mode ?? 'multi_actor';
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        return Storage::disk('public')->url($this->video_path);
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (! $this->audio_path) {
            return null;
        }

        return Storage::disk('public')->url($this->audio_path);
    }

    public function getWebtoonStripUrlAttribute(): ?string
    {
        if (! $this->webtoon_strip_path) {
            return null;
        }

        return Storage::disk('public')->url($this->webtoon_strip_path);
    }

    public function getWebtoonExportPackUrlAttribute(): ?string
    {
        if (! $this->webtoon_export_pack_path) {
            return null;
        }

        return Storage::disk('public')->url($this->webtoon_export_pack_path);
    }

    public function getCbzPackageUrlAttribute(): ?string
    {
        if (! $this->cbz_package_path) {
            return null;
        }

        return Storage::disk('public')->url($this->cbz_package_path);
    }

    public function getPdfPackageUrlAttribute(): ?string
    {
        if (! $this->pdf_package_path) {
            return null;
        }

        return Storage::disk('public')->url($this->pdf_package_path);
    }
}
