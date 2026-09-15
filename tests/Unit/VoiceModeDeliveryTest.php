<?php

use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MultiActorVoiceSynthesisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('MultiActorVoiceSynthesisService prepareDialogueLines strictly uses narration chunk in solo_narrator mode', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    $board = new SceneBoard([
        'narration_chunk' => 'The lone wanderer walked through the stormy ruins.',
        'speech_bubbles' => [
            ['speaker' => 'Ren', 'text' => 'We must hurry!', 'emotion' => 'intense'],
            ['speaker' => 'Elena', 'text' => 'I cannot go on...', 'emotion' => 'sorrow'],
        ],
    ]);

    // When solo_narrator mode is passed
    $lines = $service->prepareDialogueLines($board, 'solo_narrator');

    expect($lines)->toHaveCount(1);
    expect($lines[0]['speaker'])->toBe('Narrator');
    expect($lines[0]['text'])->toBe('The lone wanderer walked through the stormy ruins.');
    expect($lines[0]['emotion'])->toBe('neutral');
});

test('MultiActorVoiceSynthesisService prepareDialogueLines uses speech bubbles in multi_actor mode', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    $board = new SceneBoard([
        'narration_chunk' => 'Background prose recap',
        'speech_bubbles' => [
            ['speaker' => 'Ren', 'text' => 'Stand back!', 'emotion' => 'shouting'],
            ['speaker' => 'Elena', 'text' => 'Behind you!', 'emotion' => 'intense'],
        ],
    ]);

    // When multi_actor mode is passed
    $lines = $service->prepareDialogueLines($board, 'multi_actor');

    expect($lines)->toHaveCount(2);
    expect($lines[0]['speaker'])->toBe('Ren');
    expect($lines[0]['text'])->toBe('Stand back!');
    expect($lines[0]['emotion'])->toBe('shouting');
    expect($lines[1]['speaker'])->toBe('Elena');
    expect($lines[1]['text'])->toBe('Behind you!');
    expect($lines[1]['emotion'])->toBe('intense');
});

test('Episode getEffectiveVoiceMode inherits from ContentTitle or defaults to multi_actor', function () {
    $user = User::factory()->create();

    // 1. ContentTitle with solo_narrator default
    $title = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Recap Series',
        'visual_style' => 'Comic',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-GuyNeural',
        'voice_mode' => 'solo_narrator',
    ]);
    $story = Story::create([
        'user_id' => $user->id,
        'content_title_id' => $title->id,
        'title' => 'Arc 1',
        'premise' => 'Recap story',
    ]);

    // Episode with null voice_mode should inherit solo_narrator
    $episodeInherited = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Recap Chapter',
        'voice_mode' => null,
    ]);
    expect($episodeInherited->getEffectiveVoiceMode())->toBe('solo_narrator');

    // Episode with explicit multi_actor override
    $episodeOverride = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 2,
        'title' => 'Special Drama Chapter',
        'voice_mode' => 'multi_actor',
    ]);
    expect($episodeOverride->getEffectiveVoiceMode())->toBe('multi_actor');
});
