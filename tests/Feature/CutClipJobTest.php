<?php

use App\Jobs\CutClipJob;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

test('CutClipJob trims parent video and saves clip path', function () {
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'completed',
    ]);

    // Place a dummy file in the fake local storage
    Storage::disk('local')->put('videos/podcast.mp4', 'dummy video content');

    $clip = Clip::create([
        'video_id' => $video->id,
        'title' => 'Viral Moment',
        'start_seconds' => 10.00,
        'end_seconds' => 30.00,
        'status' => 'pending',
    ]);

    // Mock FFMpeg chain
    $exporterMock = Mockery::mock();
    $exporterMock->shouldReceive('toDisk')->once()->with('public')->andReturnSelf();
    $exporterMock->shouldReceive('inFormat')
        ->once()
        ->with(Mockery::on(function ($format) {
            return $format instanceof X264
                && $format->getAudioCodec() === 'aac'
                && $format->getVideoCodec() === 'libx264'
                && $format->getPasses() === 1;
        }))
        ->andReturnSelf();
    $exporterMock->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($closure) {
            $filtersMock = Mockery::mock();
            $filtersMock->shouldReceive('clip')
                ->once()
                ->with(
                    Mockery::on(fn ($t) => (float) $t->toSeconds() === 10.0),
                    Mockery::on(fn ($t) => (float) $t->toSeconds() === 20.0) // duration: 30 - 10 = 20
                );
            $closure($filtersMock);

            return true;
        }))
        ->andReturnSelf();

    $exporterMock->shouldReceive('addFilter')
        ->once()
        ->with(Mockery::on(function ($args) {
            return is_array($args)
                && $args[0] === '-filter_complex'
                && str_contains($args[1], 'split=')
                && str_contains($args[1], 'boxblur=')
                && str_contains($args[1], 'overlay=')
                && ! str_contains($args[1], 'drawbox=')
                && ! str_contains($args[1], 'drawtext=');
        }))
        ->andReturnSelf();

    $exporterMock->shouldReceive('save')->once()->andReturnTrue();

    $mediaMock = Mockery::mock();
    $mediaMock->shouldReceive('export')->once()->andReturn($exporterMock);

    $ffmpegMock = Mockery::mock();
    $ffmpegMock->shouldReceive('open')->once()->with('videos/podcast.mp4')->andReturn($mediaMock);

    FFMpeg::shouldReceive('fromDisk')->once()->with('local')->andReturn($ffmpegMock);

    // Execute job synchronously
    CutClipJob::dispatchSync($clip);

    $clip->refresh();
    expect($clip->status)->toBe('completed');
    expect($clip->clip_path)->not->toBeNull();
    expect($clip->clip_path)->toStartWith('clips/viral-moment_');
});

test('CutClipJob has appropriate timeout and tries configured for long video encoding', function () {
    $clip = new Clip;
    $job = new CutClipJob($clip);

    expect($job->timeout)->toBe(900);
    expect($job->tries)->toBe(1);
    expect((int) config('queue.connections.database.retry_after'))->toBeGreaterThanOrEqual(900);
});
