<?php

use App\Models\AiConnection;
use App\Services\Ai\AiModelManagerService;
use App\Services\Ai\Providers\CloudflareProvider;
use App\Services\Ai\Providers\FalAiProvider;
use App\Services\Ai\Providers\GeminiImageProvider;
use App\Services\Ai\Providers\HuggingFaceProvider;
use App\Services\Ai\Providers\PollinationsProvider;
use App\Services\Ai\Providers\ReplicateProvider;
use App\Services\Ai\Providers\StabilityAiProvider;
use App\Services\Ai\Providers\TogetherAiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

test('AiModelManager resolves correct strategy classes for all image providers', function () {
    $manager = app(AiModelManagerService::class);

    expect($manager->getImageProvider('cloudflare'))->toBeInstanceOf(CloudflareProvider::class)
        ->and($manager->getImageProvider('pollinations'))->toBeInstanceOf(PollinationsProvider::class)
        ->and($manager->getImageProvider('together_ai'))->toBeInstanceOf(TogetherAiProvider::class)
        ->and($manager->getImageProvider('fal_ai'))->toBeInstanceOf(FalAiProvider::class)
        ->and($manager->getImageProvider('replicate'))->toBeInstanceOf(ReplicateProvider::class)
        ->and($manager->getImageProvider('stability_ai'))->toBeInstanceOf(StabilityAiProvider::class)
        ->and($manager->getImageProvider('gemini_image'))->toBeInstanceOf(GeminiImageProvider::class)
        ->and($manager->getImageProvider('huggingface'))->toBeInstanceOf(HuggingFaceProvider::class);
});

test('preset templates include all 13 verified image presets', function () {
    $manager = app(AiModelManagerService::class);
    $presets = $manager->getPresetTemplates()['image'];

    expect(count($presets))->toBeGreaterThanOrEqual(13);

    $providers = array_column($presets, 'provider');
    expect($providers)->toContain('fal_ai')
        ->toContain('replicate')
        ->toContain('together_ai')
        ->toContain('stability_ai')
        ->toContain('gemini_image')
        ->toContain('huggingface')
        ->toContain('cloudflare')
        ->toContain('pollinations');
});

test('fal.ai provider generates image and downloads from returned URL', function () {
    $provider = app(FalAiProvider::class);
    $dummyImage = str_repeat('A', 2000);
    $tmpOutput = storage_path('app/temp/test_fal.png');

    Http::fake([
        'https://fal.run/fal-ai/flux-pro/v1.1' => Http::response([
            'images' => [
                ['url' => 'https://fal.media/files/test.png'],
            ],
        ], 200),
        'https://fal.media/files/test.png' => Http::response($dummyImage, 200),
    ]);

    $conn = new AiConnection([
        'provider' => 'fal_ai',
        'model_id' => 'fal-ai/flux-pro/v1.1',
        'base_url' => 'https://fal.run',
        'api_key' => 'fal-test-key',
    ]);

    $result = $provider->generate('Anime warrior in rain', $tmpOutput, $conn, '16:9');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();
    expect(File::get($tmpOutput))->toBe($dummyImage);

    // Clean up
    File::delete($tmpOutput);
});

test('replicate provider sends webhook and supports immediate wait response', function () {
    $provider = app(ReplicateProvider::class);
    $dummyImage = str_repeat('B', 2000);
    $tmpOutput = storage_path('app/temp/test_replicate.png');

    Http::fake([
        'https://api.replicate.com/v1/models/black-forest-labs/flux-1.1-pro/predictions' => Http::response([
            'id' => 'pred_test_12345',
            'status' => 'succeeded',
            'output' => ['https://replicate.delivery/pbxt/test.png'],
        ], 201),
        'https://replicate.delivery/pbxt/test.png' => Http::response($dummyImage, 200),
    ]);

    $conn = new AiConnection([
        'provider' => 'replicate',
        'model_id' => 'black-forest-labs/flux-1.1-pro',
        'api_key' => 'r8_testtoken123',
    ]);

    $result = $provider->generate('Cyberpunk anime city', $tmpOutput, $conn, '16:9');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();
    expect(File::get($tmpOutput))->toBe($dummyImage);

    // Verify cache mapping for webhook exists
    $cached = Cache::get('replicate_prediction_pred_test_12345');
    expect($cached)->not->toBeNull();
    expect($cached['output_path'])->toBe($tmpOutput);

    // Clean up
    File::delete($tmpOutput);
});

