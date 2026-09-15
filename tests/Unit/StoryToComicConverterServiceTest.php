<?php

use App\Models\Episode;
use App\Models\EpubProject;
use App\Models\EpubScene;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryToComicConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('StoryToComicConverterService extracts quotes and converts Story to Comic format', function () {
    $user = User::factory()->create();
    $story = Story::create([
        'user_id' => $user->id,
        'title' => 'The Shadow Sovereign',
        'premise' => 'An E-rank hunter gets a second chance in life.',
        'visual_style' => 'Anime concept art',
    ]);
    $episode = Episode::create([
        'story_id' => $story->id,
        'episode_number' => 1,
        'title' => 'Prologue Awakening',
    ]);
    $scene = Scene::create([
        'episode_id' => $episode->id,
        'scene_number' => 1,
        'title' => 'Double Dungeon',
    ]);
    $board = SceneBoard::create([
        'episode_id' => $episode->id,
        'scene_id' => $scene->id,
        'board_number' => 1,
        'sequence_order' => 1,
        'narration_chunk' => 'Ren clenched his teeth and shouted: "I will not die in this place!" while the stone statues raised their weapons.',
        'visual_prompt' => 'Ren facing giant stone statues in ancient temple',
    ]);

    $converter = new StoryToComicConverterService;
    $convertedStory = $converter->convertStoryToComic($story, 'Dark Fantasy Manhwa, Solo Leveling style');

    expect($convertedStory->visual_style)->toBe('Dark Fantasy Manhwa, Solo Leveling style');

    $board->refresh();
    expect($board->speech_bubbles)->toBeArray();
    expect($board->speech_bubbles)->not->toBeEmpty();
    expect($board->speech_bubbles[0]['text'])->toContain('I will not die in this place!');
    expect($board->gutter_spacing)->toBe(80);
});

test('StoryToComicConverterService converts EpubProject chapters into ComicStudio ContentTitle and SceneBoards', function () {
    $user = User::factory()->create();
    $epubProject = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Lord of Mysteries Volume 1',
        'author' => 'Cuttlefish That Loves Diving',
        'epub_path' => 'epub/books/lord_of_mysteries.epub',
        'visual_style' => 'Victorian Steampunk Anime',
        'aspect_ratio' => '16:9',
    ]);

    EpubScene::create([
        'epub_project_id' => $epubProject->id,
        'chapter_index' => 1,
        'scene_index' => 1,
        'sequence_order' => 1,
        'script_text' => 'Klein looked into the mirror and whispered: "The fool that doesn\'t belong to this era."',
        'original_image_path' => 'epub/images/scene1.png',
    ]);

    EpubScene::create([
        'epub_project_id' => $epubProject->id,
        'chapter_index' => 1,
        'scene_index' => 2,
        'sequence_order' => 2,
        'script_text' => 'A crimson moon hung in the eerie night sky.',
        'original_image_path' => 'epub/images/scene2.png',
    ]);

    $converter = new StoryToComicConverterService;
    $contentTitle = $converter->convertEpubProjectToComic($epubProject, 'Mystery Fantasy');

    expect($contentTitle)->not->toBeNull();
    expect($contentTitle->title)->toBe('Lord of Mysteries Volume 1');
    expect($contentTitle->genre)->toBe('Mystery Fantasy');

    // Verify Story was created
    $story = $contentTitle->stories()->first();
    expect($story)->not->toBeNull();

    // Verify Episode was created
    $episode = $story->episodes()->first();
    expect($episode)->not->toBeNull();

    // Verify SceneBoards were generated
    $boards = $episode->boards;
    expect($boards->count())->toBe(2);
    expect($boards->first()->speech_bubbles)->toBeArray();
    expect($boards->first()->gutter_spacing)->toBe(80);
});
