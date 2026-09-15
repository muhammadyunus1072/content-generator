<?php

use App\Jobs\GenerateContentJob;
use App\Jobs\GenerateSubtitlesJob;
use App\Models\Transcription;
use App\Models\User;
use App\Models\Video;
use App\Services\TranscriptionService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

test('GenerateSubtitlesJob transcribes audio and updates status', function () {
    Storage::fake('public');
    Bus::fake([GenerateContentJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'audio_path' => 'audio/podcast_12345.mp3',
        'status' => 'audio_extracted',
    ]);

    // Create dummy audio file so exists check passes
    Storage::disk('public')->put('audio/podcast_12345.mp3', 'dummy audio content');
    $audioFullPath = Storage::disk('public')->path('audio/podcast_12345.mp3');

    // Mock the TranscriptionService
    $mockTranscriptionService = Mockery::mock(TranscriptionService::class);
    $mockTranscriptionService->shouldReceive('transcribe')
        ->once()
        ->with($audioFullPath, Storage::disk('public')->path('audio'))
        ->andReturn([
            'text' => 'This is a test transcript.',
            'segments' => [
                [
                    'start' => 0.0,
                    'end' => 2.5,
                    'text' => 'This is a test transcript.',
                    'speaker' => 'SPEAKER_00',
                    'words' => [
                        ['word' => 'This', 'start' => 0.0, 'end' => 0.5, 'score' => 0.99],
                        ['word' => 'is', 'start' => 0.5, 'end' => 0.8, 'score' => 0.98],
                        ['word' => 'a', 'start' => 0.8, 'end' => 1.0, 'score' => 0.97],
                        ['word' => 'test', 'start' => 1.0, 'end' => 1.5, 'score' => 0.99],
                        ['word' => 'transcript.', 'start' => 1.5, 'end' => 2.5, 'score' => 0.99],
                    ],
                ],
            ],
            'words' => [
                ['word' => 'This', 'start' => 0.0, 'end' => 0.5, 'score' => 0.99],
                ['word' => 'is', 'start' => 0.5, 'end' => 0.8, 'score' => 0.98],
                ['word' => 'a', 'start' => 0.8, 'end' => 1.0, 'score' => 0.97],
                ['word' => 'test', 'start' => 1.0, 'end' => 1.5, 'score' => 0.99],
                ['word' => 'transcript.', 'start' => 1.5, 'end' => 2.5, 'score' => 0.99],
            ],
        ]);

    $this->app->instance(TranscriptionService::class, $mockTranscriptionService);

    // Execute job
    GenerateSubtitlesJob::dispatchSync($video);

    $video->refresh();
    expect($video->status)->toBe('transcribed');
    expect($video->error_message)->toBeNull();

    // Verify database entry
    $transcription = Transcription::where('video_id', $video->id)->first();
    expect($transcription)->not->toBeNull();
    expect($transcription->transcript_text)->toBe('This is a test transcript.');
    expect($transcription->speaker_segments)->toBeArray();
    expect($transcription->speaker_segments[0]['speaker'])->toBe('SPEAKER_00');
    expect($transcription->timeline)->toBeArray();
    expect(count($transcription->timeline))->toBe(5);

    // Verify next step was queued
    Bus::assertDispatched(GenerateContentJob::class, function ($job) use ($video) {
        return $job->video->id === $video->id;
    });
});

test('GenerateSubtitlesJob handles missing audio file', function () {
    Storage::fake('public');
    Bus::fake([GenerateContentJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'audio_path' => 'audio/nonexistent.mp3',
        'status' => 'audio_extracted',
    ]);

    try {
        GenerateSubtitlesJob::dispatchSync($video);
        $this->fail('Job did not throw expected exception');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Audio file not found');
    }

    $video->refresh();
    expect($video->status)->toBe('failed');
    expect($video->error_message)->toContain('Audio file not found');
});
