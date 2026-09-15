<?php

use App\Jobs\ExtractAudioJob;
use App\Jobs\GenerateSubtitlesJob;
use App\Models\User;
use App\Models\Video;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

test('ExtractAudioJob processes video and updates status', function () {
    Storage::fake('local');
    Bus::fake([GenerateSubtitlesJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'pending',
    ]);

    Storage::disk('local')->put($video->video_path, 'dummy video content');

    // Mock FFMpeg chain
    $exporterMock = Mockery::mock();
    $exporterMock->shouldReceive('toDisk')->once()->with('public')->andReturnSelf();
    $exporterMock->shouldReceive('inFormat')->once()->with(Mockery::type(Mp3::class))->andReturnSelf();
    $exporterMock->shouldReceive('save')->once()->andReturnTrue();

    $mediaMock = Mockery::mock();
    $mediaMock->shouldReceive('export')->once()->andReturn($exporterMock);

    $ffmpegMock = Mockery::mock();
    $ffmpegMock->shouldReceive('open')->once()->with($video->video_path)->andReturn($mediaMock);

    FFMpeg::shouldReceive('fromDisk')->once()->with('local')->andReturn($ffmpegMock);

    // Dispatch the job synchronously
    ExtractAudioJob::dispatchSync($video);

    // Refresh database record
    $video->refresh();

    expect($video->status)->toBe('audio_extracted');
    expect($video->audio_path)->not->toBeNull();
    expect($video->audio_path)->toStartWith('audio/podcast_');
    expect($video->error_message)->toBeNull();

    // Verify next step was queued
    Bus::assertDispatched(GenerateSubtitlesJob::class, function ($job) use ($video) {
        return $job->video->id === $video->id;
    });
});

test('ExtractAudioJob handles failure gracefully', function () {
    Storage::fake('local');
    Bus::fake([GenerateSubtitlesJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'pending',
    ]);

    Storage::disk('local')->put($video->video_path, 'dummy video content');

    // Mock FFMpeg to throw an exception
    FFMpeg::shouldReceive('fromDisk')->once()->with('local')->andThrow(new Exception('FFmpeg failed to initialize'));

    // We expect the job to throw an exception but update the database record first
    try {
        ExtractAudioJob::dispatchSync($video);
        $this->fail('Job did not throw exception.');
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('FFmpeg failed to initialize');
    }

    $video->refresh();
    expect($video->status)->toBe('failed');
    expect($video->error_message)->toBe('FFmpeg failed to initialize');
});
