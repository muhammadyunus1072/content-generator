<?php

namespace App\Models;

use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Environment extends Model
{
    /** @use HasFactory<EnvironmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_title_id',
        'name',
        'setting_type',
        'time_of_day',
        'atmosphere',
        'lighting',
        'prompt_keywords',
        'description',
        'image_path',
        'sheet_image_path',
        'sheet_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sheet_data' => 'array',
        ];
    }

    public function contentTitle(): BelongsTo
    {
        return $this->belongsTo(ContentTitle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sceneBoards(): HasMany
    {
        return $this->hasMany(SceneBoard::class);
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

        return "{$this->name}, {$this->setting_type}, {$this->lighting}";
    }
}
