<?php

use App\Models\Character;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MultiActorVoiceSynthesisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('MultiActorVoiceSynthesisService resolves narrator voice from story or fallback', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Voice Chronicles',
        'premise' => 'Testing narrator resolution.',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Pilot',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Intro',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'In a world torn by war...',
        'visual_prompt' => 'A lone hero',
    ]);

    $voiceConfig = $service->resolveSpeakerVoice('Narrator', $board);

    expect($voiceConfig['voice'])->toBe('en-US-ChristopherNeural');
    expect($voiceConfig['character_id'])->toBeNull();

    // Empty speaker also defaults to narrator
    $emptyConfig = $service->resolveSpeakerVoice('', $board);
    expect($emptyConfig['voice'])->toBe('en-US-ChristopherNeural');
});

test('MultiActorVoiceSynthesisService resolves character voice from Character Bible', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Hero Journey',
        'premise' => 'Testing character voice resolution.',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Chapter 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Meeting',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'They met at dawn.',
        'visual_prompt' => 'Two warriors talking',
    ]);

    // Character 1 with explicit voice_id
    $ren = Character::create([
        'user_id' => $user->id,
        'name' => 'Ren',
        'gender' => 'male',
        'voice_id' => 'en-US-GuyNeural',
    ]);
    $story->characters()->attach($ren->id);

    // Character 2 female without explicit voice_id (gender fallback)
    $elena = Character::create([
        'user_id' => $user->id,
        'name' => 'Elena',
        'gender' => 'female',
        'voice_id' => null,
    ]);
    $story->characters()->attach($elena->id);

    $renConfig = $service->resolveSpeakerVoice('Ren', $board);
    expect($renConfig['voice'])->toBe('en-US-GuyNeural');
    expect($renConfig['character_id'])->toBe($ren->id);

    $elenaConfig = $service->resolveSpeakerVoice('Elena', $board);
    expect($elenaConfig['voice'])->toBe('en-US-JennyNeural');
    expect($elenaConfig['character_id'])->toBe($elena->id);
});

test('MultiActorVoiceSynthesisService modulates rate and pitch based on emotion tags', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    // Neutral
    $neutral = $service->getEmotionAcousticParams('neutral');
    expect($neutral['rate'])->toBe('+0%');
    expect($neutral['pitch'])->toBe('+0Hz');

    // Intense
    $intense = $service->getEmotionAcousticParams('intense');
    expect($intense['rate'])->toBe('+15%');
    expect($intense['pitch'])->toBe('+8Hz');

    // Shouting
    $shouting = $service->getEmotionAcousticParams('shouting');
    expect($shouting['rate'])->toBe('+25%');
    expect($shouting['pitch'])->toBe('+14Hz');

    // Whisper
    $whisper = $service->getEmotionAcousticParams('whisper');
    expect($whisper['rate'])->toBe('-15%');
    expect($whisper['pitch'])->toBe('-6Hz');

    // Sorrow
    $sorrow = $service->getEmotionAcousticParams('sorrow');
    expect($sorrow['rate'])->toBe('-20%');
    expect($sorrow['pitch'])->toBe('-12Hz');

    // Joy
    $joy = $service->getEmotionAcousticParams('joy');
    expect($joy['rate'])->toBe('+15%');
    expect($joy['pitch'])->toBe('+6Hz');

    // Terrified
    $terrified = $service->getEmotionAcousticParams('terrified');
    expect($terrified['rate'])->toBe('+10%');
    expect($terrified['pitch'])->toBe('+16Hz');
});

test('MultiActorVoiceSynthesisService prepares structured dialogue lines from speech bubbles', function () {
    $service = app(MultiActorVoiceSynthesisService::class);

    $board = new SceneBoard([
        'narration_chunk' => 'The battle begins.',
        'speech_bubbles' => [
            ['speaker' => 'Ren', 'text' => 'Charge forward!', 'emotion' => 'shouting'],
            ['speaker' => 'Elena', 'text' => 'Be careful...', 'emotion' => 'whisper'],
            ['speaker' => '', 'text' => 'A shadow looms.', 'emotion' => 'neutral'],
        ],
    ]);

    $lines = $service->prepareDialogueLines($board);

    expect($lines)->toHaveCount(3);
    expect($lines[0]['speaker'])->toBe('Ren');
    expect($lines[0]['text'])->toBe('Charge forward!');
    expect($lines[0]['emotion'])->toBe('shouting');

    expect($lines[1]['speaker'])->toBe('Elena');
    expect($lines[1]['emotion'])->toBe('whisper');

    expect($lines[2]['speaker'])->toBe('Narrator');
});

test('MultiActorVoiceSynthesisService generates synchronized WebVTT subtitles', function () {
    Storage::fake('public');
    $service = app(MultiActorVoiceSynthesisService::class);

    $timeline = [
        [
            'speaker' => 'Ren',
            'text' => 'I will protect everyone!',
            'start_time' => 0.0,
            'end_time' => 2.45,
        ],
        [
            'speaker' => 'Elena',
            'text' => 'Together then.',
            'start_time' => 2.70,
            'end_time' => 4.10,
        ],
    ];

    $vttPath = Storage::disk('public')->path('test_subtitles.vtt');
    $service->generateSynchronizedVttSubtitles($timeline, $vttPath);

    expect(File::exists($vttPath))->toBeTrue();
    $content = File::get($vttPath);

    expect($content)->toContain('WEBVTT');
    expect($content)->toContain('00:00:00.000 --> 00:00:02.450');
    expect($content)->toContain('<v REN>[REN]: I will protect everyone!');
    expect($content)->toContain('00:00:02.700 --> 00:00:04.100');
    expect($content)->toContain('<v ELENA>[ELENA]: Together then.');
});
