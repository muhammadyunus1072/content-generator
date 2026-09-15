<?php

namespace App\Models;

use Database\Factories\PropFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Prop extends Model
{
    /** @use HasFactory<PropFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_title_id',
        'name',
        'category',
        'description',
        'prompt_keywords',
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
     */
    public function getConsistencyTokens(): string
    {
        if (! empty($this->prompt_keywords)) {
            return $this->prompt_keywords;
        }

        if (! empty($this->sheet_data['consistency_prompt_tokens'])) {
            return $this->sheet_data['consistency_prompt_tokens'];
        }

        return "{$this->name}, {$this->category}";
    }
}