test('replicate webhook endpoint saves image and handles callback', function () {
    $dummyImage = str_repeat('C', 2000);
    $tmpOutput = storage_path('app/temp/test_webhook_saved.png');

    // Register cache mapping
    Cache::put('replicate_prediction_pred_webhook_999', [
        'output_path' => $tmpOutput,
    ], now()->addHour());

    Http::fake([
        'https://replicate.delivery/pbxt/webhook_out.png' => Http::response($dummyImage, 200),
    ]);

    $response = $this->postJson(route('api.webhooks.replicate'), [
        'id' => 'pred_webhook_999',
        'status' => 'succeeded',
        'output' => ['https://replicate.delivery/pbxt/webhook_out.png'],
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'prediction_id' => 'pred_webhook_999',
        ]);

    expect(File::exists($tmpOutput))->toBeTrue();
    expect(File::get($tmpOutput))->toBe($dummyImage);

    // Clean up
    File::delete($tmpOutput);
});

test('stability ai provider generates image and saves binary', function () {
    $provider = app(StabilityAiProvider::class);
    $dummyImage = str_repeat('D', 2000);
    $tmpOutput = storage_path('app/temp/test_stability.png');

    Http::fake([
        'https://api.stability.ai/v2beta/stable-image/generate/sd3' => Http::response($dummyImage, 200),
    ]);

    $conn = new AiConnection([
        'provider' => 'stability_ai',
        'model_id' => 'sd3.5-flash',
        'base_url' => 'https://api.stability.ai/v2beta',
        'api_key' => 'sk-stability-test',
    ]);

    $result = $provider->generate('Fantasy landscape', $tmpOutput, $conn, '16:9');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();

    // Clean up
    File::delete($tmpOutput);
});

test('huggingface provider generates image and saves binary', function () {
    $provider = app(HuggingFaceProvider::class);
    $dummyImage = str_repeat('E', 2000);
    $tmpOutput = storage_path('app/temp/test_hf.png');

    Http::fake([
        'https://api-inference.huggingface.co/models/black-forest-labs/FLUX.1-schnell' => Http::response($dummyImage, 200),
    ]);

    $conn = new AiConnection([
        'provider' => 'huggingface',
        'model_id' => 'black-forest-labs/FLUX.1-schnell',
        'api_key' => 'hf_test_token',
    ]);

    $result = $provider->generate('Anime character portrait', $tmpOutput, $conn, '1:1');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();

    // Clean up
    File::delete($tmpOutput);
});

