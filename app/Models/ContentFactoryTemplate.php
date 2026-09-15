<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentFactoryTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'niche',
        'icon',
        'description',
        'visual_style',
        'color_palette',
        'lighting_mood',
        'camera_framing',
        'genre',
        'voice_name',
        'voice_mode',
        'tts_rate',
        'tts_pitch',
        'aspect_ratio',
        'target_platform',
        'content_type',
        'default_animation_config',
        'audio_config',
        'prompt_template',
        'negative_prompt',
        'monetization_tips',
        'example_titles',
        'estimated_rpm_min',
        'estimated_rpm_max',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_animation_config' => 'array',
            'audio_config' => 'array',
            'monetization_tips' => 'array',
            'example_titles' => 'array',
            'estimated_rpm_min' => 'float',
            'estimated_rpm_max' => 'float',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeForContentType(Builder $query, ?string $type): Builder
    {
        if (! $type || $type === 'all') {
            return $query;
        }

        return $query->where('content_type', $type);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemPresets(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeForNiche(Builder $query, ?string $niche): Builder
    {
        if (! $niche || $niche === 'all') {
            return $query;
        }

        return $query->where('niche', $niche);
    }
}
