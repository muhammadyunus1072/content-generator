<?php

use App\Models\ContentTitle;
use App\Models\Story;
use App\Models\User;
use App\Services\SeoGeneratorService;
use App\Services\ThumbnailGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('SeoGeneratorService returns structured video monetization metadata', function () {
    $contentTitle = ContentTitle::create([
        'user_id' => $this->user->id,
        'title' => 'Solo Shadow Monarch',
        'genre' => 'Action Fantasy',
        'visual_style' => 'Dark Fantasy Manhwa',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);

    $story = Story::create([
        'user_id' => $this->user->id,
        'content_title_id' => $contentTitle->id,
        'title' => 'Awakening of the Shadow Monarch',
        'premise' => 'An overlooked hunter obtains an ancient dark system and rises.',
        'visual_style' => 'Dark Fantasy Manhwa',
        'voice_name' => 'en-US-ChristopherNeural',
        'status' => 'completed',
    ]);

    $service = app(SeoGeneratorService::class);
    $seo = $service->generateForStory($story);

    expect($seo)->toBeArray()
        ->and($seo)->toHaveKeys(['title', 'description', 'tags', 'hashtags', 'hook_line'])
        ->and($seo['title'])->not->toBeEmpty()
        ->and($seo['description'])->not->toBeEmpty()
        ->and($seo['tags'])->toBeArray()->and(count($seo['tags']))->toBeGreaterThan(0)
        ->and($seo['hashtags'])->toBeArray()
        ->and($seo['hook_line'])->not->toBeEmpty();
});

test('ThumbnailGeneratorService produces a valid image file', function () {
    Storage::fake('public');

    // Create dummy image file in temp/public
    $tempDir = storage_path('app/public/test');
    File::ensureDirectoryExists($tempDir);
    $sourceImagePath = $tempDir.'/source.jpg';

    // Create a simple blank JPEG using GD if available, or write valid bytes
    if (extension_loaded('gd')) {
        $img = imagecreatetruecolor(640, 360);
        $bg = imagecolorallocate($img, 30, 30, 80);
        imagefilledrectangle($img, 0, 0, 640, 360, $bg);
        imagejpeg($img, $sourceImagePath);
        imagedestroy($img);
    } else {
        File::put($sourceImagePath, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
    }

    $service = app(ThumbnailGeneratorService::class);
    $resultRelPath = $service->generate(
        sourceImagePath: $sourceImagePath,
        overlayText: 'HE AWAKENED?!',
        niche: 'manhwa_recap',
        aspectRatio: '16:9'
    );

    expect($resultRelPath)->toContain('thumbnails/')
        ->and(Storage::disk('public')->exists($resultRelPath))->toBeTrue();
});
