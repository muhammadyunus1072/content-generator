<?php

use App\Livewire\ImageGenerator;
use App\Models\AiConnection;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('Image Generator page is protected by auth middleware', function () {
    // Guest is redirected
    $this->get(route('image-generator'))
        ->assertRedirect(route('login'));

    // Authenticated user can access
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('image-generator'))
        ->assertOk()
        ->assertSeeLivewire(ImageGenerator::class);
});

test('Image Generator component renders successfully with models and presets', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ImageGenerator::class)
        ->assertOk()
        ->assertSee('AI Image Generator')
        ->assertSee('Upload Reference Image');
});

test('Image Generator generates text-to-image and sets generatedImageUrl', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $dummyImage = str_repeat('X', 2000);

    Http::fake([
        'https://worker.example.com' => Http::response($dummyImage, 200),
    ]);

    $conn = AiConnection::create([
        'user_id' => $user->id,
        'name' => 'Cloudflare Test Worker',
        'category' => 'image',
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-4b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'test-key',
        'is_default' => true,
        'is_active' => true,
    ]);

    Livewire::test(ImageGenerator::class)
        ->set('selectedConnectionId', $conn->id)
        ->set('prompt', 'Futuristic cyberpunk neon city')
        ->set('style', 'cyberpunk')
        ->set('aspectRatio', '16:9')
        ->call('generateImage')
        ->assertHasNoErrors()
        ->assertSet('errorMessage', null);
});

test('Image Generator handles image-to-image with reference image upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);

    $dummyImage = str_repeat('Y', 2000);

    Http::fake([
        'https://worker.example.com' => function (Request $request) use ($dummyImage) {
            $data = $request->data();
            expect($data)->toHaveKey('image_b64')
                ->and($data['strength'])->toBe(0.75)
                ->and($data['guidance'])->toBe(7.5);

            return Http::response($dummyImage, 200);
        },
    ]);

    $conn = AiConnection::create([
        'user_id' => $user->id,
        'name' => 'Cloudflare FLUX-2 Klein',
        'category' => 'image',
        'provider' => 'cloudflare',
        'model_id' => '@cf/black-forest-labs/flux-2-klein-4b',
        'base_url' => 'https://worker.example.com',
        'api_key' => 'test-key',
        'is_default' => true,
        'is_active' => true,
    ]);

    $refFile = UploadedFile::fake()->image('character_ref.png', 512, 512);

    $component = Livewire::test(ImageGenerator::class)
        ->set('selectedConnectionId', $conn->id)
        ->set('referenceImage', $refFile)
        ->set('prompt', 'Same character in medieval armor')
        ->set('style', 'photorealistic')
        ->set('strength', 0.75)
        ->set('guidance', 7.5)
        ->call('generateImage')
        ->assertHasNoErrors()
        ->assertSet('errorMessage', null);

    // Test clearing reference image
    $component->call('clearReferenceImage')
        ->assertSet('referenceImage', null);
});

test('Image Generator integrates AI Director prompt template engine with presets and live compilation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(ImageGenerator::class)
        ->assertSet('promptMode', 'director')
        ->assertSee('Director Template Engine')
        ->assertSee('Macro Glowing Eyes');

    // Test loading a preset
    $component->call('loadPreset', 'macro_eyes')
        ->assertSet('focusType', 'body_part')
        ->assertSet('shotFraming', 'extreme macro close-up on glowing eyes');

    expect($component->get('compiledPrompt'))->toContain('extreme macro close-up on glowing eyes');

    // Test changing a field updates compiled prompt
    $component->set('actionDescription', 'glaring fiercely with dark purple lightning arcing');
    expect($component->get('compiledPrompt'))->toContain('glaring fiercely with dark purple lightning arcing');

    // Test switching to raw mode syncs prompt
    $component->call('syncToRawPrompt')
        ->assertSet('promptMode', 'raw');
    expect($component->get('prompt'))->toBe($component->get('compiledPrompt'));
});
