<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Scene;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scene>
 */
class SceneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'episode_id' => Episode::factory(),
            'scene_number' => 1,
            'title' => fake()->sentence(3),
            'setting' => 'Dark dungeon chamber illuminated by glowing crystals',
            'mood' => 'Tense and ominous',
            'narration_text' => fake()->paragraph(),
        ];
    }
}
