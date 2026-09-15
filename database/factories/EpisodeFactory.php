<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'story_id' => Story::factory(),
            'episode_number' => 1,
            'title' => fake()->sentence(3),
            'synopsis' => fake()->paragraph(),
            'narrative_script' => fake()->paragraphs(2, true),
            'status' => 'draft',
            'video_path' => null,
            'audio_path' => null,
            'subtitle_path' => null,
            'duration_seconds' => null,
        ];
    }
}
