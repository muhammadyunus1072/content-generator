<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SceneBoard>
 */
class SceneBoardFactory extends Factory
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
            'scene_id' => Scene::factory(),
            'board_number' => 1,
            'sequence_order' => 1,
            'narration_chunk' => 'Standing amidst the ruins, the hunter felt an overwhelming surge of mana.',
            'view_type' => 'wide_shot',
            'camera_movement' => 'zoom_in',
            'visual_prompt' => 'Solo leveling webtoon style, dark fantasy atmosphere, lone black haired hunter standing amidst shattered obsidian ruins with glowing purple eyes and energy aura, hyper detailed, 8k comic key visual',
            'image_path' => null,
            'audio_path' => null,
            'duration_seconds' => 4.5,
            'start_seconds' => 0.0,
            'end_seconds' => 4.5,
            'status' => 'pending',
        ];
    }
}
