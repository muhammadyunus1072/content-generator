<?php

use App\Jobs\MergeVideoBuilderJob;
use App\Livewire\VideoBuilder;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoBuilderProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('video builder route requires authentication and loads component', function () {
    $response = $this->get(route('video-builder'));
    $response->assertOk();
    $response->assertSeeLivewire(VideoBuilder::class);
    $response->assertSee('Video Builder');
    $response->assertSee('Video Merger');
});

test('video builder initializes with default merger tab and 16:9 aspect ratio', function () {
    Livewire::test(VideoBuilder::class)
        ->assertSet('activeTab', 'merger')
        ->assertSet('aspectRatio', '16:9')
        ->assertSet('mainSourceType', 'file')
        ->assertSee('Main Video (Base Canvas)')
        ->assertSee('Live Canvas Preview');
});

test('can switch aspect ratio and source type', function () {
    Livewire::test(VideoBuilder::class)
        ->set('aspectRatio', '9:16')
        ->assertSet('aspectRatio', '9:16')
        ->set('mainSourceType', 'url')
        ->assertSet('mainSourceType', 'url');
});

test('can add and remove overlay asset and apply presets', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('sticker.png', 200, 200);

    Livewire::test(VideoBuilder::class)
        ->set('newAssetFile', $file)
        ->assertCount('assets', 1)
        ->call('applyPositionPreset', 0, 'top-left')
        ->assertSet('assets.0.x_percent', 4.0)
        ->assertSet('assets.0.y_percent', 4.0)
        ->call('applyPositionPreset', 0, 'center')
        ->call('removeAsset', 0)
        ->assertCount('assets', 0);
});

test('submitting a valid merge dispatches MergeVideoBuilderJob and creates project', function () {
    Storage::fake('local');
    Storage::fake('public');
    Bus::fake();

    $mainVideo = UploadedFile::fake()->create('main-sample.mp4', 2000, 'video/mp4');
    $overlayImage = UploadedFile::fake()->image('logo.png', 100, 100);

    Livewire::test(VideoBuilder::class)
        ->set('title', 'Test Holiday Merge')
        ->set('mainSourceType', 'file')
        ->set('mainVideoFile', $mainVideo)
        ->set('newAssetFile', $overlayImage)
        ->assertCount('assets', 1)
        ->call('submitMerge')
        ->assertHasNoErrors();

    expect(VideoBuilderProject::count())->toBe(1);
    $project = VideoBuilderProject::first();
    expect($project->title)->toBe('Test Holiday Merge')
        ->and($project->aspect_ratio)->toBe('16:9')
        ->and($project->status)->toBe('pending')
        ->and(count($project->assets_config))->toBe(1)
        ->and($project->assets_config[0]['lock_aspect_ratio'])->toBeTrue();

    Bus::assertDispatched(MergeVideoBuilderJob::class, function ($job) use ($project) {
        return $job->project->id === $project->id;
    });
});

test('overlay assets have lock_aspect_ratio true by default and can be toggled', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('banner.png', 300, 100);

    Livewire::test(VideoBuilder::class)
        ->set('newAssetFile', $file)
        ->assertSet('assets.0.lock_aspect_ratio', true)
        ->set('assets.0.lock_aspect_ratio', false)
        ->assertSet('assets.0.lock_aspect_ratio', false);
});

test('asset detects original dimensions and locks to original aspect ratio', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('wide.png', 400, 200);

    $test = Livewire::test(VideoBuilder::class)
        ->set('newAssetFile', $file);

    $asset = $test->get('assets.0');
    expect($asset['original_width'])->toBe(400)
        ->and($asset['original_height'])->toBe(200)
        ->and($asset['original_aspect_ratio'])->toBe(2.0)
        ->and($asset['lock_aspect_ratio'])->toBeTrue()
        ->and($asset['width_percent'])->toBe(25.0)
        ->and($asset['height_percent'])->toBe(22.2);

    $test->set('aspectRatio', '9:16');
    $updatedAsset = $test->get('assets.0');
    expect($updatedAsset['height_percent'])->toBe(7.0);
});

