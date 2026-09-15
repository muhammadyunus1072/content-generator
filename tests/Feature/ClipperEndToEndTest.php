<?php

use App\Jobs\CutClipJob;
use App\Jobs\DownloadVideoJob;
use App\Jobs\ExtractAudioJob;
use App\Livewire\ClipManager;
use App\Livewire\VideoUpload;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use App\Services\YoutubeDownloadService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('full manual flow: URL submission to clip generation and clip manager display', function () {
    Storage::fake('local');
    Storage::fake('public');
    Bus::fake([DownloadVideoJob::class, CutClipJob::class, ExtractAudioJob::class]);

    $user = User::factory()->create();

    // 1. User submits URL in manual mode with 2 timestamp intervals on Dashboard
    Livewire::actingAs($user)
        ->test(VideoUpload::class)
        ->set('mode', 'url')
        ->set('clipMode', 'manual')
        ->set('videoUrl', 'https://www.youtube.com/watch?v=sample123')
        ->set('title', 'My Viral Podcast')
        ->set('manualClips', [
            ['start' => 10, 'end' => 40],
            ['start' => 60, 'end' => 90],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('video-uploaded');

    $video = Video::first();
    expect($video)->not->toBeNull();
    expect($video->processing_mode)->toBe('manual');
    expect($video->status)->toBe('downloading');
    expect($video->clips)->toHaveCount(2);

    // 2. Mock YoutubeDownloadService and execute DownloadVideoJob
    $mockDownload = Mockery::mock(YoutubeDownloadService::class);
    $mockDownload->shouldReceive('getTitle')->once()->andReturn('My Viral Podcast Title');
    $mockDownload->shouldReceive('download')->once()->andReturnNull();
    app()->instance(YoutubeDownloadService::class, $mockDownload);

    (new DownloadVideoJob($video, 'https://www.youtube.com/watch?v=sample123'))->handle($mockDownload);

    $video->refresh();
    expect($video->status)->toBe('completed');
    expect($video->video_path)->not->toBeEmpty();

    // AI audio extraction must be skipped in manual mode
    Bus::assertNotDispatched(ExtractAudioJob::class);
    // CutClipJob must be dispatched for each manual clip
    Bus::assertDispatched(CutClipJob::class, 2);

    // 3. Verify ClipManager loads the clips for the video
    $manager = Livewire::actingAs($user)
        ->test(ClipManager::class)
        ->dispatch('video-selected', $video->id)
        ->assertSet('videoId', $video->id);

    $editingClips = $manager->get('editingClips');
    expect(count($editingClips))->toBe(2);
    expect((float) $editingClips[$video->clips[0]->id]['start_seconds'])->toEqual(10.0);
    expect((float) $editingClips[$video->clips[0]->id]['end_seconds'])->toEqual(40.0);
});

test('full automatic pipeline: skips manual clip creation and dispatches audio extraction', function () {
    Storage::fake('local');
    Storage::fake('public');
    Bus::fake([DownloadVideoJob::class, ExtractAudioJob::class]);

    $user = User::factory()->create();

    // User submits URL in automatic mode
    Livewire::actingAs($user)
        ->test(VideoUpload::class)
        ->set('mode', 'url')
        ->set('clipMode', 'automatic')
        ->set('videoUrl', 'https://www.youtube.com/watch?v=sampleAuto')
        ->set('title', 'Auto AI Video')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('video-uploaded');

    $video = Video::first();
    expect($video)->not->toBeNull();
    expect($video->processing_mode)->toBe('automatic');
    expect($video->status)->toBe('downloading');
    expect($video->clips)->toHaveCount(0);

    // Mock YoutubeDownloadService
    $mockDownload = Mockery::mock(YoutubeDownloadService::class);
    $mockDownload->shouldReceive('getTitle')->once()->andReturn('Auto AI Video');
    $mockDownload->shouldReceive('download')->once()->andReturnNull();
    app()->instance(YoutubeDownloadService::class, $mockDownload);

    (new DownloadVideoJob($video, 'https://www.youtube.com/watch?v=sampleAuto'))->handle($mockDownload);

    $video->refresh();
    expect($video->status)->toBe('pending');
    expect($video->video_path)->not->toBeEmpty();

    // ExtractAudioJob should be dispatched in automatic mode
    Bus::assertDispatched(ExtractAudioJob::class, function ($job) use ($video) {
        return $job->video->id === $video->id;
    });
});
