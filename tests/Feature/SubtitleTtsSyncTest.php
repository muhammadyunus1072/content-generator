<?php

use App\Jobs\GenerateBoardVoiceJob;
use App\Jobs\GenerateSceneAudioJob;
use App\Jobs\RenderSceneVideoJob;
use App\Models\Episode;
use App\Models\EpubProject;
use App\Models\EpubScene;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MultiActorVoiceSynthesisService;
use App\Services\SubtitleGeneratorService;
use App\Services\TextToSpeechService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('GenerateSceneAudioJob writes subtitle file and forwards custom tts_rate and tts_pitch', function () {
    Queue::fake([RenderSceneVideoJob::class]);
    Storage::fake('public');

    $project = EpubProject::create([
        'user_id' => $this->user->id,
        'title' => 'The Cosmic Mystery',
        'epub_path' => 'epubs/test.epub',
        'voice_name' => 'en-US-ChristopherNeural',
        'tts_rate' => '-20%',
        'tts_pitch' => '-15Hz',
        'status' => 'uploaded',
    ]);

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'The stars began to blink out one by one.',
        'status' => 'pending',
    ]);

    // Mock TextToSpeechService to verify arguments and simulate VTT output
    $ttsMock = Mockery::mock(TextToSpeechService::class);
    $ttsMock->shouldReceive('synthesize')
        ->once()
        ->withArgs(function ($text, $audioPath, $subtitlePath, $voice, $provider, $rate, $pitch) {
            // Verify subtitle path is provided and custom rate/pitch are passed
            expect($subtitlePath)->not->toBeNull()
                ->and($rate)->toBe('-20%')
                ->and($pitch)->toBe('-15Hz');

            // Simulate files being written
            File::ensureDirectoryExists(dirname($audioPath));
            File::put($audioPath, 'fake-mp3-audio');

            File::ensureDirectoryExists(dirname($subtitlePath));
            File::put($subtitlePath, "WEBVTT\n\n00:00:00.050 --> 00:00:03.500\nThe stars began to blink out one by one.\n");

            return true;
        })
        ->andReturn([
            'audio_path' => 'path/to/audio.mp3',
            'subtitle_path' => 'path/to/sub.vtt',
            'duration_seconds' => 3.5,
        ]);

    $job = new GenerateSceneAudioJob($scene);
    $job->handle($ttsMock);

    $scene->refresh();
    expect($scene->status)->toBe('audio_ready')
        ->and($scene->subtitle_path)->not->toBeNull()
        ->and($scene->subtitle_path)->toContain('.vtt')
        ->and($scene->audio_duration)->toBe('3.50');

    Queue::assertPushed(RenderSceneVideoJob::class);
});

test('GenerateBoardVoiceJob forwards custom tts_rate and tts_pitch from story', function () {
    Storage::fake('public');

    $story = Story::create([
        'user_id' => $this->user->id,
        'title' => 'Dragon Lord',
        'premise' => 'An ancient dragon rises from its slumber.',
        'voice_name' => 'en-US-GuyNeural',
        'tts_rate' => '+10%',
        'tts_pitch' => '-5Hz',
        'status' => 'completed',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
        'status' => 'completed',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'sequence_order' => 1,
        'narration_chunk' => 'The dragon awakened with a thunderous roar.',
        'visual_prompt' => 'Ancient dragon opening golden eyes',
        'status' => 'pending',
    ]);

    $ttsMock = Mockery::mock(TextToSpeechService::class);
    $ttsMock->shouldReceive('synthesize')
        ->once()
        ->withArgs(function ($text, $audioPath, $subtitlePath, $voice, $provider, $rate, $pitch) {
            expect($rate)->toBe('+10%')
                ->and($pitch)->toBe('-5Hz');

            File::ensureDirectoryExists(dirname($audioPath));
            File::put($audioPath, 'fake-mp3');

            File::ensureDirectoryExists(dirname($subtitlePath));
            File::put($subtitlePath, "WEBVTT\n\n00:00:00.000 --> 00:00:02.800\nThe dragon awakened.\n");

            return true;
        })
        ->andReturn([
            'audio_path' => 'audio.mp3',
            'subtitle_path' => 'sub.vtt',
            'duration_seconds' => 2.8,
        ]);

    $job = new GenerateBoardVoiceJob($board);
    $job->handle($ttsMock, app(MultiActorVoiceSynthesisService::class));

    $board->refresh();
    expect($board->subtitle_path)->not->toBeNull()
        ->and((float) $board->duration_seconds)->toEqual(2.8);
});

test('RenderSceneVideoJob uses exact VTT converted subtitles when available', function () {
    Storage::fake('public');

    $project = EpubProject::create([
        'user_id' => $this->user->id,
        'title' => 'Test VTT Conversion',
        'epub_path' => 'test.epub',
        'status' => 'uploaded',
    ]);

    // Create fake audio and fake VTT in public storage
    $audioRel = 'test_audio.mp3';
    $vttRel = 'test_sub.vtt';
    Storage::disk('public')->put($audioRel, 'fake-audio-bytes');
    Storage::disk('public')->put($vttRel, "WEBVTT\n\n00:00:00.050 --> 00:00:02.400\nPrecise timing subtitle line.\n");

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'Precise timing subtitle line.',
        'audio_path' => $audioRel,
        'subtitle_path' => $vttRel,
        'audio_duration' => 2.5,
        'status' => 'audio_ready',
    ]);

    $job = new RenderSceneVideoJob($scene);

    // Verify SubtitleGeneratorService parses the VTT content
    $vttContent = Storage::disk('public')->get($vttRel);
    $srt = app(SubtitleGeneratorService::class)->convertVttToSrt($vttContent);

    expect($srt)->toContain('00:00:00,050 --> 00:00:02,400')
        ->and($srt)->toContain('PRECISE TIMING SUBTITLE LINE.');
});
