<?php

use App\Jobs\GenerateStoryScenesJob;
use App\Livewire\ContentTitleDetail;
use App\Models\Character;
use App\Models\ContentFactoryTemplate;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Prop;
use App\Models\Story;
use App\Models\User;
use App\Services\Ai\AiModelManagerService;
use App\Services\Ai\ContentTitleAiGeneratorService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('ContentTitleAiGeneratorService generates characters, environments, and props', function () {
    $user = User::factory()->create();

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Squirrel Adventures in the Mystic Hollow',
        'genre' => 'Children Fairytale',
        'visual_style' => 'Photorealistic Cinematic',
        'description' => 'A brave little squirrel explores a mysterious forest.',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-JennyNeural',
    ]);

    $template = ContentFactoryTemplate::create([
        'name' => 'Kids Morals & Fables',
        'slug' => 'kids-morals-fables',
        'niche' => 'Bedtime Stories',
        'genre' => 'Children Fairytale',
        'visual_style' => 'Photorealistic Cinematic',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-JennyNeural',
        'description' => 'Whimsical animal adventures with life lessons.',
        'prompt_template' => 'Create a captivating children story about {topic}',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Mock AiModelManagerService
    $mockAi = Mockery::mock(AiModelManagerService::class);
    $mockAi->shouldReceive('generateText')
        ->once()
        ->andReturn(json_encode([
            'art_direction' => [
                'color_palette' => 'Warm golden amber, mossy green, soft sky blue',
                'lighting_mood' => 'Soft morning sunlight filtering through autumn leaves',
                'camera_framing' => 'Ground level eye-level squirrel perspectives, whimsical wide angles',
            ],
            'characters' => [
                [
                    'name' => 'Barnaby the Red Squirrel',
                    'entity_type' => 'beast',
                    'gender' => 'male',
                    'age' => 3,
                    'appearance' => 'Fluffy russet fur, expressive brown eyes, bushy tail, carrying a small leather satchel',
                    'role' => 'Protagonist',
                    'personality' => 'Curious, energetic, sometimes forgetful',
                    'prompt_keywords' => 'cute red squirrel, fluffy bushy tail, tiny leather pouch, photorealistic macro details',
                ],
            ],
            'environments' => [
                [
                    'name' => 'The Whispering Hollow',
                    'setting_type' => 'exterior',
                    'time_of_day' => 'morning',
                    'atmosphere' => 'Dewy magical forest haven',
                    'lighting' => 'Dappled golden morning sunbeams through canopy',
                    'description' => 'A cozy clearing surrounded by ancient gnarled oak trees with glowing moss.',
                    'prompt_keywords' => 'ancient enchanted oak hollow, glowing green moss, sunbeams breaking through autumn leaves',
                ],
            ],
            'props' => [
                [
                    'name' => 'The Golden Acorn Compass',
                    'category' => 'relic',
                    'description' => 'An oversized luminous acorn etched with glowing constellation runes.',
                    'prompt_keywords' => 'golden carved acorn, bioluminescent compass markings, magical artifact',
                ],
            ],
        ]));
    $this->app->instance(AiModelManagerService::class, $mockAi);

    $service = app(ContentTitleAiGeneratorService::class);
    $results = $service->generateWorldBible($contentTitle, $template, [
        'scope' => 'all',
        'character_count' => 1,
        'environment_count' => 1,
        'prop_count' => 1,
        'auto_generate_sheets' => false,
        'user_id' => $user->id,
    ]);

    expect($results['characters'])->toHaveCount(1)
        ->and($results['environments'])->toHaveCount(1)
        ->and($results['props'])->toHaveCount(1);

    $this->assertDatabaseHas('characters', [
        'name' => 'Barnaby the Red Squirrel',
        'entity_type' => 'beast',
    ]);

    $this->assertDatabaseHas('environments', [
        'content_title_id' => $contentTitle->id,
        'name' => 'The Whispering Hollow',
    ]);

    $this->assertDatabaseHas('props', [
        'content_title_id' => $contentTitle->id,
        'name' => 'The Golden Acorn Compass',
    ]);

    // Character must be attached to content title
    $contentTitle->refresh();
    expect($contentTitle->characters)->toHaveCount(1)
        ->and($contentTitle->color_palette)->toBe('Warm golden amber, mossy green, soft sky blue');
});

