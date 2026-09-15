<?php

use App\Jobs\DuplicateProjectLanguageJob;
use App\Jobs\GenerateBoardVoiceJob;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('DuplicateProjectLanguageJob clones story, translates narration, and dispatches new voice jobs', function () {
    Queue::fake([GenerateBoardVoiceJob::class]);

    $story = Story::create([
        'user_id' => $this->user->id,
        'title' => 'The Whispering Forest',
        'premise' => 'Two children discover a magical tree in the ancient forest.',
        'visual_style' => 'Whimsical watercolor',
        'voice_name' => 'en-US-JennyNeural',
        'status' => 'completed',
    ]);

    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Chapter 1: The Discovery',
        'summary' => 'Entering the deep forest.',
        'status' => 'completed',
    ]);

    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1: Forest Entrance',
        'narration_text' => 'Entering the ancient forest.',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'sequence_order' => 1,
        'narration_chunk' => 'The trees began to whisper as the wind picked up.',
        'visual_prompt' => 'Ancient glowing forest trees with gentle mist',
        'image_path' => 'stories/boards/sample_image.jpg',
        'status' => 'ready',
    ]);

    // Mock TranslationService
    $mockTranslator = Mockery::mock(TranslationService::class);
    $mockTranslator->shouldReceive('getSupportedLanguages')->andReturn(['es' => '🇪🇸 Spanish']);
    $mockTranslator->shouldReceive('getDefaultVoiceForLanguage')->with('es')->andReturn('es-ES-AlvaroNeural');
    $mockTranslator->shouldReceive('translate')->andReturnUsing(function ($text, $lang) {
        if ($text === 'The Whispering Forest') {
            return 'El Bosque Susurrante';
        }
        if ($text === 'The trees began to whisper as the wind picked up.') {
            return 'Los árboles comenzaron a susurrar cuando el viento se levantó.';
        }

        return 'Translated: '.$text;
    });

    $job = new DuplicateProjectLanguageJob($story, 'es');
    $job->handle($mockTranslator);

    // Verify cloned story exists
    $clonedStory = Story::where('id', '!=', $story->id)->first();
    expect($clonedStory)->not->toBeNull()
        ->and($clonedStory->title)->toBe('[ES] El Bosque Susurrante')
        ->and($clonedStory->voice_name)->toBe('es-ES-AlvaroNeural');

    // Verify cloned board retains original image path (no extra GPU image generation needed)
    $clonedEpisode = $clonedStory->episodes->first();
    expect($clonedEpisode)->not->toBeNull();

    $clonedBoard = $clonedEpisode->boards->first();
    expect($clonedBoard)->not->toBeNull()
        ->and($clonedBoard->image_path)->toBe('stories/boards/sample_image.jpg')
        ->and($clonedBoard->narration_chunk)->toBe('Los árboles comenzaron a susurrar cuando el viento se levantó.');

    // Verify GenerateBoardVoiceJob was dispatched for the new board
    Queue::assertPushed(GenerateBoardVoiceJob::class, function ($pushedJob) use ($clonedBoard) {
        return $pushedJob->board->id === $clonedBoard->id;
    });
});
