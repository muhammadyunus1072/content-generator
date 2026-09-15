<?php

use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\User;
use App\Services\CharacterBibleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('CharacterBibleService generates fallback character profile correctly', function () {
    $service = new CharacterBibleService;
    $profile = $service->generateCharacterProfile('Aoyama Ren', 'Dark Fantasy Manhwa', 'protagonist');

    expect($profile)->toBeArray();
    expect($profile['name'])->toBe('Aoyama Ren');
    expect($profile['role'])->toBe('protagonist');
    expect($profile['prompt_keywords'])->toContain('Aoyama Ren');
    expect($profile['color_theme'])->toBe('#3B82F6');
});

test('CharacterBibleService resolves character voice with fallback', function () {
    $service = new CharacterBibleService;

    $maleChar = new Character(['name' => 'Ren', 'gender' => 'male']);
    $voiceConfig = $service->resolveVoiceForCharacter($maleChar, 'en-US-ChristopherNeural');
    expect($voiceConfig['voice_id'])->toBe('en-US-ChristopherNeural');

    $femaleChar = new Character(['name' => 'Aria', 'gender' => 'female']);
    $femaleVoiceConfig = $service->resolveVoiceForCharacter($femaleChar);
    expect($femaleVoiceConfig['voice_id'])->toBe('id-ID-GadisNeural');

    $customChar = new Character(['name' => 'Custom', 'voice_id' => 'en-US-GuyNeural']);
    $customConfig = $service->resolveVoiceForCharacter($customChar);
    expect($customConfig['voice_id'])->toBe('en-US-GuyNeural');
});

test('CharacterBibleService exports complete structured bible for ContentTitle', function () {
    $user = User::factory()->create();
    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Cursed Dynasty',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-GuyNeural',
    ]);

    $char = Character::create([
        'user_id' => $user->id,
        'name' => 'Lord Valen',
        'role' => 'antagonist',
        'gender' => 'male',
        'age' => 32,
        'prompt_keywords' => 'Valen, crimson eyes, black armor',
        'color_theme' => '#EF4444',
    ]);

    $contentTitle->characters()->attach($char->id);

    $service = new CharacterBibleService;
    $bible = $service->exportBible($contentTitle);

    expect($bible)->toHaveCount(1);
    expect($bible[0]['name'])->toBe('Lord Valen');
    expect($bible[0]['role'])->toBe('antagonist');
    expect($bible[0]['color_theme'])->toBe('#EF4444');
});
