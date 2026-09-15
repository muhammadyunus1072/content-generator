<?php

namespace App\Services;

use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\EpubProject;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;

class StoryToComicConverterService
{
    /**
     * Convert an existing Story Studio Story into a Motion Comic and Webtoon ready story.
     */
    public function convertStoryToComic(Story $story, ?string $visualStyle = null): Story
    {
        $style = $visualStyle ?: ($story->visual_style ?: 'Dark Fantasy Manhwa, Solo Leveling art style');
        $story->update(['visual_style' => $style]);

        $availableChars = $story->getAvailableCharacters();

        foreach ($story->episodes as $episode) {
            foreach ($episode->boards as $board) {
                // Extract dialogue into speech bubbles if not already present
                if (empty($board->speech_bubbles)) {
                    $bubbles = $this->extractDialogueFromNarration($board->narration_chunk, $availableChars);
                    $board->update([
                        'speech_bubbles' => $bubbles,
                        'animation_effect' => $board->animation_effect ?: ($board->board_number % 2 === 1 ? 'ken_burns' : 'parallax'),
                        'bubble_style' => $board->bubble_style ?: 'manga',
                        'gutter_spacing' => $board->gutter_spacing ?: 80,
                    ]);
                }
            }
        }

        return $story;
    }

    /**
     * Convert an entire EPUB Project into a ComicStudio ContentTitle, Story, and Episode with Comic Panels.
     */
    public function convertEpubProjectToComic(EpubProject $epubProject, ?string $genre = 'Action Fantasy'): ContentTitle
    {
        $titleName = $epubProject->title ?: 'EPUB Comic Adaptation';

        // 1. Create or find ContentTitle
        $contentTitle = ContentTitle::create([
            'user_id' => $epubProject->user_id,
            'title' => $titleName,
            'description' => "Comic adaptation converted from novel: {$titleName}",
            'genre' => $genre,
            'visual_style' => 'Dark Fantasy Manhwa, high contrast digital ink, webtoon panels',
            'aspect_ratio' => $epubProject->aspect_ratio ?: '16:9',
            'voice_name' => $epubProject->voice_name ?: 'en-US-ChristopherNeural',
            'tts_rate' => $epubProject->tts_rate ?: '+5%',
            'tts_pitch' => $epubProject->tts_pitch ?: '-5Hz',
        ]);

        // 2. Create Story
        $story = Story::create([
            'user_id' => $epubProject->user_id,
            'content_title_id' => $contentTitle->id,
            'title' => $titleName,
            'premise' => "Adapted from novel chapters: {$titleName}",
            'genre' => $genre,
            'visual_style' => $contentTitle->visual_style,
            'aspect_ratio' => $contentTitle->aspect_ratio,
            'voice_name' => $contentTitle->voice_name,
            'tts_rate' => $contentTitle->tts_rate,
            'tts_pitch' => $contentTitle->tts_pitch,
        ]);

        // 3. Create Episode
        $episode = Episode::create([
            'story_id' => $story->id,
            'episode_number' => 1,
            'title' => "Chapter 1: {$titleName}",
            'synopsis' => "First chapter comic adaptation of {$titleName}",
            'status' => 'storyboarded',
        ]);

        // 4. Create Scene
        $scene = Scene::create([
            'episode_id' => $episode->id,
            'scene_number' => 1,
            'title' => 'Opening Scene',
            'setting' => 'Novel Setting',
            'mood' => 'dramatic',
        ]);

        // 5. Convert each EpubScene to a SceneBoard
        $epubScenes = $epubProject->scenes()->orderBy('sequence_order')->get();
        $globalSeq = 1;

        foreach ($epubScenes as $epubScene) {
            $narration = $epubScene->script_text ?: ($epubScene->original_script_text ?: '');
            $bubbles = $this->extractDialogueFromNarration($narration, collect());

            SceneBoard::create([
                'episode_id' => $episode->id,
                'scene_id' => $scene->id,
                'board_number' => $globalSeq,
                'sequence_order' => $globalSeq++,
                'narration_chunk' => $narration,
                'visual_prompt' => $narration ? "Manhwa panel: {$narration}" : 'Webtoon comic panel visual',
                'view_type' => 'wide_shot',
                'camera_movement' => 'zoom_in',
                'animation_effect' => ($globalSeq % 3 === 0) ? 'camera_shake' : (($globalSeq % 2 === 0) ? 'parallax' : 'ken_burns'),
                'transition_type' => 'crossfade',
                'speech_bubbles' => $bubbles,
                'bubble_style' => 'manga',
                'mood_tag' => 'action',
                'gutter_spacing' => 80,
                'image_path' => $epubScene->image_path ?: $epubScene->original_image_path,
                'audio_path' => $epubScene->audio_path,
                'subtitle_path' => $epubScene->subtitle_path,
                'duration_seconds' => (float) ($epubScene->audio_duration ?: 4.0),
                'status' => $epubScene->image_path ? 'completed' : 'pending',
            ]);
        }

        return $contentTitle;
    }

    /**
     * Extract spoken dialogue from narration text and map into structured speech bubbles.
     *
     * @return array<int, array{speaker: string, text: string, style: string, position: string, emotion: string}>
     */
    public function extractDialogueFromNarration(string $text, $availableCharacters): array
    {
        $bubbles = [];
        $cleanText = trim($text);

        // Regex to capture quoted speech: "...", “...”, or '...'
        if (preg_match_all('/(?:["“])([^"”]{3,120})(?:["”])/u', $cleanText, $matches)) {
            $speaker = 'Character';

            if ($availableCharacters && $availableCharacters->isNotEmpty()) {
                // Check if any character's name is mentioned near quotes
                foreach ($availableCharacters as $char) {
                    if (stripos($cleanText, $char->name) !== false) {
                        $speaker = $char->name;
                        break;
                    }
                }
            }

            foreach ($matches[1] as $idx => $quote) {
                $bubbles[] = [
                    'speaker' => $speaker,
                    'text' => trim($quote),
                    'style' => 'manga',
                    'position' => ($idx % 2 === 0) ? 'top-right' : 'top-left',
                    'emotion' => 'action',
                ];
            }
        }

        return $bubbles;
    }
}