test('cloudflare provider generates text-to-image and saves binary', function () {
    $provider = app(CloudflareProvider::class);
    $dummyImage = str_repeat('F', 2000);
    $tmpOutput = storage_path('app/temp/test_cf_t2i.png');

    Http::fake([
        'https://worker.example.com*' => Http::response($dummyImage, 200),
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-4b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    $result = $provider->generate('Cyberpunk flying car', $tmpOutput, $conn, '16:9');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();
    expect(File::get($tmpOutput))->toBe($dummyImage);

    // Clean up
    File::delete($tmpOutput);
});

test('cloudflare provider sends json request with image_b64, strength, and guidance for standard models image-to-image', function () {
    $provider = app(CloudflareProvider::class);
    $dummyImage = str_repeat('G', 2000);
    $dummyRefImage = str_repeat('R', 500);
    $tmpOutput = storage_path('app/temp/test_cf_i2i.png');
    $tmpRef = storage_path('app/temp/test_cf_ref.png');

    File::ensureDirectoryExists(dirname($tmpRef));
    File::put($tmpRef, $dummyRefImage);

    Http::fake([
        'https://worker.example.com*' => function (Request $request) use ($dummyImage, $dummyRefImage) {
            expect($request->header('X-AI-Schema')[0] ?? '')->toBe('standard_json');
            $data = $request->data();
            expect($data)->toHaveKey('image_b64')
                ->and($data['image_b64'])->toBe(base64_encode($dummyRefImage))
                ->and($data['strength'])->toBe(0.8)
                ->and($data['guidance'])->toBe(8.5);

            return Http::response($dummyImage, 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
        'options' => [
            'strength' => 0.8,
            'guidance' => 8.5,
        ],
    ]);

    $result = $provider->generate('Same character in desert armor', $tmpOutput, $conn, '9:16', [$tmpRef]);

    expect($result)->toBeTrue();
    expect($provider->supportsReferenceImages())->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();

    // Clean up
    File::delete($tmpOutput);
    File::delete($tmpRef);
});

test('cloudflare provider passes configured num_steps and high resolution dimensions for standard json models', function () {
    $provider = app(CloudflareProvider::class);
    $dummyImage = str_repeat('H', 2000);
    $tmpOutput = storage_path('app/temp/test_cf_steps.png');

    Http::fake([
        'https://worker.example.com*' => function (Request $request) use ($dummyImage) {
            $data = $request->data();
            expect($data['num_steps'])->toBe(25);
            expect($data['width'])->toBe(768);
            expect($data['height'])->toBe(1024);

            return Http::response($dummyImage, 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
        'options' => ['num_steps' => 25],
    ]);

    $result = $provider->generate('Crisp facial anatomy portrait', $tmpOutput, $conn, '9:16');

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();

    // Clean up
    File::delete($tmpOutput);
});

test('cloudflare provider strictly uses multipart schema for FLUX.2 Klein 9B', function () {
    $provider = app(CloudflareProvider::class);
    $dummyImage = str_repeat('K', 2000);
    $dummyRefImage = str_repeat('R', 500);
    $tmpOutput = storage_path('app/temp/test_cf_klein9b.png');
    $tmpRef = storage_path('app/temp/test_cf_klein9b_ref.png');

    File::ensureDirectoryExists(dirname($tmpRef));
    File::put($tmpRef, $dummyRefImage);

    Http::fake([
        'https://worker.example.com*' => function (Request $request) use ($dummyImage) {
            expect($request->isMultipart())->toBeTrue();
            expect($request->header('X-AI-Schema')[0] ?? '')->toBe('multipart');
            $body = $request->body();
            expect($body)->toContain('name="prompt"')
                ->and($body)->toContain('name="model"')
                ->and($body)->toContain('@cf/black-forest-labs/flux-2-klein-9b')
                ->and($body)->toContain('name="schema_type"')
                ->and($body)->toContain('multipart')
                ->and($body)->toContain('name="input_image_0"');

            return Http::response($dummyImage, 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
        'options' => [
            'strength' => 0.75,
            'guidance' => 7.0,
        ],
    ]);

    $result = $provider->generate('Epic cybernetic warrior with glowing visor', $tmpOutput, $conn, '16:9', [$tmpRef]);

    expect($result)->toBeTrue();
    expect(File::exists($tmpOutput))->toBeTrue();

    // Clean up
    File::delete($tmpOutput);
    File::delete($tmpRef);
});

test('cloudflare provider dynamically resolves schema types with connection options override', function () {
    $provider = app(CloudflareProvider::class);

    // Default dynamic mappings
    expect($provider->resolveSchemaType('@cf/black-forest-labs/flux-2-klein-9b'))->toBe('multipart')
        ->and($provider->resolveSchemaType('@cf/black-forest-labs/flux-2-klein-4b'))->toBe('multipart')
        ->and($provider->resolveSchemaType('@cf/bytedance/stable-diffusion-xl-lightning'))->toBe('standard_json');

    // Override via connection options schema_type
    $overrideConn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/custom/model',
        'options' => ['schema_type' => 'multipart'],
    ]);
    expect($provider->resolveSchemaType('@cf/custom/model', $overrideConn))->toBe('multipart');

    // Override via connection options api_schema
    $multipartSchemaConn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/custom/model',
        'options' => [
            'api_schema' => [
                'type' => 'object',
                'properties' => ['multipart' => ['type' => 'object']],
                'required' => ['multipart'],
            ],
        ],
    ]);
    expect($provider->resolveSchemaType('@cf/custom/model', $multipartSchemaConn))->toBe('multipart');

    $phoenixSchemaConn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/leonardo/phoenix-1.0',
        'options' => [
            'api_schema' => [
                'type' => 'object',
                'properties' => ['prompt' => ['type' => 'string'], 'guidance' => ['type' => 'number']],
                'required' => ['prompt'],
            ],
        ],
    ]);
    expect($provider->resolveSchemaType('@cf/leonardo/phoenix-1.0', $phoenixSchemaConn))->toBe('standard_json');

    $overrideStandard = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'options' => ['schema_type' => 'standard_json'],
    ]);
    expect($provider->resolveSchemaType('@cf/black-forest-labs/flux-2-klein-9b', $overrideStandard))->toBe('standard_json');
});