test('ContentTitleAiGeneratorService generates story episode and dispatches scene decomposition', function () {
    Queue::fake();

    $user = User::factory()->create();

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Squirrel Adventures in the Mystic Hollow',
        'genre' => 'Children Fairytale',
        'visual_style' => 'Photorealistic Cinematic',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-JennyNeural',
    ]);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Barnaby',
        'prompt_keywords' => 'cute red squirrel',
    ]);
    $contentTitle->characters()->attach($character->id);

    $environment = Environment::create([
        'content_title_id' => $contentTitle->id,
        'name' => 'The Great Oak Tree',
        'setting_type' => 'exterior',
        'time_of_day' => 'day',
        'prompt_keywords' => 'giant oak tree hollow',
    ]);

    $template = ContentFactoryTemplate::create([
        'name' => 'Kids Morals & Fables',
        'slug' => 'kids-fables',
        'niche' => 'Bedtime Stories',
        'genre' => 'Children Fairytale',
        'visual_style' => 'Photorealistic Cinematic',
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-JennyNeural',
        'description' => 'Whimsical animal adventures with life lessons.',
        'prompt_template' => 'Create a captivating children story about {topic}',
        'is_active' => true,
    ]);

    $mockAi = Mockery::mock(AiModelManagerService::class);
    $mockAi->shouldReceive('generateText')
        ->once()
        ->andReturn(json_encode([
            'title' => 'Episode 1: The Missing Winter Hoard',
            'script' => 'Deep within the whispering canopy of the Great Oak, Barnaby the red squirrel paced back and forth across a mossy branch. Autumn had whispered its chilly arrival, and his grand collection of golden acorns had vanished without a single trace. Determined to uncover the mystery before the first frost, Barnaby set off on a daring journey through the ancient woods.',
        ]));
    $this->app->instance(AiModelManagerService::class, $mockAi);

    $service = app(ContentTitleAiGeneratorService::class);
    $story = $service->generateStoryEpisode($contentTitle, $template, [
        'episode_hook' => 'Barnaby realizes his secret stash is missing and discovers strange tiny footprints.',
        'character_ids' => [$character->id],
        'environment_ids' => [$environment->id],
        'pacing' => 'standard',
        'user_id' => $user->id,
    ]);

    expect($story)->toBeInstanceOf(Story::class)
        ->and($story->title)->toBe('Episode 1: The Missing Winter Hoard')
        ->and($story->content_title_id)->toBe($contentTitle->id);

    $this->assertDatabaseHas('stories', [
        'id' => $story->id,
        'title' => 'Episode 1: The Missing Winter Hoard',
    ]);

    Queue::assertPushed(GenerateStoryScenesJob::class, function ($job) use ($story) {
        return $job->story->id === $story->id;
    });
});

test('Livewire ContentTitleDetail triggers AI World Bible and Episode generation workflows', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $contentTitle = ContentTitle::create([
        'user_id' => $user->id,
        'title' => 'Neon Cyber Webtoon',
        'genre' => 'Cyberpunk',
        'visual_style' => 'Cyberpunk Neon',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
    ]);

    $template = ContentFactoryTemplate::create([
        'name' => 'Cyberpunk Shibuya Netrunner',
        'slug' => 'cyberpunk-shibuya',
        'niche' => 'Sci-Fi Recap',
        'genre' => 'Cyberpunk',
        'visual_style' => 'Cyberpunk Neon',
        'aspect_ratio' => '9:16',
        'voice_name' => 'id-ID-ArdiNeural',
        'description' => 'Cyberpunk netrunner heists in neon megacities.',
        'prompt_template' => 'Create a gritty cyberpunk story about {topic}',
        'is_active' => true,
    ]);

    $mockGenerator = Mockery::mock(ContentTitleAiGeneratorService::class);
    $mockGenerator->shouldReceive('generateWorldBible')
        ->once()
        ->andReturn([
            'characters' => [
                Character::create(['user_id' => $user->id, 'name' => 'Kaelen Zero', 'prompt_keywords' => 'cyberpunk netrunner']),
            ],
            'environments' => [
                Environment::create(['content_title_id' => $contentTitle->id, 'name' => 'Neon Alley 404', 'prompt_keywords' => 'cyberpunk neon alleyway']),
            ],
            'props' => [
                Prop::create(['content_title_id' => $contentTitle->id, 'name' => 'Cyberdeck MK-IV', 'prompt_keywords' => 'portable cyberdeck terminal']),
            ],
        ]);

    $mockGenerator->shouldReceive('generateStoryEpisode')
        ->once()
        ->andReturn(Story::create([
            'user_id' => $user->id,
            'content_title_id' => $contentTitle->id,
            'title' => 'Episode 1: Breach in Sector 7',
            'premise' => 'Kaelen hacked the sub-grid and triggered a corporate manhunt.',
            'genre' => 'Cyberpunk',
            'visual_style' => 'Cyberpunk Neon',
            'aspect_ratio' => '9:16',
            'voice_name' => 'id-ID-ArdiNeural',
            'language' => 'id',
            'status' => 'pending',
        ]));

    $this->app->instance(ContentTitleAiGeneratorService::class, $mockGenerator);

    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->assertSee(__('AI Generate World Bible'))
        ->call('openAiWorldBibleModal', 'all')
        ->assertSet('showAiWorldBibleModal', true)
        ->set('aiBibleTemplateId', $template->id)
        ->set('aiBiblePrompt', 'High tech corporate espionage with neon aesthetic')
        ->call('generateWorldBibleWithAi')
        ->assertSet('showAiWorldBibleModal', false)
        ->assertHasNoErrors();

    Livewire::test(ContentTitleDetail::class, ['contentTitle' => $contentTitle])
        ->set('activeTab', 'stories')
        ->assertSee(__('AI Generate Episode with Template'))
        ->call('openAiEpisodeModal')
        ->assertSet('showAiEpisodeModal', true)
        ->set('aiEpisodeTemplateId', $template->id)
        ->set('aiEpisodeHook', 'Kaelen hacks into Arasaka data vault')
        ->call('generateEpisodeWithAi')
        ->assertSet('showAiEpisodeModal', false)
        ->assertSet('activeTab', 'stories')
        ->assertHasNoErrors();
});
