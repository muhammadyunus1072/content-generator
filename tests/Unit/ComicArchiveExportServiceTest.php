<?php

use App\Models\Episode;
use App\Models\Story;
use App\Models\User;
use App\Services\ComicArchiveExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

uses(TestCase::class, RefreshDatabase::class);

test('ComicArchiveExportService creates CBZ archive with ComicInfo.xml and page images', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Kingdom of Shadows',
        'premise' => 'An exiled prince reclaims his birthright.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'The Fall of the Capital',
        'manga_reading_direction' => 'rtl',
    ]);

    // Create 2 test page image files
    $pages = [];
    for ($i = 1; $i <= 2; $i++) {
        $img = imagecreatetruecolor(800, 1200);
        $color = imagecolorallocate($img, 20 * $i, 20, 20);
        imagefilledrectangle($img, 0, 0, 800, 1200, $color);

        $relPath = "stories/manga_pages/episode_{$episode->id}/page_{$i}.jpg";
        $absPath = Storage::disk('public')->path($relPath);
        File::ensureDirectoryExists(dirname($absPath));
        imagejpeg($img, $absPath);
        imagedestroy($img);

        $pages[] = [
            'page_number' => $i,
            'layout' => 'splash',
            'image_path' => $relPath,
            'panel_board_ids' => [$i],
        ];
    }

    $episode->update([
        'manga_pages' => $pages,
        'manga_status' => 'completed',
    ]);

    $service = new ComicArchiveExportService;
    $cbzRelative = $service->exportCbzArchive($episode);

    expect($cbzRelative)->toEndWith('.cbz');
    $cbzAbs = Storage::disk('public')->path($cbzRelative);
    expect(File::exists($cbzAbs))->toBeTrue();

    // Verify ZIP contents using ZipArchive
    $zip = new ZipArchive;
    expect($zip->open($cbzAbs))->toBeTrue();
    expect($zip->locateName('ComicInfo.xml'))->not->toBeFalse();
    expect($zip->locateName('page_001.jpg'))->not->toBeFalse();
    expect($zip->locateName('page_002.jpg'))->not->toBeFalse();

    // Read and verify ComicInfo.xml metadata
    $xmlContent = $zip->getFromName('ComicInfo.xml');
    expect($xmlContent)->toContain('<Title>The Fall of the Capital</Title>');
    expect($xmlContent)->toContain('<Series>Kingdom of Shadows</Series>');
    expect($xmlContent)->toContain('<Manga>YesAndRightToLeft</Manga>');
    expect($xmlContent)->toContain('<PageCount>2</PageCount>');

    $zip->close();
});

test('ComicArchiveExportService compiles valid multi-page PDF document', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Valkyrie Chronicles',
        'premise' => 'Wings of steel in a world of brass.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Flight 01',
    ]);

    // Create 1 test page image file
    $img = imagecreatetruecolor(800, 1200);
    $color = imagecolorallocate($img, 50, 100, 150);
    imagefilledrectangle($img, 0, 0, 800, 1200, $color);

    $relPath = "stories/manga_pages/episode_{$episode->id}/page_test.jpg";
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

    $service = new ComicArchiveExportService;
    $pdfRelative = $service->exportPdfDocument($episode);

    expect($pdfRelative)->toEndWith('.pdf');
    $pdfAbs = Storage::disk('public')->path($pdfRelative);
    expect(File::exists($pdfAbs))->toBeTrue();

    $pdfContent = File::get($pdfAbs);
    expect($pdfContent)->toStartWith('%PDF-1.4');
    expect($pdfContent)->toContain('/Type /Catalog');
    expect($pdfContent)->toContain('/Type /Pages');
    expect($pdfContent)->toContain('/Filter /DCTDecode');
    expect($pdfContent)->toEndWith('%%EOF');
});