test('cloudflare provider testConnection sends multipart request for Klein 9B', function () {
    $provider = app(CloudflareProvider::class);

    Http::fake([
        'https://worker.example.com*' => function (Request $request) {
            expect($request->isMultipart())->toBeTrue();
            expect($request->header('X-AI-Schema')[0] ?? '')->toBe('multipart');
            $body = $request->body();
            expect($body)->toContain('name="model"')
                ->and($body)->toContain('@cf/black-forest-labs/flux-2-klein-9b')
                ->and($body)->toContain('name="schema_type"')
                ->and($body)->toContain('multipart');

            return Http::response('OK', 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    $res = $provider->testConnection($conn);

    expect($res['status'])->toBe('success')
        ->and($res['message'])->toContain('@cf/black-forest-labs/flux-2-klein-9b')
        ->and($res['message'])->toContain('multipart');
});

test('cloudflare provider testConnection falls back to JSON when remote worker returns 400', function () {
    $provider = app(CloudflareProvider::class);

    $attempt = 0;
    Http::fake([
        'https://worker.example.com*' => function (Request $request) use (&$attempt) {
            $attempt++;
            if ($attempt === 1) {
                // First attempt: multipart rejected by JSON-only worker
                expect($request->isMultipart())->toBeTrue();

                return Http::response(['error' => 'Bad Request: prompt is required'], 400);
            }

            // Second attempt: retried as JSON
            expect($request->isMultipart())->toBeFalse();
            $data = $request->data();
            expect($data['prompt'])->toBe('ping test')
                ->and($data['model'])->toBe('@cf/black-forest-labs/flux-2-klein-9b');

            return Http::response('OK', 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    $res = $provider->testConnection($conn);

    expect($res['status'])->toBe('success');
    expect($attempt)->toBe(2);
});

test('cloudflare provider normalizes model IDs with missing @ prefix', function () {
    $provider = app(CloudflareProvider::class);

    expect($provider->normalizeModelId('cf/black-forest-labs/flux-2-klein-9b'))
        ->toBe('@cf/black-forest-labs/flux-2-klein-9b')
        ->and($provider->normalizeModelId('@cf/black-forest-labs/flux-2-klein-9b'))
        ->toBe('@cf/black-forest-labs/flux-2-klein-9b')
        ->and($provider->normalizeModelId(''))
        ->toBe('@cf/bytedance/stable-diffusion-xl-lightning')
        ->and($provider->normalizeModelId(null))
        ->toBe('@cf/bytedance/stable-diffusion-xl-lightning');
});

test('presets include Cloudflare FLUX-2 Klein 9B with proper model identifier', function () {
    $manager = app(AiModelManagerService::class);
    $presets = collect($manager->getPresetTemplates()['image']);

    $flux9b = $presets->firstWhere('model_id', '@cf/black-forest-labs/flux-2-klein-9b');
    expect($flux9b)->not->toBeNull()
        ->and($flux9b['provider'])->toBe('cloudflare')
        ->and($flux9b['name'])->toContain('FLUX-2 Klein 9B');
});

test('cloudflare provider testConnection supports zero neuron ping mode with worker message', function () {
    $provider = app(CloudflareProvider::class);

    Http::fake([
        'https://worker.example.com*' => function (Request $request) {
            expect($request->header('X-Test-Connection')[0] ?? '')->toBe('true');
            expect(str_contains($request->url(), 'ping=true'))->toBeTrue();

            return Http::response([
                'status' => 'success',
                'message' => 'Cloudflare Worker AI online (@cf/black-forest-labs/flux-2-klein-9b [multipart]).',
                'model' => '@cf/black-forest-labs/flux-2-klein-9b',
                'schema' => 'multipart',
            ], 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    $res = $provider->testConnection($conn);

    expect($res['status'])->toBe('success');
    expect($res['message'])->toBe('Cloudflare Worker AI online (@cf/black-forest-labs/flux-2-klein-9b [multipart]).');
});

test('cloudflare provider throws clear exception when daily neuron quota is exceeded in testConnection', function () {
    $provider = app(CloudflareProvider::class);

    Http::fake([
        'https://worker.example.com*' => Http::response([
            'error' => 'Failed to generate image',
            'details' => '4006: you have used up your daily free allocation of 10,000 neurons, please upgrade to Cloudflare\'s Workers Paid plan if you would like to continue usage.',
        ], 500),
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    expect(fn () => $provider->testConnection($conn))
        ->toThrow(Exception::class, 'Cloudflare quota exceeded (4006)');
});

test('cloudflare provider throws clear exception when daily neuron quota is exceeded in generate', function () {
    $provider = app(CloudflareProvider::class);
    $tmpOutput = storage_path('app/temp/test_cf_quota.png');

    Http::fake([
        'https://worker.example.com*' => Http::response([
            'error' => 'Failed to generate image',
            'details' => '4006: you have used up your daily free allocation of 10,000 neurons, please upgrade to Cloudflare\'s Workers Paid plan if you would like to continue usage.',
        ], 500),
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    expect(fn () => $provider->generate('A testing prompt', $tmpOutput, $conn))
        ->toThrow(Exception::class, 'Cloudflare quota exceeded (4006)');
});

test('cloudflare provider maps different aspect ratios to correct dimensions in standard json models', function (string $ratio, int $expectedW, int $expectedH) {
    $provider = app(CloudflareProvider::class);
    $dummyImage = str_repeat('F', 2000);
    $tmpOutput = storage_path('app/temp/test_cf_ar_'.str_replace(':', '_', $ratio).'.png');

    Http::fake([
        'https://worker.example.com*' => function (Request $request) use ($dummyImage, $expectedW, $expectedH) {
            $data = $request->data();
            expect($data['width'])->toBe($expectedW)
                ->and($data['height'])->toBe($expectedH);

            return Http::response($dummyImage, 200);
        },
    ]);

    $conn = new AiConnection([
        'provider' => 'cloudflare',
        'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'cf-test-key',
    ]);

    $result = $provider->generate('Landscape test', $tmpOutput, $conn, $ratio);
    expect($result)->toBeTrue();
    File::delete($tmpOutput);
})->with([
    ['1:1', 1024, 1024],
    ['4:3', 1024, 768],
    ['3:4', 768, 1024],
    ['16:9', 1280, 720],
    ['9:16', 768, 1024],
]);
