<?php

use App\Livewire\AiConfig;
use App\Models\AiConnection;
use App\Models\User;
use App\Services\Ai\AiModelManagerService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('ai config page is protected by auth middleware', function () {
    $this->get(route('ai-config'))
        ->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('ai-config'))
        ->assertOk()
        ->assertSeeLivewire(AiConfig::class);
});

test('user can create a new AI connection with encrypted API key and set as default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiConfig::class)
        ->set('formName', 'Together AI - FLUX.1 Schnell')
        ->set('formCategory', 'image')
        ->set('formProvider', 'together_ai')
        ->set('formModelId', 'black-forest-labs/FLUX.1-schnell')
        ->set('formApiKey', 'together-secret-token-12345')
        ->set('formBaseUrl', 'https://api.together.xyz/v1')
        ->set('formIsDefault', true)
        ->call('saveConnection')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ai_connections', [
        'name' => 'Together AI - FLUX.1 Schnell',
        'category' => 'image',
        'provider' => 'together_ai',
        'model_id' => 'black-forest-labs/FLUX.1-schnell',
        'is_default' => true,
    ]);

    $conn = AiConnection::where('name', 'Together AI - FLUX.1 Schnell')->first();
    // Verify encryption
    expect($conn->api_key)->toBe('together-secret-token-12345');
    expect($conn->masked_api_key)->toBe('toge••••••••2345');
});

test('applying quick presets populates form correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiConfig::class)
        ->set('activeCategory', 'text')
        ->call('applyPreset', 'Groq - Llama 3.3 70B Versatile')
        ->assertSet('formName', 'Groq - Llama 3.3 70B Versatile')
        ->assertSet('formProvider', 'groq')
        ->assertSet('formModelId', 'llama-3.3-70b-versatile')
        ->assertSet('showModal', true);
});

test('testing an AI connection sends ping and records latency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Http::fake([
        'https://api.together.xyz/v1/images/generations' => Http::response([
            'data' => [['b64_json' => base64_encode('fake-image-bytes')]],
        ], 200),
    ]);

    $conn = AiConnection::create([
        'user_id' => $user->id,
        'name' => 'Together AI Image Test',
        'category' => 'image',
        'provider' => 'together_ai',
        'model_id' => 'black-forest-labs/FLUX.1-schnell',
        'api_key' => 'fake-key',
    ]);

    Livewire::test(AiConfig::class)
        ->call('testConnection', $conn->id)
        ->assertHasNoErrors();

    $conn->refresh();
    expect($conn->last_test_status)->toBe('success');
    expect($conn->last_tested_at)->not->toBeNull();
});

test('AiModelManagerService resolves available models and defaults', function () {
    $manager = app(AiModelManagerService::class);

    $textModels = $manager->getAvailableModels('text');
    expect($textModels)->not->toBeEmpty();

    $defaultText = $manager->getDefaultModel('text');
    expect($defaultText)->not->toBeNull();
    expect($defaultText->category)->toBe('text');
});

test('saving cloudflare model without @ prefix automatically normalizes to @cf/...', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AiConfig::class)
        ->set('formName', 'cf/black-forest-labs/flux-2-klein-9b')
        ->set('formCategory', 'image')
        ->set('formProvider', 'cloudflare')
        ->set('formModelId', 'cf/black-forest-labs/flux-2-klein-9b')
        ->set('formBaseUrl', 'https://worker.example.com')
        ->call('saveConnection')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ai_connections', [
        'name' => 'cf/black-forest-labs/flux-2-klein-9b',
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
    ]);
});

test('testing a cloudflare image connection sends prompt and model payload', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Http::fake([
        'https://worker.example.com*' => function (Request $request) {
            expect($request->isMultipart())->toBeTrue();
            $body = $request->body();
            expect($body)->toContain('ping test')
                ->and($body)->toContain('@cf/black-forest-labs/flux-2-klein-9b');

            return Http::response('fake-binary-image-data-output', 200);
        },
    ]);

    $conn = AiConnection::create([
        'user_id' => $user->id,
        'name' => 'Cloudflare FLUX-2 Klein 9B Test',
        'category' => 'image',
        'provider' => 'cloudflare',
        'model_id' => 'cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
    ]);

    Livewire::test(AiConfig::class)
        ->call('testConnection', $conn->id)
        ->assertHasNoErrors();

    $conn->refresh();
    expect($conn->last_test_status)->toBe('success');
    expect($conn->last_test_message)->toContain('@cf/black-forest-labs/flux-2-klein-9b');
});

test('testing cloudflare connection with multipart error surfaces helpful guidance message', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Http::fake([
        'https://worker.example.com*' => Http::response([
            'error' => 'Failed to generate image',
            'details' => "5006: Error: required properties at '/' are 'multipart'",
        ], 500),
    ]);

    $conn = AiConnection::create([
        'user_id' => $user->id,
        'name' => 'Cloudflare Failing Worker',
        'category' => 'image',
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
        'base_url' => 'https://worker.example.com',
    ]);

    Livewire::test(AiConfig::class)
        ->call('testConnection', $conn->id)
        ->assertHasNoErrors();

    $conn->refresh();
    expect($conn->last_test_status)->toBe('failed');
    expect($conn->last_test_message)->toContain('Model \'@cf/black-forest-labs/flux-2-klein-9b\' requires multipart/form-data');
});
