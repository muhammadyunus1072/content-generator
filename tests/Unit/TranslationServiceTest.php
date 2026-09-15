<?php

use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

test('TranslationService returns original text for empty input', function () {
    $service = new TranslationService;

    expect($service->translate('', 'id'))->toBe('');
    expect($service->translate('   ', 'id'))->toBe('');
});

test('TranslationService returns original text when source and target are identical', function () {
    $service = new TranslationService;
    $text = 'Hello world, this is a test.';

    expect($service->translate($text, 'en', 'en'))->toBe($text);
});

test('TranslationService provides supported languages and grouped voices', function () {
    $service = new TranslationService;

    $languages = $service->getSupportedLanguages();
    expect($languages)->toHaveKeys(['auto', 'en', 'id', 'ja', 'ko', 'es', 'fr', 'de']);

    expect($service->getDefaultVoiceForLanguage('id'))->toBe('id-ID-ArdiNeural');
    expect($service->getDefaultVoiceForLanguage('ja'))->toBe('ja-JP-NanamiNeural');
    expect($service->getDefaultVoiceForLanguage('en'))->toBe('en-US-ChristopherNeural');

    $voices = $service->getGroupedVoices();
    expect($voices)->toHaveKeys(['en', 'id', 'ja']);
});

test('TranslationService sanitizes HTML entities to prevent 403 Forbidden errors', function () {
    $service = new TranslationService;
    $dirtyHtml = '<p>Hello &amp; welcome to &quot;Storytelling&quot;&nbsp;world!</p>';

    $cleaned = $service->sanitizeInput($dirtyHtml);

    expect($cleaned)->toBe('Hello & welcome to "Storytelling" world!');
    expect($cleaned)->not->toContain('<p>');
    expect($cleaned)->not->toContain('&amp;');
});

test('TranslationService splits text longer than MAX_CHUNK_LENGTH into safe pieces to prevent 413 Entity Too Large', function () {
    $service = new TranslationService;

    // Create a 8,000 character string composed of sentences
    $sentence = 'The brave warrior traveled through the dark ancient enchanted forest. ';
    $longText = str_repeat($sentence, 120); // ~8400 characters

    $chunks = $service->splitTextIntoSafeChunks($longText, 3500);

    expect(count($chunks))->toBeGreaterThanOrEqual(3);
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk, 'UTF-8'))->toBeLessThanOrEqual(3500);
    }
});

test('TranslationService caches translations to prevent 429 Too Many Requests and IP bans', function () {
    Cache::flush();
    $service = new TranslationService;
    $text = 'The ancient artifact glowed with a mysterious blue light.';

    // Pre-populate cache
    $cacheKey = 'google_trans_'.md5("auto_id_{$text}");
    Cache::put($cacheKey, 'Artefak kuno bersinar dengan cahaya biru misterius.', 604800);

    // Calling translate should return cached translation without making HTTP call
    $result = $service->translate($text, 'id');
    expect($result)->toBe('Artefak kuno bersinar dengan cahaya biru misterius.');
});