test('can submit video builder project with zero assets for direct download without errors', function () {
    Bus::fake();

    Livewire::test(VideoBuilder::class)
        ->set('title', 'Direct URL Video')
        ->set('mainSourceType', 'url')
        ->set('mainVideoUrl', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->set('saveToLibrary', true)
        ->assertCount('assets', 0)
        ->call('submitMerge')
        ->assertHasNoErrors();

    expect(VideoBuilderProject::count())->toBe(1);
    $project = VideoBuilderProject::first();
    expect($project->title)->toBe('Direct URL Video')
        ->and($project->main_source_type)->toBe('url')
        ->and($project->assets_config)->toBe([])
        ->and($project->save_to_library)->toBeTrue()
        ->and($project->status)->toBe('pending');

    Bus::assertDispatched(MergeVideoBuilderJob::class);
});

test('MergeVideoBuilderJob performs direct copy and registers in library when assets are empty', function () {
    Storage::disk('public')->put('videos/source.mp4', 'dummy-video-content');

    $project = VideoBuilderProject::create([
        'user_id' => $this->user->id,
        'title' => 'Direct Passthrough Project',
        'aspect_ratio' => '16:9',
        'main_source_type' => 'file',
        'main_video_path' => 'videos/source.mp4',
        'assets_config' => [],
        'save_to_library' => true,
        'status' => 'pending',
    ]);

    $job = new MergeVideoBuilderJob($project);
    app()->call([$job, 'handle']);

    $project->refresh();
    expect($project->status)->toBe('completed')
        ->and($project->output_video_path)->not->toBeNull()
        ->and($project->saved_video_id)->not->toBeNull();

    $savedVideo = Video::find($project->saved_video_id);
    expect($savedVideo)->not->toBeNull()
        ->and($savedVideo->user_id)->toBe($this->user->id)
        ->and($savedVideo->video_path)->toBe($project->output_video_path)
        ->and($savedVideo->processing_mode)->toBe('video_builder')
        ->and($savedVideo->status)->toBe('completed');
});

test('saveProjectToLibrary manually links completed project to video library', function () {
    Storage::disk('public')->put('video_builder/output.mp4', 'output-video-content');

    $project = VideoBuilderProject::create([
        'user_id' => $this->user->id,
        'title' => 'Manual Save Project',
        'aspect_ratio' => '16:9',
        'main_source_type' => 'file',
        'main_video_path' => 'videos/source.mp4',
        'output_video_path' => 'video_builder/output.mp4',
        'assets_config' => [],
        'save_to_library' => false,
        'saved_video_id' => null,
        'status' => 'completed',
    ]);

    Livewire::test(VideoBuilder::class)
        ->call('saveProjectToLibrary', $project->id);

    $project->refresh();
    expect($project->save_to_library)->toBeTrue()
        ->and($project->saved_video_id)->not->toBeNull();

    $savedVideo = Video::find($project->saved_video_id);
    expect($savedVideo)->not->toBeNull()
        ->and($savedVideo->user_id)->toBe($this->user->id)
        ->and($savedVideo->video_path)->toBe('video_builder/output.mp4')
        ->and($savedVideo->processing_mode)->toBe('video_builder');
});

test('deleteProject cleans up project and associated video record from library', function () {
    $video = Video::create([
        'user_id' => $this->user->id,
        'title' => 'Temporary Video',
        'original_filename' => 'temp.mp4',
        'video_path' => 'video_builder/temp.mp4',
        'status' => 'completed',
        'processing_mode' => 'video_builder',
    ]);

    $project = VideoBuilderProject::create([
        'user_id' => $this->user->id,
        'title' => 'To Delete Project',
        'aspect_ratio' => '16:9',
        'main_source_type' => 'file',
        'output_video_path' => 'video_builder/temp.mp4',
        'assets_config' => [],
        'save_to_library' => true,
        'saved_video_id' => $video->id,
        'status' => 'completed',
    ]);

    Livewire::test(VideoBuilder::class)
        ->call('deleteProject', $project->id);

    expect(VideoBuilderProject::find($project->id))->toBeNull()
        ->and(Video::find($video->id))->toBeNull();
});
