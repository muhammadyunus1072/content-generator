<?php

use App\Livewire\ContentTitleDetail;
use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Episode;
use App\Models\Prop;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\ReferenceSheetAnalyzerService;
use Livewire\Livewire;

test('user can manage environments, props, and art direction in World Bible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Solo Hunter Chronicles',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);

    // Mock ReferenceSheetAnalyzerService for asset sheet generation
    $mockAnalyzer = Mockery::mock(ReferenceSheetAnalyzerService::class);
    $mockAnalyzer->shouldReceive('generateSheetImage')
        ->andReturn([
            'sheet_image_path' => 'sheets/test.png',
            'sheet_data' => [
                'entity_type' => 'environment',
                'consistency_prompt_tokens' => 'ancient subterranean temple, basalt monolith, glowing violet runes',
            ],
        ]);
    $this->app->instance(ReferenceSheetAnalyzerService::class, $mockAnalyzer);

    // 1. Create Environment via Unified Asset Studio
    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->call('openCreateAssetModal', 'environment')
        ->set('creationMode', 'prompt')
        ->set('envName', 'Subway Monolith Chamber')
        ->set('envSettingType', 'interior')
        ->set('envTimeOfDay', 'night')
        ->set('envAtmosphere', 'Eerie violet mana embers')
        ->set('envLighting', 'Dim glowing purple crystals')
        ->set('envPrompt', 'ancient subterranean temple, basalt monolith, glowing violet runes')
        ->call('saveAsset')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('environments', [
        'content_title_id' => $contentTitle->id,
        'name' => 'Subway Monolith Chamber',
        'setting_type' => 'interior',
    ]);

    // 2. Create Prop via Unified Asset Studio
    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->call('openCreateAssetModal', 'prop')
        ->set('creationMode', 'prompt')
        ->set('propName', 'Obsidian Dagger')
        ->set('propCategory', 'weapon')
        ->set('propPrompt', 'obsidian ritual dagger, black volcanic glass, purple aura')
        ->call('saveAsset')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('props', [
        'content_title_id' => $contentTitle->id,
        'name' => 'Obsidian Dagger',
        'category' => 'weapon',
    ]);

    // 3. Save Art Direction Rules
    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->set('artColorPalette', 'Obsidian, neon purple, cyan')
        ->set('artLightingMood', 'Dramatic volumetric lighting')
        ->set('artCameraFraming', 'Dynamic webtoon action angles')
        ->call('saveArtDirection')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('content_titles', [
        'id' => $contentTitle->id,
        'color_palette' => 'Obsidian, neon purple, cyan',
        'lighting_mood' => 'Dramatic volumetric lighting',
    ]);

    // 4. Test 1-Click Art Direction Preset
    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->call('applyArtPreset', 'solo_leveling')
        ->assertHasNoErrors();

    $contentTitle->refresh();
    expect($contentTitle->lighting_mood)->toContain('chiaroscuro');
});

test('storyboard decomposition attributes environment and characters to scene boards', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Solo Hunter',
        'visual_style' => 'Dark Fantasy Manhwa',
    ]);

    $env = Environment::create([
        'user_id' => $user->id,
        'content_title_id' => $contentTitle->id,
        'name' => 'Subway Monolith Chamber',
        'prompt_keywords' => 'ancient basalt monolith chamber',
    ]);

    $char = Character::create([
        'user_id' => $user->id,
        'name' => 'Naya Arunika',
        'gender' => 'female',
        'prompt_keywords' => '23yo Indonesian woman, dark espresso bob',
    ]);
    $contentTitle->characters()->attach($char->id);

    $story = Story::create([
        'user_id' => $user->id,
        'content_title_id' => $contentTitle->id,
        'title' => 'Episode 1',
        'premise' => 'Naya explores the subway.',
        'visual_style' => 'Dark Fantasy Manhwa',
        'status' => 'pending',
    ]);
    $story->characters()->attach($char->id);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'status' => 'draft',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Subway Chamber Entry',
        'narration_text' => 'Naya steps past the warning tape into the monolith chamber.',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'environment_id' => $env->id,
        'characters_present' => ['Naya Arunika'],
        'props_present' => ['Obsidian Dagger'],
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Naya steps past the warning tape into the monolith chamber.',
        'visual_prompt' => 'Dark Fantasy Manhwa, ancient basalt monolith chamber, 23yo Indonesian woman, dark espresso bob',
        'status' => 'pending',
    ]);

    expect($board->environment->id)->toBe($env->id);
    expect($board->characters_present)->toContain('Naya Arunika');
    expect($board->props_present)->toContain('Obsidian Dagger');
});
