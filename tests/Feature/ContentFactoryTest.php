<?php

use App\Livewire\ContentFactory;
use App\Livewire\EpubStudio;
use App\Livewire\StoryStudio;
use App\Models\ContentFactoryTemplate;
use App\Models\User;
use Database\Seeders\ContentFactoryTemplateSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->seed(ContentFactoryTemplateSeeder::class);
});

test('content factory seeds all 16 monetizable niche templates including motion comics', function () {
    expect(ContentFactoryTemplate::count())->toBe(16);

    $niches = ContentFactoryTemplate::pluck('niche')->unique()->values()->all();
    expect($niches)->toContain(
        'manhwa_recap',
        'kids_story',
        'science_explainer',
        'horror_narration',
        'book_summary'
    );

    // Verify motion comic template type
    $motionComics = ContentFactoryTemplate::where('content_type', 'motion_comic')->get();
    expect($motionComics)->toHaveCount(6);

    // Each template has rich structured data
    $template = ContentFactoryTemplate::first();
    expect($template->monetization_tips)->toBeArray()
        ->and($template->example_titles)->toBeArray()
        ->and($template->estimated_rpm_min)->toBeGreaterThan(0)
        ->and($template->visual_style)->not->toBeEmpty();
});

test('content factory route and page load successfully', function () {
    $response = $this->get(route('content-factory'));
    $response->assertOk();
    $response->assertSeeLivewire(ContentFactory::class);
});

test('content factory livewire component lists all templates and supports niche filtering', function () {
    Livewire::test(ContentFactory::class)
        ->assertSee('Turn Video Niches Into Revenue')
        ->assertSee('Manhwa Epic Recap')
        ->assertSee('Bedtime Storybook')
        ->assertSee('Cosmic & Earth Science Explainer')
        ->set('niche', 'science_explainer')
        ->assertSee('Cosmic & Earth Science Explainer')
        ->assertDontSee('Bedtime Storybook');
});

test('content factory livewire supports searching and platform filtering', function () {
    Livewire::test(ContentFactory::class)
        ->set('search', 'Horror')
        ->assertSee('Midnight Liminal Horror')
        ->assertDontSee('Bedtime Storybook')
        ->set('platform', 'tiktok')
        ->assertSee('Micro Horror');
});

test('content factory modal opens with monetization tips and titles', function () {
    $template = ContentFactoryTemplate::where('slug', 'manhwa-epic-recap-yt')->first();

    Livewire::test(ContentFactory::class)
        ->call('openPreview', $template->id)
        ->assertSet('showPreviewModal', true)
        ->assertSee('Monetization & Conversion Playbook')
        ->call('closePreview')
        ->assertSet('showPreviewModal', false);
});

test('story studio pre-fills form data when template_id is provided in query parameter', function () {
    $template = ContentFactoryTemplate::where('niche', 'science_explainer')->first();

    Livewire::withQueryParams(['template_id' => $template->id])
        ->test(StoryStudio::class)
        ->assertSet('visualStyle', $template->visual_style)
        ->assertSet('aspectRatio', $template->aspect_ratio)
        ->assertSet('voiceName', $template->voice_name)
        ->assertSet('openCreateForm', true);
});

test('epub studio pre-fills settings when template_id is provided in query parameter', function () {
    $template = ContentFactoryTemplate::where('niche', 'kids_story')->first();

    Livewire::withQueryParams(['template_id' => $template->id])
        ->test(EpubStudio::class)
        ->assertSet('aspectRatio', $template->aspect_ratio)
        ->assertSet('voiceName', $template->voice_name)
        ->assertSet('showUploadModal', true);
});
