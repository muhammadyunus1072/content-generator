<?php

use App\Jobs\GenerateBoardImageJob;
use App\Livewire\StoryProjectDetail;
use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\ImageGenerationService;
use App\Services\StoryDecompositionService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('story resolves characters from content title when pivot is empty', function () {
    $user = User::factory()->create();

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Solo Hunter Universe',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Aoyama Ren',
        'gender' => 'male',
        'age' => 24,
        'appearance' => 'Black spiky hair, cyber hoodie',
        'prompt_keywords' => 'Aoyama Ren, 24yo manhwa protagonist, cyber hoodie',
    ]);
    $contentTitle->characters()->attach($character->id);

    // Story created without attaching to character_story pivot
    $story = Story::create([
        'user_id' => $user->id,
        'content_title_id' => $contentTitle->id,
        'title' => 'Episode 1: The Dungeon Awakening',
        'premise' => 'Ren discovers an underground dungeon.',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
        'status' => 'pending',
    ]);

    expect($story->characters()->count())->toBe(0);
    $availableChars = $story->getAvailableCharacters();
    expect($availableChars)->toHaveCount(1);
    expect($availableChars->first()->id)->toBe($character->id);
    expect($availableChars->first()->name)->toBe('Aoyama Ren');
});

test('resolve character by name supports exact, word tokens, and common narrative aliases', function () {
    $user = User::factory()->create();

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Aoyama Ren',
        'gender' => 'male',
        'age' => 24,
        'appearance' => 'Black spiky hair, cyber hoodie',
        'prompt_keywords' => 'Aoyama Ren, cyber hoodie',
    ]);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Alias Test Story',
        'premise' => 'Testing character alias resolution.',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
    ]);
    $story->characters()->attach($character->id);

    // 1. Exact case-insensitive
    expect($story->resolveCharacterByName('Aoyama Ren')->id)->toBe($character->id);
    expect($story->resolveCharacterByName('aoyama ren')->id)->toBe($character->id);

    // 2. Word token match
    expect($story->resolveCharacterByName('Ren')->id)->toBe($character->id);
    expect($story->resolveCharacterByName('Aoyama')->id)->toBe($character->id);

    // 3. Narrative aliases
    expect($story->resolveCharacterByName('Protagonist')->id)->toBe($character->id);
    expect($story->resolveCharacterByName('MC')->id)->toBe($character->id);
    expect($story->resolveCharacterByName('The Hero')->id)->toBe($character->id);
    expect($story->resolveCharacterByName('he')->id)->toBe($character->id);

    // 4. Empty or null
    expect($story->resolveCharacterByName(''))->toBeNull();
    expect($story->resolveCharacterByName(null))->toBeNull();
});

test('heuristic fallback in story decomposition populates characters and environments', function () {
    $user = User::factory()->create();

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Heuristic Universe',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Naya Arunika',
        'gender' => 'female',
        'age' => 22,
        'appearance' => 'Dark espresso bob, denim tactical jacket',
        'prompt_keywords' => 'Naya Arunika, dark espresso bob',
    ]);
    $contentTitle->characters()->attach($character->id);

    $environment = Environment::create([
        'content_title_id' => $contentTitle->id,
        'name' => 'Neon Underpass',
        'setting_type' => 'urban',
        'lighting' => 'cyberpunk cyan neon',
        'prompt_keywords' => 'Neon Underpass, wet asphalt, cyan neon signs',
    ]);

    $story = Story::create([
        'user_id' => $user->id,
        'content_title_id' => $contentTitle->id,
        'title' => 'Heuristic Story',
        'premise' => 'Naya navigates the neon underpass alone.',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'narrative_script' => 'Naya walked slowly through the dark underpass. She gripped her blade as rain poured.',
        'status' => 'draft',
    ]);

    $service = app(StoryDecompositionService::class);
    $populatedEpisode = $service->generateScenesAndBoardsForEpisode($episode);

    expect($populatedEpisode->boards)->not->toBeEmpty();
    $firstBoard = $populatedEpisode->boards->first();

    // Characters present must not be empty
    expect($firstBoard->characters_present)->toContain('Naya Arunika');
    expect($firstBoard->environment_id)->toBe($environment->id);
    expect($firstBoard->visual_prompt)->toContain('Naya Arunika');
});

