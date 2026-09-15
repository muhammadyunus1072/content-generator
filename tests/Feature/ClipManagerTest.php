<?php

use App\Jobs\CutClipJob;
use App\Livewire\ClipManager;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

test('ClipManager loads selected video suggested clips', function () {
    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'completed',
    ]);

    $clip = Clip::create([
        'video_id' => $video->id,
        'title' => 'Moment A',
        'start_seconds' => 10.0,
        'end_seconds' => 30.0,
        'status' => 'pending',
    ]);

    $component = Livewire::actingAs($user)
        ->test(ClipManager::class)
        ->assertSet('videoId', null)
        ->dispatch('video-selected', $video->id)
        ->assertSet('videoId', $video->id);

    $editingClips = $component->get('editingClips');
    expect($editingClips[$clip->id]['title'])->toBe('Moment A');
    expect((float) $editingClips[$clip->id]['start_seconds'])->toBe(10.0);
    expect((float) $editingClips[$clip->id]['end_seconds'])->toBe(30.0);
});

test('ClipManager dispatches CutClipJob on manual generateClip trigger', function () {
    Bus::fake([CutClipJob::class]);

    $user = User::factory()->create();
    $video = Video::create([
        'user_id' => $user->id,
        'original_filename' => 'podcast.mp4',
        'video_path' => 'videos/podcast.mp4',
        'status' => 'completed',
    ]);

    $clip = Clip::create([
        'video_id' => $video->id,
        'title' => 'Moment A',
        'start_seconds' => 10.0,
        'end_seconds' => 30.0,
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(ClipManager::class)
        ->dispatch('video-selected', $video->id)
        ->set('editingClips.'.$clip->id.'.title', 'Updated Hook')
        ->set('editingClips.'.$clip->id.'.start_seconds', 15.5)
        ->set('editingClips.'.$clip->id.'.end_seconds', 45.2)
        ->call('generateClip', $clip->id);

    $clip->refresh();
    expect($clip->title)->toBe('Updated Hook');
    expect((float) $clip->start_seconds)->toBe(15.5);
    expect((float) $clip->end_seconds)->toBe(45.2);
    expect($clip->status)->toBe('processing');

    Bus::assertDispatched(CutClipJob::class, function ($job) use ($clip) {
        return $job->clip->id === $clip->id;
    });
});
