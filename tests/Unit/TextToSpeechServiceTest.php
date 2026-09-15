<?php

use App\Services\TextToSpeechService;

test('TextToSpeechService has configured default rate and pitch constants', function () {
    expect(TextToSpeechService::DEFAULT_RATE)->toBe('-15%');
    expect(TextToSpeechService::DEFAULT_PITCH)->toBe('-10Hz');
});

test('TextToSpeechService applyDramaticPunctuation replaces sentence periods with ellipses and normalizes commas', function () {
    $service = new TextToSpeechService;
    $text = 'The ancient castle stood tall on the hill, overlooking the mist. The wind was howling fiercely.';

    $result = $service->applyDramaticPunctuation($text);

    expect($result)->toContain('The ancient castle stood tall on the hill, overlooking the mist...');
    expect($result)->toContain('The wind was howling fiercely...');
});

test('TextToSpeechService applyDramaticPunctuation handles empty text and prevents duplicate ellipses', function () {
    $service = new TextToSpeechService;

    expect($service->applyDramaticPunctuation(''))->toBe('');
    expect($service->applyDramaticPunctuation('   '))->toBe('');

    $existingEllipses = 'He waited... and waited..... until the night ended.';
    $result = $service->applyDramaticPunctuation($existingEllipses);

    expect($result)->not->toContain('......');
    expect($result)->toContain('He waited...');
});
