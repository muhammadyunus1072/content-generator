<?php

use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MangaPageLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('MangaPageLayoutService clusters scene boards into appropriate page layouts', function () {
    $service = new MangaPageLayoutService;

    // Test 1 board -> splash
    $b1 = new SceneBoard(['board_number' => 1, 'page_layout_hint' => 'auto']);
    $clusters1 = $service->clusterBoardsIntoPages(collect([$b1]));
    expect($clusters1)->toHaveCount(1);
    expect($clusters1[0]['layout'])->toBe('splash');

    // Test 2 boards -> split_2_horizontal
    $b2 = new SceneBoard(['board_number' => 2, 'page_layout_hint' => 'auto']);
    $clusters2 = $service->clusterBoardsIntoPages(collect([$b1, $b2]));
    expect($clusters2)->toHaveCount(1);
    expect($clusters2[0]['layout'])->toBe('split_2_horizontal');

    // Test 3 boards -> layout_3_top_wide
    $b3 = new SceneBoard(['board_number' => 3, 'page_layout_hint' => 'auto']);
    $clusters3 = $service->clusterBoardsIntoPages(collect([$b1, $b2, $b3]));
    expect($clusters3)->toHaveCount(1);
    expect($clusters3[0]['layout'])->toBe('layout_3_top_wide');

    // Test 4 boards -> grid_4
    $b4 = new SceneBoard(['board_number' => 4, 'page_layout_hint' => 'auto']);
    $clusters4 = $service->clusterBoardsIntoPages(collect([$b1, $b2, $b3, $b4]));
    expect($clusters4)->toHaveCount(1);
    expect($clusters4[0]['layout'])->toBe('grid_4');

    // Test explicit splash hint
    $bSplash = new SceneBoard(['board_number' => 5, 'page_layout_hint' => 'splash']);
    $clustersSplash = $service->clusterBoardsIntoPages(collect([$bSplash, $b1]));
    expect($clustersSplash)->toHaveCount(2);
    expect($clustersSplash[0]['layout'])->toBe('splash');
});

test('MangaPageLayoutService calculates cell geometry for RTL manga vs LTR comic', function () {
    $service = new MangaPageLayoutService;

    $rtlCells = $service->calculateCellGeometry('grid_4', 'rtl', 4);
    $ltrCells = $service->calculateCellGeometry('grid_4', 'ltr', 4);

    expect($rtlCells)->toHaveCount(4);
    expect($ltrCells)->toHaveCount(4);

    // In RTL, the first panel (index 0) must be positioned on the RIGHT half of the page
    // In LTR, the first panel (index 0) must be positioned on the LEFT half of the page
    expect($rtlCells[0]['x'])->toBeGreaterThan($ltrCells[0]['x']);
    expect($ltrCells[0]['x'])->toBe(MangaPageLayoutService::BLEED_MARGIN);
});

test('MangaPageLayoutService generates 1600x2400 composite manga page on disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Shonen Vanguard',
        'premise' => 'An aspiring swordsman challenges the nine heavens.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'The Blade Awakening',
        'manga_reading_direction' => 'rtl',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Dojo duel',
    ]);

    // Create 3 dummy panel images
    for ($i = 1; $i <= 3; $i++) {
        $img = imagecreatetruecolor(800, 600);
        $color = imagecolorallocate($img, 10 * $i, 30 * $i, 60 * $i);
        imagefilledrectangle($img, 0, 0, 800, 600, $color);

        $relPath = "stories/panels/manga_test_{$i}.png";
        $absPath = Storage::disk('public')->path($relPath);
        File::ensureDirectoryExists(dirname($absPath));
        imagepng($img, $absPath);
        imagedestroy($img);

        SceneBoard::create([
            'episode_id' => $episode->id,
            'scene_id' => $scene->id,
            'board_number' => $i,
            'sequence_order' => $i,
            'narration_chunk' => "Combat action beat {$i}",
            'visual_prompt' => "Manga swordsman action panel {$i}",
            'image_path' => $relPath,
        ]);
    }

    $service = new MangaPageLayoutService;
    $pages = $service->generateMangaPages($episode, 'rtl');

    expect($pages)->not->toBeEmpty();
    expect($pages[0]['page_number'])->toBe(1);
    expect($pages[0]['layout'])->toBe('layout_3_top_wide');

    $pageAbsPath = Storage::disk('public')->path($pages[0]['image_path']);
    expect(File::exists($pageAbsPath))->toBeTrue();

    // Verify dimensions of composite page (1600 x 2400)
    $size = getimagesize($pageAbsPath);
    expect($size[0])->toBe(1600);
    expect($size[1])->toBe(2400);

    $episode->refresh();
    expect($episode->manga_status)->toBe('completed');
    expect($episode->manga_pages)->toBeArray();
});
