<?php

use App\Livewire\AiConfig;
use App\Models\AiConnection;
use App\Models\User;
use App\Services\Ai\AiModelManagerService;
use App\Services\Ai\Providers\DockerReplicateImageProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('docker replicate provider resolves from ai manager', function () {
    $manager = app(AiModelManagerService::class);
    $provider = $manager->getImageProvider('docker_cog');

    expect($provider)->toBeInstanceOf(DockerReplicateImageProvider::class);

    $aliasProvider = $manager->getImageProvider('docker_replicate');
    expect($aliasProvider)->toBeInstanceOf(DockerReplicateImageProvider::class);
});

test('docker replicate provider calculates 6gb vram safe dimensions', function () {
    $provider = new DockerReplicateImageProvider;

    expect($provider->calculateDimensions('1:1'))->toBe([512, 512]);
    expect($provider->calculateDimensions('16:9'))->toBe([640, 360]);
    expect($provider->calculateDimensions('9:16'))->toBe([360, 640]);
    expect($provider->calculateDimensions('4:3'))->toBe([576, 432]);
    expect($provider->calculateDimensions('3:4'))->toBe([432, 576]);
});

test('docker replicate provider generates image from base64 data uri', function () {
    $fakePng = base64_encode(str_repeat('PNG_TEST_BINARY_DATA_AT_LEAST_100_BYTES_LONG_FOR_VALID_IMAGE_CHECK_PADDING_DATA', 3));
    $dataUri = 'data:image/png;base64,'.$fakePng;

    Http::fake([
        'http://localhost:5000/predictions' => Http::response([
            'status' => 'succeeded',
            'output' => [$dataUri],
        ], 200),
    ]);

    $connection = new AiConnection([
        'name' => 'Local Docker Test',
        'category' => 'image',
        'provider' => 'docker_cog',
        'base_url' => 'http://localhost:5000',
        'options' => [
            'num_steps' => 20,
            'guidance' => 7.0,
        ],
    ]);

    $outputPath = storage_path('framework/testing/docker_test_out.png');
    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }

    $provider = new DockerReplicateImageProvider;
    $result = $provider->generate(
        prompt: 'a scenic anime mountain river',
        outputAbsPath: $outputPath,
        connection: $connection,
        aspectRatio: '16:9'
    );

    expect($result)->toBeTrue();
    expect(File::exists($outputPath))->toBeTrue();
    expect(File::get($outputPath))->toBe(base64_decode($fakePng));

    Http::assertSent(function (Request $request) {
        return $request->url() === 'http://localhost:5000/predictions'
            && $request['input']['width'] === 640
            && $request['input']['height'] === 360
            && $request['input']['guidance_scale'] == 7.0
            && $request['input']['num_inference_steps'] === 20;
    });

    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }
});

test('docker replicate provider generates image from url output', function () {
    $fakeImageBytes = str_repeat('IMAGE_BYTES_FROM_REMOTE_URL_CONTAINER_HOSTED_TEST_SUITE_PADDING', 4);

    Http::fake([
        'http://localhost:5000/predictions' => Http::response([
            'status' => 'succeeded',
            'output' => ['http://localhost:5000/static/generated.png'],
        ], 200),
        'http://localhost:5000/static/generated.png' => Http::response($fakeImageBytes, 200),
    ]);

    $connection = new AiConnection([
        'provider' => 'docker_cog',
        'base_url' => 'http://localhost:5000/predictions',
    ]);

    $outputPath = storage_path('framework/testing/docker_url_test.png');
    $provider = new DockerReplicateImageProvider;

    $result = $provider->generate(
        prompt: 'test prompt',
        outputAbsPath: $outputPath,
        connection: $connection,
        aspectRatio: '1:1'
    );

    expect($result)->toBeTrue();
    expect(File::exists($outputPath))->toBeTrue();
    expect(File::get($outputPath))->toBe($fakeImageBytes);

    if (File::exists($outputPath)) {
        File::delete($outputPath);
    }
});

test('docker replicate test connection succeeds when container is online', function () {
    Http::fake([
        'http://localhost:5000/openapi.json' => Http::response(['title' => 'Cog Model API'], 200),
    ]);

    $connection = new AiConnection([
        'name' => 'Local Docker Cog',
        'category' => 'image',
        'provider' => 'docker_cog',
        'base_url' => 'http://localhost:5000',
    ]);

    $provider = new DockerReplicateImageProvider;
    $result = $provider->testConnection($connection);

    expect($result['status'])->toBe('success');
    expect($result['message'])->toContain('Local Docker Cog container online');
});

test('docker replicate warmup inference runs 1 step test', function () {
    Http::fake([
        'http://localhost:5000/predictions' => Http::response([
            'status' => 'succeeded',
            'output' => ['data:image/png;base64,dGVzdA=='],
        ], 200),
    ]);

    $connection = new AiConnection([
        'name' => 'Local Docker Cog',
        'category' => 'image',
        'provider' => 'docker_cog',
        'base_url' => 'http://localhost:5000',
    ]);

    $provider = new DockerReplicateImageProvider;
    $result = $provider->warmupInference($connection);

    expect($result['status'])->toBe('success');
    expect($result['message'])->toContain('Docker GPU warm-up complete');

    Http::assertSent(function (Request $request) {
        return $request['input']['num_inference_steps'] === 1
            && $request['input']['width'] === 256;
    });
});

test('ai config preset applies docker rtx 3050 safe settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiConfig::class)
        ->set('activeCategory', 'image')
        ->call('applyPreset', 'Local Docker (Replicate / Cog) - RTX 3050 Safe')
        ->assertSet('formName', 'Local Docker (Replicate / Cog) - RTX 3050 Safe')
        ->assertSet('formProvider', 'docker_cog')
        ->assertSet('formBaseUrl', 'http://localhost:5000/predictions')
        ->assertSet('formNumSteps', 20)
        ->assertSet('formGuidance', 7.0);
});

test('ai manager generates text via docker cog llm', function () {
    Http::fake([
        'http://localhost:5000/predictions' => Http::response([
            'status' => 'succeeded',
            'output' => ['Once upon a time in a cyberpunk Tokyo...'],
        ], 200),
    ]);

    $connection = new AiConnection([
        'name' => 'Docker LLM',
        'category' => 'text',
        'provider' => 'docker_cog',
        'base_url' => 'http://localhost:5000',
    ]);

    $manager = app(AiModelManagerService::class);
    $text = $manager->generateText('Tell a story', $connection);

    expect($text)->toBe('Once upon a time in a cyberpunk Tokyo...');
});
