<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Story;
use App\Services\TranslationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DuplicateProjectLanguageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public Story $sourceStory,
        public string $targetLanguage,
        public ?string $targetVoice = null
    ) {}

    public function handle(TranslationService $translator): void
    {
        try {
            $langName = $translator->getSupportedLanguages()[$this->targetLanguage] ?? strtoupper($this->targetLanguage);
            $voice = $this->targetVoice ?: $translator->getDefaultVoiceForLanguage($this->targetLanguage);

            // 1. Translate Title & Premise
            $translatedTitle = '['.strtoupper($this->targetLanguage).'] '.$translator->translate($this->sourceStory->title, $this->targetLanguage);
            $translatedPremise = $this->sourceStory->premise
                ? $translator->translate($this->sourceStory->premise, $this->targetLanguage)
                : null;

            // 2. Clone Story
            $newStory = $this->sourceStory->replicate([
                'output_video_path',
            ]);
            $newStory->title = $translatedTitle;
            $newStory->premise = $translatedPremise;
            $newStory->voice_name = $voice;
            $newStory->status = 'assets_generating';
            $newStory->save();

            // Copy characters association
            if ($this->sourceStory->characters()->exists()) {
                $newStory->characters()->sync($this->sourceStory->characters->pluck('id'));
            }

            // 3. Clone Episodes, Scenes, and SceneBoards with translated narration
            foreach ($this->sourceStory->episodes as $sourceEpisode) {
                $translatedEpisodeTitle = $translator->translate($sourceEpisode->title, $this->targetLanguage);
                $translatedSynopsis = $sourceEpisode->synopsis
                    ? $translator->translate($sourceEpisode->synopsis, $this->targetLanguage)
                    : null;

                $newEpisode = $sourceEpisode->replicate([
                    'video_path',
                    'audio_path',
                    'subtitle_path',
                ]);
                $newEpisode->story_id = $newStory->id;
                $newEpisode->title = $translatedEpisodeTitle;
                $newEpisode->synopsis = $translatedSynopsis;
                $newEpisode->status = 'assets_generating';
                $newEpisode->save();

                // Clone scenes
                $sceneMap = [];
                foreach ($sourceEpisode->scenes as $sourceScene) {
                    $newScene = $sourceScene->replicate();
                    $newScene->episode_id = $newEpisode->id;
                    $newScene->title = $translator->translate($sourceScene->title ?? 'Scene', $this->targetLanguage);
                    $newScene->narration_text = $sourceScene->narration_text
                        ? $translator->translate($sourceScene->narration_text, $this->targetLanguage)
                        : null;
                    $newScene->save();
                    $sceneMap[$sourceScene->id] = $newScene->id;
                }

                // If episode has no scenes, ensure at least one default scene exists
                if (empty($sceneMap)) {
                    $fallbackScene = $newEpisode->scenes()->create([
                        'scene_number' => 1,
                        'title' => $translatedEpisodeTitle,
                    ]);
                    $defaultSceneId = $fallbackScene->id;
                } else {
                    $defaultSceneId = reset($sceneMap);
                }

                foreach ($sourceEpisode->boards()->orderBy('sequence_order')->get() as $sourceBoard) {
                    $translatedNarration = $translator->translate($sourceBoard->narration_chunk, $this->targetLanguage);

                    $newBoard = $sourceBoard->replicate([
                        'audio_path',
                        'subtitle_path',
                        'duration_seconds',
                    ]);
                    $newBoard->episode_id = $newEpisode->id;
                    $newBoard->scene_id = $sceneMap[$sourceBoard->scene_id] ?? $defaultSceneId;
                    $newBoard->narration_chunk = $translatedNarration;
                    $newBoard->status = 'generating_audio';
                    // Retain image_path from source board to avoid regenerating artwork!
                    $newBoard->image_path = $sourceBoard->image_path;
                    $newBoard->save();

                    // Dispatch voice generation in target language
                    GenerateBoardVoiceJob::dispatch($newBoard);
                }
            }

            Log::info('DuplicateProjectLanguageJob completed successfully', [
                'source_story_id' => $this->sourceStory->id,
                'new_story_id' => $newStory->id,
                'target_language' => $this->targetLanguage,
                'voice' => $voice,
            ]);
        } catch (Exception $e) {
            Log::error('DuplicateProjectLanguageJob failed: '.$e->getMessage(), [
                'source_story_id' => $this->sourceStory->id,
                'target_language' => $this->targetLanguage,
            ]);
            throw $e;
        }
    }
}
