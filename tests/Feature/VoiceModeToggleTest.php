<?php

use App\Jobs\GenerateBoardVoiceJob;
use App\Livewire\ComicStudio;
use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MultiActorVoiceSynthesisService;
use App\Services\TextToSpeechService;
use Livewire\Livewire;

test('ComicStudio createContentTitle persists voice_mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->set('title', 'Action Recap Title')
        ->set('description', 'A recap series')
        ->set('visualStyle', 'Manhwa Lineart')
        ->set('aspectRatio', '16:9')
        ->set('voiceName', 'en-US-ChristopherNeural')
        ->set('voiceMode', 'solo_narrator')
        ->call('createContentTitle');

    $title = ContentTitle::where('title', 'Action Recap Title')->first();
    expect($title)->not->toBeNull();
    expect($title->voice_mode)->toBe('solo_narrator');
});

test('ComicStudio toggleEpisodeVoiceMode toggles between multi_actor and solo_narrator', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Toggle Story',
        'premise' => 'Testing voice mode toggle',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'voice_mode' => 'multi_actor',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('toggleEpisodeVoiceMode', $episode->id);

    $episode->refresh();
    expect($episode->voice_mode)->toBe('solo_narrator');

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('toggleEpisodeVoiceMode', $episode->id);

    $episode->refresh();
    expect($episode->voice_mode)->toBe('multi_actor');
});

test('GenerateBoardVoiceJob bypasses multi-actor when episode is in solo_narrator mode', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Solo Story',
        'premise' => 'Testing solo mode job execution',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'voice_mode' => 'solo_narrator', // Solo mode active
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
        'narration_chunk' => 'The lone warrior stood firm.',
        'visual_prompt' => 'Warrior standing',
        'speech_bubbles' => [
            ['speaker' => 'Ren', 'text' => 'Never surrender!', 'emotion' => 'shouting'],
        ],
    ]);

    // Multi-actor service should NOT be called because mode is solo_narrator
    $multiVoiceMock = Mockery::mock(MultiActorVoiceSynthesisService::class);
    $multiVoiceMock->shouldNotReceive('synthesizeBoardDialogue');

    // Standard TTS should be called for narration_chunk
    $ttsMock = Mockery::mock(TextToSpeechService::class);
    $ttsMock->shouldReceive('synthesize')
        ->once()
        ->with(
            'The lone warrior stood firm.',
            Mockery::any(),
            Mockery::any(),
            'en-US-ChristopherNeural',
            Mockery::any(),
            Mockery::any(),
            Mockery::any()
        )
        ->andReturn([
            'audio_path' => 'mock_path.mp3',
            'subtitle_path' => null,
            'duration_seconds' => 3.2,
        ]);

    $job = new GenerateBoardVoiceJob($board);
    $job->handle($ttsMock, $multiVoiceMock);

    $board->refresh();
    expect($board->status)->toBe('audio_ready');
    expect($board->duration_seconds)->toBe(3.2);
});
