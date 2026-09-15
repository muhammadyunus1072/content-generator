<?php

use App\Jobs\GenerateContentJob;
use App\Models\Clip;
use App\Models\Transcription;
use App\Models\User;
use App\Models\Video;
use App\Services\ContentGenerationService;
use Illuminate\Support\Facades\Queue;

test('GenerateContentJob parses clip suggestions and updates status', function () {
    Queue::fake();

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'transcribed',
    ]);

    Transcription::create([
        'video_id' => $video->id,
        'transcript_text' => 'This is a complete transcript of a great video.',
        'speaker_segments' => [],
        'timeline' => [],
    ]);

    // Mock ContentGenerationService
    $mockContentService = Mockery::mock(ContentGenerationService::class);
    $mockContentService->shouldReceive('generateClips')
        ->once()
        ->with(Mockery::type(Video::class))
        ->andReturn([
            [
                'title' => 'Engagement Hook',
                'description' => 'The most engaging moment of the podcast.',
                'start_seconds' => 15.5,
                'end_seconds' => 45.0,
                'tags' => ['highlight', 'viral'],
            ],
            [
                'title' => 'Key Quote',
                'description' => 'A great quote explaining the core topic.',
                'start_seconds' => 50.0,
                'end_seconds' => 80.2,
                'tags' => ['quote', 'learning'],
            ],
        ]);

    $this->app->instance(ContentGenerationService::class, $mockContentService);

    // Execute job
    $job = new GenerateContentJob($video);
    $job->handle($mockContentService);

    $video->refresh();
    expect($video->status)->toBe('completed');
    expect($video->error_message)->toBeNull();

    // Verify clips database entries
    $clips = Clip::where('video_id', $video->id)->get();
    expect($clips->count())->toBe(2);

    $firstClip = $clips->first();
    expect($firstClip->title)->toBe('Engagement Hook');
    expect($firstClip->description)->toBe('The most engaging moment of the podcast.');
    expect((float) $firstClip->start_seconds)->toBe(15.5);
    expect((float) $firstClip->end_seconds)->toBe(45.0);
    expect($firstClip->metadata)->toBeArray();
    expect($firstClip->metadata['tags'][0])->toBe('highlight');
});

test('GenerateContentJob handles service failure gracefully', function () {
    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'transcribed',
    ]);

    // Mock ContentGenerationService to fail
    $mockContentService = Mockery::mock(ContentGenerationService::class);
    $mockContentService->shouldReceive('generateClips')
        ->once()
        ->with(Mockery::type(Video::class))
        ->andThrow(new Exception('Gemini API quota exceeded'));

    $this->app->instance(ContentGenerationService::class, $mockContentService);

    try {
        GenerateContentJob::dispatchSync($video);
        $this->fail('Job did not throw expected exception');
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('Gemini API quota exceeded');
    }

    $video->refresh();
    expect($video->status)->toBe('failed');
    expect($video->error_message)->toBe('Gemini API quota exceeded');
});
