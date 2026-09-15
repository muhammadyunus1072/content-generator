<?php

use App\Livewire\ComicStudio;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('Visual Panel Director initializes board data and custom keyframes', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Director Chronicle',
        'premise' => 'A director tests the visual panel editor.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Act 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Intro Scene',
    ]);

    $existingKeyframes = [
        ['time_pct' => 0, 'zoom' => 1.0, 'x_pct' => 50, 'y_pct' => 50, 'shake' => 0],
        ['time_pct' => 100, 'zoom' => 1.5, 'x_pct' => 70, 'y_pct' => 40, 'shake' => 5],
    ];

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Look over there!',
        'visual_prompt' => 'A warrior standing on cliff edge',
        'camera_keyframes' => $existingKeyframes,
        'speech_bubbles' => [
            ['speaker' => 'Ren', 'text' => 'Watch out!', 'style' => 'manga', 'x_pct' => 40, 'y_pct' => 20],
        ],
        'sfx_typography' => [
            ['text' => 'SLASH!', 'style' => 'blade_slash', 'color' => '#EF4444', 'x_pct' => 60, 'y_pct' => 50],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('openBubbleEditor', $board->id)
        ->assertSet('showBubbleModal', true)
        ->assertSet('editingBoardId', $board->id)
        ->assertSet('boardKeyframes', $existingKeyframes)
        ->assertCount('boardBubbles', 1)
        ->assertCount('boardSfxTypography', 1);
});

test('Visual Panel Director adds keyframes and sorts them chronologically', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Keyframe Flow',
        'premise' => 'Testing keyframe sorting.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Act 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The clash of steel echoed across the valley.',
        'visual_prompt' => 'An epic battle',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('openBubbleEditor', $board->id)
        // Add keyframe at 80%
        ->set('kfTimePct', 80)
        ->set('kfZoom', 1.6)
        ->set('kfXPct', 60)
        ->set('kfYPct', 40)
        ->set('kfShake', 10)
        ->call('addKeyframe')
        // Add keyframe at 20%
        ->set('kfTimePct', 20)
        ->set('kfZoom', 1.2)
        ->set('kfXPct', 50)
        ->set('kfYPct', 50)
        ->set('kfShake', 0)
        ->call('addKeyframe')
        ->assertCount('boardKeyframes', 2)
        // Verify chronological order: first element should be 20%, second 80%
        ->assertSet('boardKeyframes.0.time_pct', 20)
        ->assertSet('boardKeyframes.1.time_pct', 80);
});

test('Visual Panel Director updates coordinates and persists to database', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Persistence Flow',
        'premise' => 'Testing board config persistence.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Act 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The wizard began chanting arcane runes.',
        'visual_prompt' => 'Hero preparing spell',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('openBubbleEditor', $board->id)
        // Add bubble
        ->set('bubbleSpeaker', 'Mage')
        ->set('bubbleText', 'Fireball!')
        ->call('addBubbleToBoard')
        // Update bubble coords
        ->call('updateBubbleCoords', 0, 72.5, 28.3)
        // Add SFX
        ->set('sfxText', 'FWHUUUSH!')
        ->call('addSfxToBoard')
        // Update SFX coords
        ->call('updateSfxCoords', 0, 65, 45)
        // Add Keyframe
        ->set('kfTimePct', 50)
        ->set('kfZoom', 1.3)
        ->set('kfXPct', 60)
        ->set('kfYPct', 40)
        ->call('addKeyframe')
        ->call('saveBoardConfig')
        ->assertSet('showBubbleModal', false);

    $board->refresh();

    expect($board->camera_keyframes)->toBeArray();
    expect($board->camera_keyframes[0]['time_pct'])->toBe(50);
    expect((float) $board->speech_bubbles[0]['x_pct'])->toEqual(72.5);
    expect((float) $board->speech_bubbles[0]['y_pct'])->toEqual(28.3);
    expect((float) $board->sfx_typography[0]['x_pct'])->toEqual(65.0);
    expect((float) $board->sfx_typography[0]['y_pct'])->toEqual(45.0);
});
