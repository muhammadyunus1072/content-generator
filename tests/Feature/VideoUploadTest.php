<?php

use App\Jobs\CutClipJob;
use App\Jobs\ExtractAudioJob;
use App\Livewire\VideoUpload;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('authenticated users can upload a video in automatic mode', function () {
    Storage::fake('local');
    Bus::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('test-podcast.mp4', 5000, 'video/mp4');

    Livewire::test(VideoUpload::class)
        ->set('clipMode', 'automatic')
        ->set('videoFile', $file)
        ->set('title', 'My Awesome Podcast')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('video-uploaded');

    // Assert video was stored in local storage
    $video = Video::first();
    expect($video)->not->toBeNull();
    expect($video->title)->toBe('My Awesome Podcast');
    expect($video->original_filename)->toBe('test-podcast.mp4');
    expect($video->status)->toBe('pending');
    expect($video->processing_mode)->toBe('automatic');
    expect($video->user_id)->toBe($user->id);

    Storage::disk('local')->assertExists($video->video_path);

    // Assert background job was dispatched
    Bus::assertDispatched(ExtractAudioJob::class, function ($job) use ($video) {
        return $job->video->id === $video->id;
    });
});

test('authenticated users can upload a video in manual mode with timestamps', function () {
    Storage::fake('local');
    Bus::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('test-podcast.mp4', 5000, 'video/mp4');

    Livewire::test(VideoUpload::class)
        ->set('clipMode', 'manual')
        ->set('manualClips', [
            ['start' => 5, 'end' => 25],
            ['start' => 30, 'end' => 60],
        ])
        ->set('videoFile', $file)
        ->set('title', 'Manual Clips Podcast')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('video-uploaded');

    $video = Video::first();
    expect($video)->not->toBeNull();
    expect($video->processing_mode)->toBe('manual');
    expect($video->status)->toBe('completed');
    expect($video->clips)->toHaveCount(2);

    expect((float) $video->clips[0]->start_seconds)->toEqual(5.0);
    expect((float) $video->clips[0]->end_seconds)->toEqual(25.0);
    expect((float) $video->clips[1]->start_seconds)->toEqual(30.0);
    expect((float) $video->clips[1]->end_seconds)->toEqual(60.0);

    // AI audio extraction should NOT be dispatched
    Bus::assertNotDispatched(ExtractAudioJob::class);
    // CutClipJob should be dispatched for the manual clips
    Bus::assertDispatched(CutClipJob::class, 2);
});

test('it validates file type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(VideoUpload::class)
        ->set('videoFile', $invalidFile)
        ->call('save')
        ->assertHasErrors(['videoFile']);
});

test('it validates manual timestamps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');

    Livewire::test(VideoUpload::class)
        ->set('clipMode', 'manual')
        ->set('manualClips', [
            ['start' => 20, 'end' => 10], // end is less than start
        ])
        ->set('videoFile', $file)
        ->call('save')
        ->assertHasErrors(['manualClips.0.end']);
});
