<?php

namespace App\Models;

use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'gender',
        'entity_type', // human, creature, animal, android, etc.
        'age',
        'appearance',
        'prompt_keywords',
        'avatar_path',
        'sheet_image_path',
        'sheet_data',
        'voice_id',
        'tts_provider',
        'color_theme',
        'personality',
        'relationships',
        'role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'sheet_data' => 'array',
            'relationships' => 'array',
        ];
    }

    /**
     * Get TTS voice configuration for this character.
     *
     * @return array{voice_id: ?string, tts_provider: string}
     */
    public function getVoiceConfig(): array
    {
        return [
            'voice_id' => $this->voice_id,
            'tts_provider' => $this->tts_provider ?? 'edge-tts',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class, 'character_story')->withTimestamps();
    }

    public function contentTitles(): BelongsToMany
    {
        return $this->belongsToMany(ContentTitle::class, 'character_content_title')->withTimestamps();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://')) {
            return $this->avatar_path;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function getSheetImageUrlAttribute(): ?string
    {
        if (! $this->sheet_image_path) {
            return null;
        }

        if (str_starts_with($this->sheet_image_path, 'http://') || str_starts_with($this->sheet_image_path, 'https://')) {
            return $this->sheet_image_path;
        }

        return Storage::disk('public')->url($this->sheet_image_path);
    }

    /**
     * Get concentrated prompt tokens for consistent image generation.
     * User's explicit input prompt_keywords ALWAYS takes first priority.
     */
    public function getConsistencyTokens(): string
    {
        if (! empty(trim($this->prompt_keywords ?? ''))) {
            return trim($this->prompt_keywords);
        }

        if (! empty($this->sheet_data['consistency_prompt_tokens'])) {
            return $this->sheet_data['consistency_prompt_tokens'];
        }

        $tokens = array_filter([
            $this->name,
            $this->entity_type ? "({$this->entity_type})" : null,
            $this->gender,
            $this->age ? "age {$this->age}" : null,
            $this->appearance,
        ]);

        return implode(', ', $tokens) ?: "{$this->name}";
    }
}
