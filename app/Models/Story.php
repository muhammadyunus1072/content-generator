<?php

namespace App\Models;

use Database\Factories\StoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Story extends Model
{
    /** @use HasFactory<StoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_title_id',
        'title',
        'premise',
        'genre',
        'visual_style',
        'image_provider',
        'aspect_ratio',
        'voice_name',
        'tts_provider',
        'tts_rate',
        'tts_pitch',
        'language',
        'status',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contentTitle(): BelongsTo
    {
        return $this->belongsTo(ContentTitle::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_story')->withTimestamps();
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number');
    }

    public function boards(): HasManyThrough
    {
        return $this->hasManyThrough(SceneBoard::class, Episode::class);
    }

    /**
     * Get all available characters for this story from Story pivot, ContentTitle, and User library.
     *
     * @return Collection<int, Character>
     */
    public function getAvailableCharacters(): Collection
    {
        $storyChars = $this->relationLoaded('characters') ? $this->characters : $this->characters()->get();
        $titleChars = $this->contentTitle?->relationLoaded('characters')
            ? $this->contentTitle->characters
            : ($this->contentTitle ? $this->contentTitle->characters()->get() : collect());
        $userChars = $this->user_id ? Character::where('user_id', $this->user_id)->get() : collect();

        return $storyChars
            ->merge($titleChars)
            ->merge($userChars)
            ->unique('id')
            ->values();
    }

    /**
     * Robust 5-tier fuzzy resolver for character names.
     */
    public function resolveCharacterByName(?string $name): ?Character
    {
        if (empty($name)) {
            return null;
        }

        $chars = $this->getAvailableCharacters();
        if ($chars->isEmpty()) {
            return null;
        }

        $cleanName = trim(strtolower($name));

        // 1. Exact case-insensitive match
        $match = $chars->first(fn ($c) => strtolower(trim($c->name)) === $cleanName);
        if ($match) {
            return $match;
        }

        // 2. Word-token overlap (e.g. "Ren" matching "Aoyama Ren")
        $queryWords = array_values(array_filter(
            preg_split('/[\s,\-_()]+/', $cleanName) ?: [],
            fn ($w) => strlen($w) >= 3
        ));

        if (! empty($queryWords)) {
            foreach ($chars as $c) {
                $charWords = array_values(array_filter(
                    preg_split('/[\s,\-_()]+/', strtolower(trim($c->name))) ?: [],
                    fn ($w) => strlen($w) >= 3
                ));
                if (count(array_intersect($queryWords, $charWords)) > 0) {
                    return $c;
                }
            }
        }

        // 3. Bidirectional Substring / Contains match
        $match = $chars->first(fn ($c) => str_contains(strtolower($c->name), $cleanName) ||
            str_contains($cleanName, strtolower($c->name))
        );
        if ($match) {
            return $match;
        }

        // 4. Common narrative aliases dictionary
        $genericAliases = ['protagonist', 'main character', 'mc', 'hero', 'the hero', 'lead', 'the lead', 'he', 'she', 'the boy', 'the girl', 'warrior', 'young man', 'young woman'];
        if (in_array($cleanName, $genericAliases, true)) {
            return $chars->first();
        }

        return null;
    }
}
