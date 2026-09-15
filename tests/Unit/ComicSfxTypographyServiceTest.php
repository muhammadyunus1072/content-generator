<?php

use App\Services\ComicSfxTypographyService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('ComicSfxTypographyService returns original path if sfx list is empty', function () {
    $service = new ComicSfxTypographyService;
    $result = $service->renderSfxOnImage('some/path.png', []);
    expect($result)->toBe('some/path.png');
});

test('ComicSfxTypographyService renders manga sound effects onto image', function () {
    Storage::fake('public');

    // Create a blank test image
    $img = imagecreatetruecolor(800, 600);
    $bg = imagecolorallocate($img, 20, 20, 30);
    imagefilledrectangle($img, 0, 0, 800, 600, $bg);

    $testSourceRelative = 'test_sfx_source.png';
    $testSourceAbs = Storage::disk('public')->path($testSourceRelative);
    File::ensureDirectoryExists(dirname($testSourceAbs));
    imagepng($img, $testSourceAbs);
    imagedestroy($img);

    $sfxList = [
        [
            'text' => 'BOOOOM!!',
            'style' => 'action_speed',
            'color' => '#EF4444',
            'rotation' => -15,
            'x' => '50%',
            'y' => '30%',
            'size' => 64,
        ],
        [
            'text' => 'SLASH!',
            'style' => 'blade_slash',
            'color' => '#38BDF8',
            'rotation' => 20,
            'x' => '20%',
            'y' => '70%',
            'size' => 48,
        ],
    ];

    $service = new ComicSfxTypographyService;
    $outputRelative = 'test_sfx_output.png';
    $result = $service->renderSfxOnImage($testSourceRelative, $sfxList, $outputRelative);

    expect($result)->toBe($outputRelative);
    $outputAbs = Storage::disk('public')->path($outputRelative);
    expect(File::exists($outputAbs))->toBeTrue();
    expect(filesize($outputAbs))->toBeGreaterThan(0);
});

test('ComicSfxTypographyService suggests sfx based on action description keywords', function () {
    $service = new ComicSfxTypographyService;

    $blastSuggestion = $service->suggestSfxForAction('A colossal blast explosion echoed across the battlefield.');
    expect($blastSuggestion)->toHaveKeys(['text', 'style', 'color', 'rotation']);
    expect($blastSuggestion['text'])->toBe('BOOOOOM!!');
    expect($blastSuggestion['style'])->toBe('action_speed');

    $slashSuggestion = $service->suggestSfxForAction('Ren drew his katana and made a swift cut across the demon.');
    expect($slashSuggestion['text'])->toBe('SHHIIING!');
    expect($slashSuggestion['style'])->toBe('blade_slash');

    $whisperSuggestion = $service->suggestSfxForAction('A creepy shadow crept along the dark corridor.');
    expect($whisperSuggestion['style'])->toBe('eerie_whisper');
});
