<?php

use App\Jobs\ExportComicArchiveJob;
use App\Jobs\GenerateMangaPagesJob;
use App\Livewire\ComicStudio;
use App\Livewire\MangaReader;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\ComicArchiveExportService;
use App\Services\MangaPageLayoutService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('GenerateMangaPagesJob generates multi-panel composite pages', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Rune Knight',
        'premise' => 'An enchanted knight fights ancient dragons.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'The Dragon Gate',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Cavern Entrance',
    ]);

    // Create a dummy panel image
    $img = imagecreatetruecolor(800, 600);
    $color = imagecolorallocate($img, 10, 40, 70);
    imagefilledrectangle($img, 0, 0, 800, 600, $color);

    $panelPath = 'stories/panels/manga_feature_1.png';
    $panelAbs = Storage::disk('public')->path($panelPath);
    File::ensureDirectoryExists(dirname($panelAbs));
    imagepng($img, $panelAbs);
    imagedestroy($img);

    SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The dragon roared with primal fury.',
        'visual_prompt' => 'Ancient dragon roaring at cavern entrance',
        'image_path' => $panelPath,
    ]);

    $job = new GenerateMangaPagesJob($episode, 'rtl');
    $job->handle(new MangaPageLayoutService);

    $episode->refresh();
    expect($episode->manga_status)->toBe('completed');
    expect($episode->manga_pages)->toBeArray();
    expect($episode->manga_pages)->toHaveCount(1);
});

test('ExportComicArchiveJob creates CBZ and PDF publishing artifacts', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Cyberpunk Shinobi',
        'premise' => 'Stealth operatives in neon-lit Neo-Tokyo.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Neon Infiltration',
    ]);

    // Create test page
    $img = imagecreatetruecolor(800, 1200);
    $color = imagecolorallocate($img, 30, 60, 90);
    imagefilledrectangle($img, 0, 0, 800, 1200, $color);

    $relPath = "stories/manga_pages/ep_{$episode->id}_p1.jpg";
    $absPath = Storage::disk('public')->path($relPath);
    File::ensureDirectoryExists(dirname($absPath));
    imagejpeg($img, $absPath);
    imagedestroy($img);

    $episode->update([
        'manga_pages' => [
            ['page_number' => 1, 'layout' => 'splash', 'image_path' => $relPath, 'panel_board_ids' => [1]],
        ],
        'manga_status' => 'completed',
    ]);

    $job = new ExportComicArchiveJob($episode, 'both');
    $job->handle(new ComicArchiveExportService);

    $episode->refresh();
    expect($episode->cbz_package_path)->not->toBeEmpty();
    expect($episode->pdf_package_path)->not->toBeEmpty();

    expect(File::exists(Storage::disk('public')->path($episode->cbz_package_path)))->toBeTrue();
    expect(File::exists(Storage::disk('public')->path($episode->pdf_package_path)))->toBeTrue();
});

test('MangaReader Livewire component navigates dual-page spreads and handles RTL flipping', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Grandmaster Alchemist',
        'premise' => 'Brewing potions that defy reality.',
    ]);
    $episode1 = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Cauldron 1',
        'manga_reading_direction' => 'rtl',
        'manga_pages' => [
            ['page_number' => 1, 'layout' => 'splash', 'image_path' => 'p1.jpg'],
            ['page_number' => 2, 'layout' => 'split', 'image_path' => 'p2.jpg'],
            ['page_number' => 3, 'layout' => 'split', 'image_path' => 'p3.jpg'],
        ],
        'manga_status' => 'completed',
    ]);
    $episode2 = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 2,
        'title' => 'Cauldron 2',
        'manga_reading_direction' => 'rtl',
        'manga_pages' => [
            ['page_number' => 1, 'layout' => 'splash', 'image_path' => 'p4.jpg'],
        ],
        'manga_status' => 'completed',
    ]);

    Livewire::actingAs($user)
        ->test(MangaReader::class, ['episode' => $episode1])
        ->assertSee('Cauldron 1')
        ->assertSee('Grandmaster Alchemist')
        ->assertSet('viewMode', 'spread')
        ->assertSet('readingDirection', 'rtl')
        ->call('toggleViewMode')
        ->assertSet('viewMode', 'single')
        ->call('toggleDirection')
        ->assertSet('readingDirection', 'ltr')
        ->call('setPage', 2)
        ->assertSet('currentPage', 2)
        ->call('nextPage')
        ->assertSet('currentPage', 3)
        ->call('previousPage')
        ->assertSet('currentPage', 2)
        ->call('nextEpisode')
        ->assertRedirect(route('manga-reader', $episode2->id));
});

test('ComicStudio Livewire component dispatches manga page and archive jobs', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Reborn Empress',
        'premise' => 'Palace intrigue and royal vengeance.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Coronation Banquet',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Throne Room',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The golden chalice was poisoned.',
        'visual_prompt' => 'Golden chalice glinting with venom',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('generateMangaPages', $episode->id, 'rtl')
        ->call('exportComicArchive', $episode->id, 'both')
        ->call('openBubbleEditor', $board->id)
        ->set('pageLayoutHint', 'splash')
        ->call('saveBoardConfig');

    Queue::assertPushed(GenerateMangaPagesJob::class);
    Queue::assertPushed(ExportComicArchiveJob::class);

    $board->refresh();
    expect($board->page_layout_hint)->toBe('splash');
});
