<?php

use App\Jobs\ExportWebtoonPackJob;
use App\Jobs\GenerateWebtoonStripJob;
use App\Livewire\ComicStudio;
use App\Livewire\WebtoonReader;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\WebtoonExportService;
use App\Services\WebtoonStripService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('GenerateWebtoonStripJob processes episode panels and stitches slices', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Infinite Mage',
        'premise' => 'A boy born in the slums discovers the true geometry of magic.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'The Circle',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Magic Testing Room',
    ]);

    // Create a panel image
    $img = imagecreatetruecolor(800, 600);
    $color = imagecolorallocate($img, 10, 50, 90);
    imagefilledrectangle($img, 0, 0, 800, 600, $color);

    $panelPath = 'stories/panels/mage_panel_1.png';
    $panelAbs = Storage::disk('public')->path($panelPath);
    File::ensureDirectoryExists(dirname($panelAbs));
    imagepng($img, $panelAbs);
    imagedestroy($img);

    SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Red light beamed from the magic circle.',
        'visual_prompt' => 'Magic circle in dark dungeon chamber',
        'image_path' => $panelPath,
    ]);

    $job = new GenerateWebtoonStripJob($episode);
    $job->handle(new WebtoonStripService);

    $episode->refresh();
    expect($episode->webtoon_status)->toBe('completed');
    expect($episode->webtoon_strip_path)->not->toBeEmpty();
    expect($episode->webtoon_slices)->toBeArray();
});

test('ExportWebtoonPackJob builds Webtoon Canvas and Tapas ZIP bundle', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Necromancer Legend',
        'premise' => 'Rising from the bottom to the absolute monarch.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Return of the King',
    ]);

    // Create 2 slice images
    $slices = [];
    for ($i = 1; $i <= 2; $i++) {
        $img = imagecreatetruecolor(800, 1000);
        $color = imagecolorallocate($img, 30 * $i, 20, 50);
        imagefilledrectangle($img, 0, 0, 800, 1000, $color);

        $sliceRel = "stories/webtoons/slices/slice_{$i}.jpg";
        $sliceAbs = Storage::disk('public')->path($sliceRel);
        File::ensureDirectoryExists(dirname($sliceAbs));
        imagejpeg($img, $sliceAbs);
        imagedestroy($img);

        $slices[] = $sliceRel;
    }

    $episode->update([
        'webtoon_slices' => $slices,
        'webtoon_status' => 'completed',
    ]);

    $job = new ExportWebtoonPackJob($episode);
    $job->handle(new WebtoonExportService);

    $episode->refresh();
    expect($episode->webtoon_export_pack_path)->not->toBeEmpty();

    $zipAbs = Storage::disk('public')->path($episode->webtoon_export_pack_path);
    expect(File::exists($zipAbs))->toBeTrue();
    expect(filesize($zipAbs))->toBeGreaterThan(0);
});

test('WebtoonReader Livewire component renders vertical strip slices and navigates', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Tower of Ascension',
        'premise' => 'Climbing 100 floors to claim the celestial throne.',
    ]);
    $episode1 = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Floor 1: Trial of Fire',
        'webtoon_slices' => ['stories/webtoons/slices/slice_1.jpg', 'stories/webtoons/slices/slice_2.jpg'],
        'webtoon_status' => 'completed',
    ]);
    $episode2 = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 2,
        'title' => 'Floor 2: Trial of Frost',
        'webtoon_slices' => ['stories/webtoons/slices/slice_3.jpg'],
        'webtoon_status' => 'completed',
    ]);

    Livewire::actingAs($user)
        ->test(WebtoonReader::class, ['episode' => $episode1])
        ->assertSee('Floor 1: Trial of Fire')
        ->assertSee('Tower of Ascension')
        ->assertSet('readerWidth', 800)
        ->call('setReaderWidth', 1000)
        ->assertSet('readerWidth', 1000)
        ->assertSet('bgTheme', 'dark')
        ->call('toggleBgTheme')
        ->assertSet('bgTheme', 'light')
        ->call('nextEpisode')
        ->assertRedirect(route('webtoon-reader', $episode2->id));
});

test('ComicStudio Livewire component dispatches Webtoon jobs and handles SFX', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Solo Ranker',
        'premise' => 'The lone player in a global survival deathmatch.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'First Blood',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Street Battle',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'A blinding shockwave ripped through the concrete floor.',
        'visual_prompt' => 'Shockwave ripples on cracked pavement',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('generateWebtoonStrip', $episode->id)
        ->call('exportWebtoonPack', $episode->id)
        ->call('openBubbleEditor', $board->id)
        ->set('sfxText', 'KABOOOM!')
        ->set('sfxStyle', 'action_speed')
        ->set('sfxRotation', -15)
        ->call('addSfxToBoard')
        ->assertCount('boardSfxTypography', 1)
        ->call('saveBoardConfig');

    Queue::assertPushed(GenerateWebtoonStripJob::class);
    Queue::assertPushed(ExportWebtoonPackJob::class);

    $board->refresh();
    expect($board->sfx_typography)->toBeArray();
    expect($board->sfx_typography[0]['text'])->toBe('KABOOOM!');
});
