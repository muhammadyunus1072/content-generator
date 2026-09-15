<?php

use App\Jobs\RenderMotionComicJob;
use App\Livewire\ComicStudio;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('comic studio page requires authentication', function () {
    $this->get(route('comic-studio'))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('comic-studio'))
        ->assertOk()
        ->assertSeeLivewire(ComicStudio::class);
});

test('user can create a motion comic content title in ComicStudio', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ComicStudio::class)
        ->set('title', 'Solo Hunter Chronicles')
        ->set('description', 'An underground dungeon hunter breaks all records.')
        ->set('visualStyle', 'Dark Fantasy Manhwa, Solo Leveling art style')
        ->set('aspectRatio', '16:9')
        ->set('voiceName', 'en-US-ChristopherNeural')
        ->call('createContentTitle')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('content_titles', [
        'title' => 'Solo Hunter Chronicles',
        'aspect_ratio' => '16:9',
        'visual_style' => 'Dark Fantasy Manhwa, Solo Leveling art style',
    ]);
});

test('user can register a character with role and voice in Character Bible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ComicStudio::class)
        ->set('characterName', 'Jinwoo Kang')
        ->set('characterGender', 'male')
        ->set('characterAge', 24)
        ->set('characterRole', 'protagonist')
        ->set('characterVoiceId', 'en-US-GuyNeural')
        ->set('characterColorTheme', '#3B82F6')
        ->set('characterPersonality', 'Quiet, calculating, relentless.')
        ->set('characterPromptKeywords', 'Jinwoo Kang, solo, glowing blue eyes, dagger')
        ->call('saveCharacter')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('characters', [
        'name' => 'Jinwoo Kang',
        'role' => 'protagonist',
        'voice_id' => 'en-US-GuyNeural',
        'color_theme' => '#3B82F6',
    ]);
});

test('user can attach speech bubbles and animation effects to a scene board', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Shadow Awakening',
        'premise' => 'An ancient hunter awakens.',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'The Awakening',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Altar of Shadow',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The dragon roared.',
        'visual_prompt' => 'Dragon breathing fire',
        'animation_effect' => 'ken_burns',
    ]);

    Livewire::test(ComicStudio::class)
        ->call('openBubbleEditor', $board->id)
        ->set('bubbleSpeaker', 'Jinwoo')
        ->set('bubbleText', 'Arise!')
        ->set('bubbleStyle', 'manga')
        ->set('bubblePosition', 'top-right')
        ->call('addBubbleToBoard')
        ->set('boardAnimationEffect', 'camera_shake')
        ->set('boardMoodTag', 'action')
        ->call('saveBoardConfig')
        ->assertHasNoErrors();

    $board->refresh();
    expect($board->animation_effect)->toBe('camera_shake');
    expect($board->mood_tag)->toBe('action');
    expect($board->speech_bubbles)->toHaveCount(1);
    expect($board->speech_bubbles[0]['text'])->toBe('Arise!');
});

test('user can trigger RenderMotionComicJob from ComicStudio', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Solo Shadow',
        'premise' => 'A lone warrior rises.',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Pilot Episode',
    ]);

    Livewire::test(ComicStudio::class)
        ->call('renderMotionComic', $episode->id)
        ->assertHasNoErrors();

    Queue::assertPushed(RenderMotionComicJob::class, function ($job) use ($episode) {
        return $job->episode->id === $episode->id;
    });
});
