<?php

use App\Jobs\CutClipJob;
use App\Jobs\DownloadVideoJob;
use App\Jobs\ExtractAudioJob;
use App\Models\User;
use App\Models\Video;
use App\Services\YoutubeDownloadService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

test('DownloadVideoJob downloads video and updates model and dispatches audio extraction', function () {
    Storage::fake('local');
    Bus::fake([ExtractAudioJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'video_path' => '',
        'status' => 'downloading',
    ]);

    // Mock the YoutubeDownloadService
    $mockDownloadService = Mockery::mock(YoutubeDownloadService::class);

    // Expect getTitle to be called once
    $mockDownloadService->shouldReceive('getTitle')
        ->once()
        ->with('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->andReturn('Rick Astley - Never Gonna Give You Up');

    // Expect download to be called once
    $mockDownloadService->shouldReceive('download')
        ->once()
        ->with('https://www.youtube.com/watch?v=dQw4w9WgXcQ', Mockery::type('string'), 900)
        ->andReturnNull();

    $this->app->instance(YoutubeDownloadService::class, $mockDownloadService);

    // Execute job synchronously
    DownloadVideoJob::dispatchSync($video, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    $video->refresh();
    expect($video->status)->toBe('pending');
    expect($video->title)->toBe('Rick Astley - Never Gonna Give You Up');
    expect($video->original_filename)->toBe('Rick Astley - Never Gonna Give You Up.mp4');
    expect($video->video_path)->not->toBeEmpty();
    expect($video->video_path)->toStartWith('videos/download_');

    // Assert next step is dispatched
    Bus::assertDispatched(ExtractAudioJob::class, function ($job) use ($video) {
        return $job->video->id === $video->id;
    });
});

test('DownloadVideoJob in manual mode dispatches CutClipJob and skips audio extraction', function () {
    Storage::fake('local');
    Bus::fake([CutClipJob::class, ExtractAudioJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'https://www.youtube.com/watch?v=manual_test',
        'video_path' => '',
        'status' => 'downloading',
        'processing_mode' => 'manual',
    ]);

    $clip1 = $video->clips()->create([
        'title' => 'Clip 1',
        'start_seconds' => 10,
        'end_seconds' => 30,
        'status' => 'pending',
    ]);

    $clip2 = $video->clips()->create([
        'title' => 'Clip 2',
        'start_seconds' => 45,
        'end_seconds' => 60,
        'status' => 'pending',
    ]);

    $mockDownloadService = Mockery::mock(YoutubeDownloadService::class);
    $mockDownloadService->shouldReceive('getTitle')
        ->once()
        ->with('https://www.youtube.com/watch?v=manual_test')
        ->andReturn('Manual Test Video');

    $mockDownloadService->shouldReceive('download')
        ->once()
        ->with('https://www.youtube.com/watch?v=manual_test', Mockery::type('string'), 900)
        ->andReturnNull();

    $this->app->instance(YoutubeDownloadService::class, $mockDownloadService);

    DownloadVideoJob::dispatchSync($video, 'https://www.youtube.com/watch?v=manual_test');

    $video->refresh();
    expect($video->status)->toBe('completed');
    expect($video->processing_mode)->toBe('manual');
    expect($video->video_path)->not->toBeEmpty();

    // AI audio extraction should NOT be dispatched
    Bus::assertNotDispatched(ExtractAudioJob::class);

    // CutClipJob should be dispatched for both clips
    Bus::assertDispatched(CutClipJob::class, 2);
});
