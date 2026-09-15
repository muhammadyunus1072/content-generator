<?php

namespace Database\Factories;

use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'title' => fake()->sentence(4),
            'premise' => fake()->paragraphs(3, true),
            'genre' => fake()->randomElement(['Action Fantasy', 'Cultivation', 'Dark Webtoon', 'Cyberpunk', 'Romance']),
            'visual_style' => 'Dark Fantasy Manhwa',
            'aspect_ratio' => '16:9',
            'voice_name' => 'en-US-ChristopherNeural',
            'language' => 'en',
            'status' => 'draft',
            'settings' => [],
        ];
    }
}
