<?php

use App\Livewire\AudioBuilder;
use App\Models\AudioGeneration;
use App\Models\User;
use App\Services\TextToSpeechService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('audio builder route requires authentication and loads component', function () {
    $response = $this->get(route('audio-builder'));
    $response->assertOk();
    $response->assertSeeLivewire(AudioBuilder::class);
    $response->assertSee('AI Audio Builder & Voice Studio');
    $response->assertSee('Script / Narration Text');
});

test('audio builder initializes with default edge-tts provider and voice', function () {
    Livewire::test(AudioBuilder::class)
        ->assertSet('provider', 'edge_tts')
        ->assertSet('voice', 'id-ID-ArdiNeural')
        ->assertSet('rate', '-15%')
        ->assertSet('pitch', '-10Hz')
        ->assertSet('applyDramaticPause', true)
        ->assertSee('Edge-TTS (Python)');
});

test('switching provider to docker_tts updates defaults and shows docker controls', function () {
    Livewire::test(AudioBuilder::class)
        ->set('provider', 'docker_tts')
        ->assertSet('voice', 'af_heart')
        ->assertSet('activeVoiceCategory', 'Docker Kokoro-82M')
        ->assertSet('dockerBaseUrl', 'http://localhost:8880')
        ->set('dockerProtocol', 'cog')
        ->assertSet('dockerBaseUrl', 'http://localhost:5000');
});

test('quick sample buttons populate text and preset rates', function () {
    Livewire::test(AudioBuilder::class)
        ->call('applySample', 'anime')
        ->assertSet('voice', 'ja-JP-NanamiNeural')
        ->assertSet('rate', '+0%')
        ->assertSet('pitch', '+5Hz')
        ->call('applySample', 'documentary')
        ->assertSet('voice', 'id-ID-ArdiNeural')
        ->assertSet('rate', '-15%')
        ->assertSet('pitch', '-10Hz');
});

test('vtt parser converts WebVTT text into accurate structured cue cards', function () {
    $sampleVtt = "WEBVTT\n\n1\n00:00:00.500 --> 00:00:02.800\nDi sebuah desa terpencil yang damai\n\n2\n00:00:03.000 --> 00:00:05.450\nterdengar suara gemercik air sungai jernih";

    $cues = TextToSpeechService::parseVttToCues($sampleVtt);

    expect($cues)->toHaveCount(2);
    expect($cues[0]['start'])->toBe(0.5);
    expect($cues[0]['end'])->toBe(2.8);
    expect($cues[0]['text'])->toBe('Di sebuah desa terpencil yang damai');
    expect($cues[0]['start_formatted'])->toBe('00:00.50');
    expect($cues[0]['end_formatted'])->toBe('00:02.80');

    expect($cues[1]['start'])->toBe(3.0);
    expect($cues[1]['end'])->toBe(5.45);
    expect($cues[1]['text'])->toBe('terdengar suara gemercik air sungai jernih');
});

test('convertVttToSrt converts WebVTT timestamps to standard SubRip comma format', function () {
    $sampleVtt = "WEBVTT\n\n00:00:01.250 --> 00:00:04.750\nSatelit mengorbit di luar angkasa";

    $srt = TextToSpeechService::convertVttToSrt($sampleVtt);

    expect($srt)->toContain('00:00:01,250 --> 00:00:04,750');
    expect($srt)->toContain('Satelit mengorbit di luar angkasa');
});

test('generating audio persists record in audio_generations table and creates files', function () {
    Storage::fake('public');

    // Mock TextToSpeechService synthesize
    $mockTts = Mockery::mock(TextToSpeechService::class);
    $mockTts->shouldReceive('synthesize')
        ->once()
        ->andReturnUsing(function ($text, $audioPath, $subtitlePath) {
            File::ensureDirectoryExists(dirname($audioPath));
            File::put($audioPath, 'fake-mp3-audio-bytes');
            if ($subtitlePath) {
                File::ensureDirectoryExists(dirname($subtitlePath));
                File::put($subtitlePath, "WEBVTT\n\n00:00:00.000 --> 00:00:03.500\nNaskah pengujian voice acting.");
            }

            return [
                'audio_path' => $audioPath,
                'subtitle_path' => $subtitlePath,
                'duration_seconds' => 3.5,
            ];
        });

    app()->instance(TextToSpeechService::class, $mockTts);

    Livewire::test(AudioBuilder::class)
        ->set('text', 'Naskah pengujian voice acting.')
        ->set('title', 'Karakter Naya')
        ->call('generateAudio')
        ->assertHasNoErrors();

    $generation = AudioGeneration::first();
    expect($generation)->not->toBeNull();
    expect($generation->title)->toBe('Karakter Naya');
    expect($generation->duration_seconds)->toBe(3.5);
    expect($generation->provider)->toBe('edge_tts');
    expect($generation->subtitle_cues)->toHaveCount(1);
    expect($generation->subtitle_cues[0]['text'])->toBe('Naskah pengujian voice acting.');
});

test('synthesizeWithDockerOpenAi posts to /v1/audio/speech endpoint and writes audio', function () {
    Http::fake([
        'http://localhost:8880/v1/audio/speech' => Http::response('fake-kokoro-mp3-binary', 200),
    ]);

    $tempAudio = storage_path('app/temp/test_docker_audio_'.uniqid().'.mp3');
    File::ensureDirectoryExists(dirname($tempAudio));

    $service = new TextToSpeechService;
    $res = $service->synthesizeWithDockerOpenAi('Hello world', $tempAudio, 'af_heart', 'http://localhost:8880');

    expect(File::exists($tempAudio))->toBeTrue();
    expect(File::get($tempAudio))->toBe('fake-kokoro-mp3-binary');
    expect($res['audio_path'])->toBe($tempAudio);

    if (File::exists($tempAudio)) {
        File::delete($tempAudio);
    }
});

test('deleting audio removes record and storage files', function () {
    Storage::fake('public');

    Storage::disk('public')->put('audio_builder/test.mp3', 'dummy-audio');
    Storage::disk('public')->put('audio_builder/test.vtt', 'dummy-vtt');

    $gen = AudioGeneration::create([
        'user_id' => $this->user->id,
        'title' => 'Test Delete',
        'text' => 'Sample text',
        'provider' => 'edge_tts',
        'voice' => 'id-ID-ArdiNeural',
        'audio_path' => 'audio_builder/test.mp3',
        'subtitle_path' => 'audio_builder/test.vtt',
        'duration_seconds' => 2.0,
    ]);

    expect(AudioGeneration::count())->toBe(1);

    Livewire::test(AudioBuilder::class)
        ->call('deleteAudio', $gen->id);

    expect(AudioGeneration::count())->toBe(0);
    expect(Storage::disk('public')->exists('audio_builder/test.mp3'))->toBeFalse();
    expect(Storage::disk('public')->exists('audio_builder/test.vtt'))->toBeFalse();
});
