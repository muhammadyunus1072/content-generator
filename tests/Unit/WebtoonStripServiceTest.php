<?php

use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\WebtoonStripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('WebtoonStripService throws exception if episode has no panels', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Test Webtoon Story',
        'premise' => 'A hunter enters a rank S gate alone.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Prologue',
    ]);

    $service = new WebtoonStripService;
    $service->generateAndSliceEpisode($episode);
})->throws(Exception::class, 'Cannot generate webtoon: Episode has no ready panel images.');

test('WebtoonStripService stitches panels and creates slices with gutter safety', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Leveling in Dungeon',
        'premise' => 'Awakened hunters fight terrifying shadows.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Awakening',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Boss Room',
    ]);

    // Create two dummy panel images (800x600 each)
    $panelPaths = [];
    for ($i = 1; $i <= 3; $i++) {
        $img = imagecreatetruecolor(800, 600);
        $color = imagecolorallocate($img, 20 * $i, 30 * $i, 40 * $i);
        imagefilledrectangle($img, 0, 0, 800, 600, $color);

        $relPath = "stories/panels/panel_{$i}.png";
        $absPath = Storage::disk('public')->path($relPath);
        File::ensureDirectoryExists(dirname($absPath));
        imagepng($img, $absPath);
        imagedestroy($img);

        $board = SceneBoard::create([
            'episode_id' => $episode->id,
            'scene_id' => $scene->id,
            'board_number' => $i,
            'sequence_order' => $i,
            'narration_chunk' => "Narration for panel {$i}",
            'visual_prompt' => "Visual prompt for panel {$i}",
            'image_path' => $relPath,
            'gutter_spacing' => 60,
        ]);
    }

    $service = new WebtoonStripService;
    $result = $service->generateAndSliceEpisode($episode);

    expect($result)->toHaveKeys(['strip_path', 'slices']);
    expect($result['strip_path'])->not->toBeEmpty();
    expect($result['slices'])->not->toBeEmpty();

    // Verify full strip was created on disk
    $stripAbs = Storage::disk('public')->path($result['strip_path']);
    expect(File::exists($stripAbs))->toBeTrue();

    // Verify all slices were created and do not exceed 1280px in height
    foreach ($result['slices'] as $sliceRelative) {
        $sliceAbs = Storage::disk('public')->path($sliceRelative);
        expect(File::exists($sliceAbs))->toBeTrue();

        $size = getimagesize($sliceAbs);
        expect($size[0])->toBe(800);
        expect($size[1])->toBeLessThanOrEqual(1280);
    }

    // Refresh episode and verify model fields
    $episode->refresh();
    expect($episode->webtoon_status)->toBe('completed');
    expect($episode->webtoon_strip_path)->toBe($result['strip_path']);
    expect($episode->webtoon_slices)->toBeArray();
    expect(count($episode->webtoon_slices))->toBeGreaterThan(0);
});
