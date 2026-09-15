<?php

use App\Livewire\ContentTitleDetail;
use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\User;
use App\Services\ReferenceSheetAnalyzerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('character model supports reference sheet image path and structured sheet data', function () {
    $character = Character::create([
        'name' => 'Shibuya Shion',
        'gender' => 'female',
        'entity_type' => 'human',
        'age' => 20,
        'appearance' => 'Purple hoodie, denim shorts, black sneakers, lavender eyes',
        'prompt_keywords' => null,
        'sheet_image_path' => 'sheets/character_1.png',
        'sheet_data' => [
            'entity_type' => 'human',
            'three_views' => [
                'front' => 'Front view wearing oversized lavender purple hoodie, denim shorts',
                'side' => 'Side profile showing neat chin-length dark bob haircut and backpack',
                'back' => 'Back view showing black backpack and hoodie silhouette',
            ],
            'color_palette' => [
                ['name' => 'Hoodie', 'color' => 'Lavender Purple'],
                ['name' => 'Hair', 'color' => 'Deep Indigo / Black'],
                ['name' => 'Eyes', 'color' => 'Purple'],
            ],
            'materials' => 'Loopback cotton hoodie, selvedge denim, nylon canvas backpack',
            'consistency_prompt_tokens' => 'Shibuya Shion, lavender purple hoodie, dark bob haircut, purple eyes, consistent anime model sheet',
        ],
    ]);

    expect($character->entity_type)->toBe('human');
    expect($character->sheet_data)->toBeArray();
    expect($character->getConsistencyTokens())->toBe('Shibuya Shion, lavender purple hoodie, dark bob haircut, purple eyes, consistent anime model sheet');

    $character->prompt_keywords = 'Shibuya Shion, custom explicit override';
    expect($character->getConsistencyTokens())->toBe('Shibuya Shion, custom explicit override');
});

test('user can upload and deconstruct a reference sheet via ContentTitleDetail direct upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Shibuya Motion Designer',
        'genre' => 'Cyberpunk Webtoon',
        'visual_style' => 'Modern Anime Aesthetic',
        'aspect_ratio' => '16:9',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Aoyama Ren',
        'gender' => 'male',
        'entity_type' => 'human',
        'age' => 21,
        'prompt_keywords' => 'Aoyama Ren, tech hoodie',
    ]);
    $contentTitle->characters()->attach($character->id);

    $file = UploadedFile::fake()->image('aoyama_ren_sheet.png', 1280, 720);

    // Mock ReferenceSheetAnalyzerService
    $mockAnalyzer = Mockery::mock(ReferenceSheetAnalyzerService::class);
    $mockAnalyzer->shouldReceive('analyzeSheetImage')
        ->once()
        ->andReturn([
            'entity_type' => 'human',
            'three_views' => [
                'front' => 'Front view in purple-navy tech hoodie and cargo pants',
                'side' => 'Side view with roll-top backpack',
                'back' => 'Back view with tactical straps',
            ],
            'consistency_prompt_tokens' => 'Aoyama Ren, tech hoodie purple-navy, cargo pants, violet eyes, messy dark hair',
        ]);

    $this->app->instance(ReferenceSheetAnalyzerService::class, $mockAnalyzer);

    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->set('inlineUploadFile', $file)
        ->call('directUploadSheet', 'character', $character->id)
        ->assertHasNoErrors();

    $character->refresh();
    expect($character->sheet_image_path)->not->toBeNull();
    expect($character->sheet_data['consistency_prompt_tokens'])->toBe('Aoyama Ren, tech hoodie purple-navy, cargo pants, violet eyes, messy dark hair');
});

test('inspect sheet modal loads structured sheet data correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Fantasy Woods',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '16:9',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);

    $environment = Environment::create([
        'user_id' => $user->id,
        'content_title_id' => $contentTitle->id,
        'name' => 'The Whispering Woods',
        'setting_type' => 'fantasy',
        'time_of_day' => 'daylight',
        'prompt_keywords' => 'Fantasy woods, bioluminescent tree roots',
        'sheet_image_path' => 'sheets/whispering_woods.png',
        'sheet_data' => [
            'main_view' => 'Daylight forest with massive central oak tree with glowing blue roots',
            'exterior_view' => 'Surrounding misty canopy with golden god rays',
            'materials_and_textures' => 'Mossy bark, glowing blue mushrooms, stone spirit arch',
            'consistency_prompt_tokens' => 'The Whispering Woods, ancient giant tree, glowing cyan roots, dense ferns, sunlight beams',
        ],
    ]);

    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->call('inspectSheet', 'environment', $environment->id)
        ->assertSet('showSheetModal', true)
        ->assertSet('inspectingSheetTitle', 'The Whispering Woods - Reference Sheet')
        ->assertSet('inspectingSheetData', $environment->sheet_data);
});
