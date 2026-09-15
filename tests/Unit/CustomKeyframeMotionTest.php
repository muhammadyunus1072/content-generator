<?php

use App\Services\ComicSfxTypographyService;
use App\Services\MotionComicRendererService;
use App\Services\SpeechBubbleGeneratorService;

test('MotionComicRendererService builds fallback zoompan filter when keyframes are empty', function () {
    $renderer = app(MotionComicRendererService::class);

    $filter = $renderer->buildDynamicKeyframeFilter([], 1920, 1080, 150, 30);

    expect($filter)->toContain('zoompan=z=\'min(zoom+0.0014,1.20)\'');
    expect($filter)->toContain('s=1920x1080');
});

test('MotionComicRendererService compiles continuous interpolation zoompan filter from keyframes', function () {
    $renderer = app(MotionComicRendererService::class);

    $keyframes = [
        ['time_pct' => 0, 'zoom' => 1.0, 'x_pct' => 50, 'y_pct' => 50, 'shake' => 0],
        ['time_pct' => 50, 'zoom' => 1.4, 'x_pct' => 70, 'y_pct' => 30, 'shake' => 0],
        ['time_pct' => 100, 'zoom' => 1.8, 'x_pct' => 80, 'y_pct' => 20, 'shake' => 8],
    ];

    $filter = $renderer->buildDynamicKeyframeFilter($keyframes, 1920, 1080, 150, 30);

    // Should contain dynamic zoom expression and shake math
    expect($filter)->toContain('zoompan=z=\'min(1+on*');
    expect($filter)->toContain('(iw-iw/zoom)*');
    expect($filter)->toContain('+sin(2*PI*on/4)*8');
    expect($filter)->toContain('s=1920x1080:fps=30');
});

test('SpeechBubbleGeneratorService calculates coordinates using percentage overrides', function () {
    $bubbleService = app(SpeechBubbleGeneratorService::class);

    $method = new ReflectionMethod($bubbleService, 'calculateCoordinates');
    $method->setAccessible(true);

    // calculateCoordinates(int $w, int $h, int $bw, string $pos, ?float $xPct = null, ?float $yPct = null)
    // 1920 x 1080, bw=300, pos='top-right', xPct=50, yPct=30
    // x = (1920 * 50) / 100 = 960
    // y = (1080 * 30) / 100 = 324
    $coords = $method->invoke($bubbleService, 1920, 1080, 300, 'top-right', 50.0, 30.0);

    expect($coords)->toBeArray();
    expect($coords['x'])->toBe(960);
    expect($coords['y'])->toBe(324);
});

test('ComicSfxTypographyService calculates coordinates from percentage values', function () {
    $sfxService = app(ComicSfxTypographyService::class);

    // 1920 x 1080 image
    // x_pct = 25% => 1920 * 0.25 = 480
    // y_pct = 75% => 1080 * 0.75 = 810
    $sfxData = [
        'x_pct' => 25,
        'y_pct' => 75,
    ];

    $coords = $sfxService->resolveCoordinates(1920, 1080, $sfxData);

    expect($coords)->toBeArray();
    expect($coords['x'])->toBe(480);
    expect($coords['y'])->toBe(810);
});
