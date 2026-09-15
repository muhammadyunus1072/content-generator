<?php

use App\Jobs\GenerateBoardImageJob;
use App\Jobs\GenerateBoardVoiceJob;
use App\Jobs\GenerateStoryScenesJob;
use App\Livewire\ContentTitleDetail;
use App\Livewire\StoryProjectDetail;
use App\Livewire\StoryStudio;
use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\ImageGenerationService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('story studio page is protected by auth middleware', function () {
    $this->get(route('story-studio'))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('story-studio'))
        ->assertOk()
        ->assertSeeLivewire(StoryStudio::class);
});

test('user can create a content title with characters attached', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Naya Arunika',
        'gender' => 'female',
        'age' => 23,
        'prompt_keywords' => '23yo Indonesian woman, dark espresso bob',
    ]);

    Livewire::test(StoryStudio::class)
        ->set('title', 'Solo Hunter Chronicles')
        ->set('description', 'A fantasy series about an underground hunter.')
        ->set('visualStyle', 'Dark Fantasy Manhwa')
        ->set('aspectRatio', '9:16')
        ->set('voiceName', 'id-ID-ArdiNeural')
        ->set('selectedCharacterIds', [$character->id])
        ->call('createContentTitle')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('content_titles', [
        'title' => 'Solo Hunter Chronicles',
        'aspect_ratio' => '9:16',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);

    $contentTitle = ContentTitle::where('title', 'Solo Hunter Chronicles')->first();
    expect($contentTitle->characters)->toHaveCount(1);
});

test('user can create a story under a content title and it dispatches GenerateStoryScenesJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Naya Arunika',
        'gender' => 'female',
        'age' => 23,
        'prompt_keywords' => '23yo Indonesian woman, dark espresso bob',
    ]);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Solo Hunter Chronicles',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);
    $contentTitle->characters()->attach($character->id);

    // Route test
    $this->get(route('story-studio.title', $contentTitle->id))
        ->assertOk()
        ->assertSeeLivewire(ContentTitleDetail::class);

    // Add Story test
    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->set('storyTitle', 'Episode 1: The Dungeon Awakening')
        ->set('narrativeScript', 'In the heart of Jakarta, Naya discovers an ancient monolith beneath the subway line.')
        ->set('selectedCharacterIds', [$character->id])
        ->call('createStory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('stories', [
        'content_title_id' => $contentTitle->id,
        'title' => 'Episode 1: The Dungeon Awakening',
        'aspect_ratio' => '9:16',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);

    $story = Story::where('title', 'Episode 1: The Dungeon Awakening')->first();
    expect($story->characters)->toHaveCount(1);
    expect($story->contentTitle->id)->toBe($contentTitle->id);

    Queue::assertPushed(GenerateStoryScenesJob::class, function ($job) use ($story) {
        return $job->story->id === $story->id;
    });
});

test('user can trigger granular board image and voice regeneration', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Test Story',
        'premise' => 'A test premise for story video generation.',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
        'status' => 'scenes_ready',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'status' => 'scenes_ready',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
        'narration_text' => 'The subway door opens into an ancient chamber.',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The subway door opens into an ancient chamber.',
        'visual_prompt' => 'Ancient chamber with glowing purple runes, Dark Fantasy Manhwa',
        'status' => 'pending',
    ]);

    Livewire::test(StoryProjectDetail::class, ['story' => $story])
        ->call('regenerateImage', $board->id)
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateBoardImageJob::class, function ($job) use ($board) {
        return $job->board->id === $board->id;
    });

    Livewire::test(StoryProjectDetail::class, ['story' => $story])
        ->call('regenerateVoice', $board->id)
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateBoardVoiceJob::class, function ($job) use ($board) {
        return $job->board->id === $board->id;
    });
});

test('GenerateBoardImageJob handles storage paths without undefined function errors', function () {
    $user = User::factory()->create();

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Job Storage Test',
        'premise' => 'Testing image job storage path resolution.',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'status' => 'scenes_ready',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'status' => 'scenes_ready',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
        'narration_text' => 'Testing narration.',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Testing narration chunk.',
        'visual_prompt' => 'A glowing rune in dark fantasy manhwa style.',
        'status' => 'pending',
    ]);

    $mockImageService = Mockery::mock(ImageGenerationService::class);
    $mockImageService->shouldReceive('generateAndSave')
        ->once()
        ->withArgs(function ($prompt, $path, $provider, $aspectRatio) {
            return ! empty($path) && str_contains($path, 'stories');
        })
        ->andReturn(true);

    $job = new GenerateBoardImageJob($board);
    $job->handle($mockImageService);

    $board->refresh();
    expect($board->image_path)->not->toBeNull();
    expect($board->status)->toBe('image_ready');
});
