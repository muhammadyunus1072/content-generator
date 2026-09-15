<?php

namespace App\Models;

use Database\Factories\AiConnectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConnection extends Model
{
    /** @use HasFactory<AiConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'provider',
        'model_id',
        'api_key',
        'base_url',
        'headers',
        'options',
        'is_default',
        'is_active',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'headers' => 'array',
            'options' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function getMaskedApiKeyAttribute(): ?string
    {
        if (empty($this->api_key)) {
            return null;
        }

        $length = strlen($this->api_key);
        if ($length <= 8) {
            return '••••••••';
        }

        return substr($this->api_key, 0, 4).'••••••••'.substr($this->api_key, -4);
    }
}
