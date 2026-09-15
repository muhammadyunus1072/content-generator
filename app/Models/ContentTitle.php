<?php

namespace App\Models;

use Database\Factories\ContentTitleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ContentTitle extends Model
{
    /** @use HasFactory<ContentTitleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'genre',
        'visual_style',
        'color_palette',
        'lighting_mood',
        'camera_framing',
        'aspect_ratio',
        'voice_name',
        'voice_mode',
        'image_provider',
        'image_connection_id',
        'text_connection_id',
        'tts_provider',
        'tts_rate',
        'tts_pitch',
        'cover_image_path',
        'prompt_config',
    ];

    protected $casts = [
        'prompt_config' => 'array',
    ];

    public function imageConnection(): BelongsTo
    {
        return $this->belongsTo(AiConnection::class, 'image_connection_id');
    }

    public function textConnection(): BelongsTo
    {
        return $this->belongsTo(AiConnection::class, 'text_connection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class)->latest();
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_content_title')->withTimestamps();
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function props(): HasMany
    {
        return $this->hasMany(Prop::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        if (str_starts_with($this->cover_image_path, 'http://') || str_starts_with($this->cover_image_path, 'https://')) {
            return $this->cover_image_path;
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    public function getBasePromptFormula(): string
    {
        if (! $this->prompt_config || ! is_array($this->prompt_config)) {
            // Fallback to legacy string field
            return $this->visual_style ?? 'Highly detailed, visual style';
        }

        $levels = [
            $this->prompt_config['level1'] ?? '',
            $this->prompt_config['level2'] ?? '',
            $this->prompt_config['level5'] ?? '',
        ];

        // Filter out empty values and implode
        return implode(', ', array_filter($levels));
    }
}
