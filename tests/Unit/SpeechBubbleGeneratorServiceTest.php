<?php

use App\Services\SpeechBubbleGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('SpeechBubbleGeneratorService returns original path if bubbles array is empty', function () {
    $service = new SpeechBubbleGeneratorService;
    $result = $service->renderBubblesOnImage('some/path.png', []);
    expect($result)->toBe('some/path.png');
});

test('SpeechBubbleGeneratorService creates panel image with manga bubbles using GD', function () {
    Storage::fake('public');

    // Create a blank test source image
    $img = imagecreatetruecolor(800, 600);
    $bg = imagecolorallocate($img, 40, 40, 50);
    imagefilledrectangle($img, 0, 0, 800, 600, $bg);

    $testSourceRelative = 'test_source_panel.png';
    $testSourceAbs = Storage::disk('public')->path($testSourceRelative);
    File::ensureDirectoryExists(dirname($testSourceAbs));
    imagepng($img, $testSourceAbs);
    imagedestroy($img);

    $bubbles = [
        [
            'speaker' => 'Ren',
            'text' => 'The shadows are awakening!',
            'style' => 'manga',
            'position' => 'top-right',
            'emotion' => 'action',
        ],
        [
            'speaker' => 'Narrator',
            'text' => 'A terrifying silence swept the dungeon.',
            'style' => 'caption_box',
            'position' => 'bottom',
        ],
    ];

    $service = new SpeechBubbleGeneratorService;
    $outputRelative = 'stories/comic_panels/test_output.png';
    $result = $service->renderBubblesOnImage($testSourceRelative, $bubbles, $outputRelative);

    expect($result)->not->toBeEmpty();
    $outputAbs = Storage::disk('public')->path($outputRelative);
    expect(File::exists($outputAbs))->toBeTrue();
    expect(filesize($outputAbs))->toBeGreaterThan(0);
});
