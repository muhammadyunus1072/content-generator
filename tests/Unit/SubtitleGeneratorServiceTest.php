<?php

use App\Services\SubtitleGeneratorService;

beforeEach(function () {
    $this->service = new SubtitleGeneratorService;
});

test('convertVttToSrt converts Edge-TTS WebVTT to properly formatted SRT', function () {
    $vtt = <<<'VTT'
WEBVTT

00:00:00.050 --> 00:00:01.800
The ancient gates creaked open...

00:00:01.900 --> 00:00:03.450
revealing the lost dragon king.
VTT;

    $srt = $this->service->convertVttToSrt($vtt);

    expect($srt)->not->toBeEmpty()
        ->and($srt)->toContain("1\n00:00:00,050 --> 00:00:01,800\nTHE ANCIENT GATES CREAKED OPEN")
        ->and($srt)->toContain("2\n00:00:01,900 --> 00:00:03,450\nREVEALING THE LOST DRAGON KING");
});

test('convertVttToSrt handles cue numbers and styling tags in VTT', function () {
    $vtt = <<<'VTT'
WEBVTT

1
00:00:00.120 --> 00:00:02.500
<c.yellow>He said,</c> <v Narrator>"Do not open that door."</v>

2
00:00:02.600 --> 00:00:04.200
Too late.
VTT;

    $srt = $this->service->convertVttToSrt($vtt);

    expect($srt)->toContain('00:00:00,120 --> 00:00:02,500')
        ->and($srt)->not->toContain('<c.yellow>')
        ->and($srt)->not->toContain('<v Narrator>')
        ->and($srt)->toContain('HE SAID, "DO NOT OPEN THAT DOOR."');
});

test('convertVttToSrt returns empty string for blank input', function () {
    expect($this->service->convertVttToSrt(''))->toBe('')
        ->and($this->service->convertVttToSrt('   '))->toBe('')
        ->and($this->service->convertVttToSrt("WEBVTT\n\n"))->toBe('');
});

test('calculateTimings weights punctuation pauses appropriately', function () {
    // Chunk A has comma, Chunk B has ellipsis/period, Chunk C is short plain text
    $chunks = [
        'IN THE BEGINNING,',
        'THERE WAS DARKNESS...',
        'THEN LIGHT.',
    ];

    $totalDuration = 10.0;
    $cues = $this->service->calculateTimings($chunks, $totalDuration);

    expect($cues)->toHaveCount(3);
    expect($cues[0]['start'])->toBe(0.0);
    expect($cues[2]['end'])->toBe(10.0);

    // Each consecutive cue starts when previous ends
    expect($cues[1]['start'])->toBe($cues[0]['end']);
    expect($cues[2]['start'])->toBe($cues[1]['end']);

    // The duration of Chunk B (with dramatic ellipsis) should be longer than Chunk C (short plain)
    $durationB = $cues[1]['end'] - $cues[1]['start'];
    $durationC = $cues[2]['end'] - $cues[2]['start'];
    expect($durationB)->toBeGreaterThan($durationC);
});
