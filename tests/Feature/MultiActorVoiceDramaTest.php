<?php

use App\Jobs\GenerateBoardVoiceJob;
use App\Jobs\GenerateMultiActorVoiceJob;
use App\Livewire\ComicStudio;
use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\MultiActorVoiceSynthesisService;
use App\Services\TextToSpeechService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('ComicStudio openBubbleEditor loads existing audio and dialogue timeline', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Voice Test Title',
        'premise' => 'Testing audio drama UI.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Act 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);

    $existingTimeline = [
        ['speaker' => 'Ren', 'text' => 'Hold the line!', 'emotion' => 'shouting', 'start_time' => 0.0, 'end_time' => 2.1],
    ];

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'The fight raged on.',
        'visual_prompt' => 'Battlefield',
        'audio_path' => 'stories/boards/board_1_audio.mp3',
        'dialogue_audio_timeline' => $existingTimeline,
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('openBubbleEditor', $board->id)
        ->assertSet('showBubbleModal', true)
        ->assertSet('boardDialogueTimeline', $existingTimeline)
        ->assertNotSet('editingBoardAudioUrl', null);
});

test('ComicStudio synthesizeEpisodeVoices queues multi-actor synthesis for all boards', function () {
    Queue::fake();

    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Batch Audio Title',
        'premise' => 'Testing episode audio batch.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);

    $board1 = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Panel 1 narration',
        'visual_prompt' => 'Prompt 1',
    ]);

    $board2 = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 2,
        'sequence_order' => 2,
        'narration_chunk' => 'Panel 2 narration',
        'visual_prompt' => 'Prompt 2',
    ]);

    Livewire::actingAs($user)
        ->test(ComicStudio::class)
        ->call('synthesizeEpisodeVoices', $episode->id);

    Queue::assertPushed(GenerateMultiActorVoiceJob::class, 2);
});

test('GenerateBoardVoiceJob delegates to MultiActorVoiceSynthesisService when speech bubbles exist', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'Delegation Test',
        'premise' => 'Testing job delegation.',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Episode 1',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Scene 1',
    ]);

    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Fallback narration',
        'visual_prompt' => 'Visual prompt',
        'speech_bubbles' => [
            ['speaker' => 'Hero', 'text' => 'Victory is ours!', 'emotion' => 'joy'],
        ],
    ]);

    $multiVoiceMock = Mockery::mock(MultiActorVoiceSynthesisService::class);
    $multiVoiceMock->shouldReceive('synthesizeBoardDialogue')
        ->once()
        ->with(Mockery::on(fn ($b) => $b->id === $board->id), Mockery::any())
        ->andReturn([
            'audio_path' => 'stories/boards/mock.mp3',
            'subtitle_path' => 'stories/boards/mock.vtt',
            'duration_seconds' => 3.5,
            'timeline' => [],
        ]);

    $ttsMock = Mockery::mock(TextToSpeechService::class);

    $job = new GenerateBoardVoiceJob($board);
    $job->handle($ttsMock, $multiVoiceMock);
});