test('inspector modal always displays consistency tokens even without turnaround sheet data', function () {
    $user = User::factory()->create();

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Ren Kurogane',
        'gender' => 'male',
        'age' => 20,
        'appearance' => 'Silver messy hair, crimson eye, obsidian trench coat',
        'prompt_keywords' => 'Ren Kurogane, silver messy hair, crimson eye, obsidian coat',
        'sheet_data' => null, // No turnaround vision JSON generated yet
    ]);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Inspector Modal Test',
        'premise' => 'Testing modal token display.',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);
    $story->characters()->attach($character->id);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
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
        'characters_present' => ['Ren Kurogane'],
        'narration_chunk' => 'Ren stepped forward.',
        'visual_prompt' => 'Ren Kurogane standing in shadow',
        'status' => 'pending',
    ]);

    Livewire::test(StoryProjectDetail::class, ['story' => $story])
        ->call('inspectAsset', 'character', $character->id)
        ->assertSet('showAssetModal', true)
        ->assertSet('inspectingSheetTokens', $character->getConsistencyTokens())
        ->assertSeeHtml($character->getConsistencyTokens())
        ->assertSee('Active Consistency Prompt Tokens');
});

test('user can interactively bind and unbind characters on a board from the inspector', function () {
    $user = User::factory()->create();

    $character1 = Character::create([
        'user_id' => $user->id,
        'name' => 'Kaelen Shadow',
        'gender' => 'male',
        'age' => 26,
        'prompt_keywords' => 'Kaelen Shadow, stealth assassin',
    ]);

    $character2 = Character::create([
        'user_id' => $user->id,
        'name' => 'Lyra Dawnbringer',
        'gender' => 'female',
        'age' => 23,
        'prompt_keywords' => 'Lyra Dawnbringer, solar mage',
    ]);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Interactive Binding Test',
        'premise' => 'Testing live binding in inspector.',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);
    $story->characters()->attach([$character1->id, $character2->id]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
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
        'narration_chunk' => 'Shadows gather in the alleyway.',
        'visual_prompt' => 'Dark Fantasy Manhwa, Kaelen Shadow in the alleyway.',
        'characters_present' => ['Kaelen Shadow'],
        'status' => 'pending',
    ]);

    Livewire::test(StoryProjectDetail::class, ['story' => $story])
        ->call('bindCharacterToBoard', $board->id, $character2->id)
        ->assertHasNoErrors();

    $board->refresh();
    expect($board->characters_present)->toContain('Kaelen Shadow');
    expect($board->characters_present)->toContain('Lyra Dawnbringer');

    // Test unbinding
    Livewire::test(StoryProjectDetail::class, ['story' => $story])
        ->call('unbindCharacterFromBoard', $board->id, 'Kaelen Shadow')
        ->assertHasNoErrors();

    $board->refresh();
    expect($board->characters_present)->not->toContain('Kaelen Shadow');
    expect($board->characters_present)->toContain('Lyra Dawnbringer');
});

test('GenerateBoardImageJob attaches character reference sheet even when board uses alias', function () {
    $user = User::factory()->create();

    // Create a temporary dummy character reference sheet
    Storage::fake('public');
    $dummySheetRelative = 'sheets/character_test.png';
    Storage::disk('public')->put($dummySheetRelative, 'fake-image-bytes');
    $dummySheetAbsolute = Storage::disk('public')->path($dummySheetRelative);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Aoyama Ren',
        'gender' => 'male',
        'age' => 24,
        'prompt_keywords' => 'Aoyama Ren, cyber hoodie',
        'sheet_image_path' => $dummySheetRelative,
    ]);

    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Job Alias Test',
        'premise' => 'Testing reference image path attachment.',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);
    $story->characters()->attach($character->id);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);

    // The board only has the alias "Protagonist"
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'characters_present' => ['Protagonist'],
        'narration_chunk' => 'He leaped across the rooftop.',
        'visual_prompt' => 'Aoyama Ren jumping across neon rooftops',
        'status' => 'pending',
    ]);

    $mockImageService = Mockery::mock(ImageGenerationService::class);
    $mockImageService->shouldReceive('generateAndSave')
        ->once()
        ->withArgs(function ($prompt, $path, $provider, $aspectRatio, $referenceImagePaths) use ($dummySheetAbsolute) {
            return in_array($dummySheetAbsolute, $referenceImagePaths, true);
        })
        ->andReturn(true);

    $job = new GenerateBoardImageJob($board);
    $job->handle($mockImageService);

    $board->refresh();
    expect($board->image_path)->not->toBeNull();
    expect($board->status)->toBe('image_ready');
});
