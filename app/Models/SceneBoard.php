<?php

namespace App\Models;

use App\Services\PromptCompilerService;
use Database\Factories\SceneBoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SceneBoard extends Model
{
    /** @use HasFactory<SceneBoardFactory> */
    use HasFactory;

    protected $fillable = [
        'episode_id',
        'scene_id',
        'environment_id',
        'characters_present',
        'props_present',
        'board_number',
        'sequence_order',
        'narration_chunk',
        'view_type',
        'camera_movement',
        'scene_data',
        'image_path',
        'audio_path',
        'subtitle_path',
        'duration_seconds',
        'start_seconds',
        'end_seconds',
        'status',
        'animation_effect',
        'transition_type',
        'speech_bubbles',
        'bubble_style',
        'sfx_tags',
        'mood_tag',
        'parallax_layers',
        'has_particles',
        'particle_type',
        'speech_bubble_image_path',
        'sfx_typography',
        'gutter_spacing',
        'page_layout_hint',
        'camera_keyframes',
        'dialogue_audio_timeline',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'float',
            'start_seconds' => 'float',
            'end_seconds' => 'float',
            'board_number' => 'integer',
            'sequence_order' => 'integer',
            'characters_present' => 'array',
            'props_present' => 'array',
            'speech_bubbles' => 'array',
            'sfx_tags' => 'array',
            'sfx_typography' => 'array',
            'camera_keyframes' => 'array',
            'dialogue_audio_timeline' => 'array',
            'gutter_spacing' => 'integer',
            'parallax_layers' => 'array',
            'has_particles' => 'boolean',
            'scene_data' => 'array',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function audioMixes(): HasMany
    {
        return $this->hasMany(AudioMix::class);
    }

    public function getSpeechBubbleImageUrlAttribute(): ?string
    {
        if (! $this->speech_bubble_image_path) {
            return null;
        }

        if (str_starts_with($this->speech_bubble_image_path, 'http://') || str_starts_with($this->speech_bubble_image_path, 'https://')) {
            return $this->speech_bubble_image_path;
        }

        return Storage::disk('public')->url($this->speech_bubble_image_path);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
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

    public function getVisualPromptAttribute(): string
    {
        if (! empty($this->attributes['visual_prompt'])) {
            return (string) $this->attributes['visual_prompt'];
        }

        if (! empty($this->scene_data['action_description'])) {
            return (string) $this->scene_data['action_description'];
        }

        if (! empty($this->scene_data)) {
            $contentTitle = $this->episode?->story?->contentTitle;
            $compiled = app(PromptCompilerService::class)->compilePrompt($contentTitle, $this->scene_data);
            if (! empty($compiled)) {
                return $compiled;
            }
        }

        $chars = implode(', ', $this->characters_present ?? []);

        return trim("{$chars} {$this->narration_chunk}");
    }
}
