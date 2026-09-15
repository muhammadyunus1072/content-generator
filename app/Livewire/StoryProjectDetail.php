<?php

namespace App\Livewire;

use App\Jobs\DuplicateProjectLanguageJob;
use App\Jobs\GenerateBoardImageJob;
use App\Jobs\GenerateBoardVoiceJob;
use App\Jobs\RenderEpisodeVideoJob;
use App\Models\Character;
use App\Models\Environment;
use App\Models\Episode;
use App\Models\Prop;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Services\PromptCompilerService;
use App\Services\SeoGeneratorService;
use App\Services\ThumbnailGeneratorService;
use App\Services\TranslationService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class StoryProjectDetail extends Component
{
    public Story $story;

    public ?int $activeBoardId = null;

    public string $editingNarration = '';

    public string $editingPrompt = '';

    public string $editingViewType = 'two_shot_interaction';

    public string $editingCameraMovement = 'zoom_in';

    // Model Sheet Inspector Modal
    public bool $showAssetModal = false;

    public ?string $inspectingSheetTitle = null;

    public ?string $inspectingSheetImageUrl = null;

    public ?array $inspectingSheetData = null;

    public ?string $inspectingSheetTokens = null;

    public ?string $inspectingAssetType = null;

    public ?string $inspectingTokenTier = null; // 'blueprint_verified', 'world_bible_profile', 'entity_baseline'

    // SEO Kit Modal
    public bool $showSeoModal = false;

    public ?array $seoData = null;

    // Thumbnail Modal
    public bool $showThumbnailModal = false;

    public string $thumbnailText = '';

    public string $thumbnailNiche = 'manhwa_recap';

    public ?string $generatedThumbnailUrl = null;

    // Duplicate to Language Modal
    public bool $showDuplicateModal = false;

    public string $duplicateTargetLanguage = 'es';

    public ?string $duplicateTargetVoice = null;

    public function mount(Story $story): void
    {
        $this->story = $story;
        $firstBoard = $this->story->episodes()->first()?->boards()->orderBy('sequence_order')->first();
        if ($firstBoard) {
            $this->selectBoard($firstBoard->id);
        }
    }

    public function selectBoard(int $boardId): void
    {
        $board = SceneBoard::find($boardId);
        if ($board) {
            $this->activeBoardId = $board->id;
            $this->editingNarration = $board->narration_chunk;
            $compiler = app(PromptCompilerService::class);
            $this->editingPrompt = $board->scene_data ? $compiler->compilePrompt($this->story->contentTitle, $board->scene_data) : 'No scene data generated.';
            $this->editingViewType = $board->view_type ?? 'two_shot_interaction';
            $this->editingCameraMovement = $board->camera_movement ?? 'zoom_in';
        }
    }

    public function saveActiveBoard(): void
    {
        if (! $this->activeBoardId) {
            return;
        }

        $board = SceneBoard::find($this->activeBoardId);
        if ($board) {
            $board->update([
                'narration_chunk' => $this->editingNarration,
                'view_type' => $this->editingViewType,
                'camera_movement' => $this->editingCameraMovement,
            ]);

            Flux::toast(variant: 'success', text: __('Comic panel prompt and settings saved!'));
        }
    }

    public function generateAllAssets(): void
    {
        $this->regenerateAllSceneAssets('both');
    }

    public function regenerateAllSceneAssets(string $scope = 'both'): void
    {
        $firstEpisode = $this->story->episodes()->first();
        if (! $firstEpisode) {
            Flux::toast(variant: 'danger', text: __('No episode found.'));

            return;
        }

        $boards = $firstEpisode->boards;

        if ($boards->isEmpty()) {
            Flux::toast(variant: 'danger', text: __('No scene boards found to regenerate.'));

            return;
        }

        foreach ($boards as $board) {
            if ($scope === 'both' || $scope === 'images_only') {
                GenerateBoardImageJob::dispatch($board);
            }
            if ($scope === 'both' || $scope === 'voices_only') {
                GenerateBoardVoiceJob::dispatch($board);
            }
        }

        $this->story->update(['status' => 'assets_generating']);

        $message = match ($scope) {
            'images_only' => __('Regenerating all comic panel images in background!'),
            'voices_only' => __('Regenerating all voiceover audio in background!'),
            default => __('Regenerating all comic panel images & voiceovers in background!'),
        };

        Flux::toast(variant: 'success', text: $message);
    }

    public function regenerateImage(int $boardId): void
    {
        $board = SceneBoard::find($boardId);
        if ($board) {
            GenerateBoardImageJob::dispatch($board);
            Flux::toast(variant: 'success', text: __('Rendering comic panel artwork for Board #'.$board->board_number));
        }
    }

    public function regenerateVoice(int $boardId): void
    {
        $board = SceneBoard::find($boardId);
        if ($board) {
            GenerateBoardVoiceJob::dispatch($board);
            Flux::toast(variant: 'success', text: __('Synthesizing voiceover for Board #'.$board->board_number));
        }
    }

    public function inspectAsset(string $type, int $id): void
    {
        $item = match ($type) {
            'character' => Character::find($id),
            'environment' => Environment::find($id),
            'prop' => Prop::find($id),
            default => null,
        };

        if ($item) {
            $this->inspectingAssetType = $type;
            $this->inspectingSheetTitle = "{$item->name} - Reference Sheet";
            $this->inspectingSheetImageUrl = $item->sheet_image_url ?? $item->image_url ?? $item->avatar_url;
            $this->inspectingSheetData = $item->sheet_data;
            $this->inspectingSheetTokens = $item->getConsistencyTokens();

            if (! empty($item->sheet_data['consistency_prompt_tokens'])) {
                $this->inspectingTokenTier = 'blueprint_verified';
            } elseif (! empty($item->prompt_keywords)) {
                $this->inspectingTokenTier = 'world_bible_profile';
            } else {
                $this->inspectingTokenTier = 'entity_baseline';
            }

            $this->showAssetModal = true;
        }
    }

    public function bindCharacterToBoard(int $boardId, int $characterId): void
    {
        $board = SceneBoard::find($boardId);
        $character = Character::find($characterId);
        if ($board && $character) {
            $currentChars = $board->characters_present ?? [];
            if (! in_array($character->name, $currentChars, true)) {
                $currentChars[] = $character->name;
                $board->update(['characters_present' => $currentChars]);
                Flux::toast(variant: 'success', text: __('Character ":name" bound to panel.', ['name' => $character->name]));
            }
        }
    }

    public function unbindCharacterFromBoard(int $boardId, string $characterName): void
    {
        $board = SceneBoard::find($boardId);
        if ($board) {
            $currentChars = array_values(array_filter(
                $board->characters_present ?? [],
                fn ($n) => strtolower(trim($n)) !== strtolower(trim($characterName))
            ));
            $board->update(['characters_present' => $currentChars]);
            Flux::toast(variant: 'success', text: __('Character removed from panel.'));
        }
    }

    public function renderFinalVideo(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        $readyBoards = $episode->boards()->whereNotNull('image_path')->whereNotNull('audio_path')->count();
        $totalBoards = $episode->boards()->count();

        if ($totalBoards === 0 || $readyBoards < $totalBoards) {
            Flux::toast(
                variant: 'danger',
                text: __('Cannot render: Only '.$readyBoards.'/'.$totalBoards.' boards have both image and voice ready.')
            );

            return;
        }

        RenderEpisodeVideoJob::dispatch($episode);
        $this->story->update(['status' => 'rendering']);

        Flux::toast(
            variant: 'success',
            text: __('Rendering video with FFmpeg in background! Syncing audio, motion, and captions.')
        );
    }

    /**
     * @return Collection<int, Character>
     */
    public function getAvailableCharactersProperty()
    {
        return $this->story->getAvailableCharacters();
    }

    public function findCharacterByName(?string $name): ?Character
    {
        return $this->story->resolveCharacterByName($name);
    }

    public function openSeoModal(SeoGeneratorService $seoService): void
    {
        $this->showSeoModal = true;
        if (! $this->seoData) {
            $this->generateSeoMetadata($seoService);
        }
    }

    public function generateSeoMetadata(SeoGeneratorService $seoService): void
    {
        $this->seoData = $seoService->generateForStory($this->story);
        Flux::toast(variant: 'success', text: __('SEO Kit generated!'));
    }

    public function closeSeoModal(): void
    {
        $this->showSeoModal = false;
    }

    public function openThumbnailModal(): void
    {
        if (empty($this->thumbnailText)) {
            $this->thumbnailText = strtoupper(Str::limit($this->story->title, 26, '?!'));
        }
        $this->showThumbnailModal = true;
    }

    public function closeThumbnailModal(): void
    {
        $this->showThumbnailModal = false;
    }

    public function generateThumbnail(ThumbnailGeneratorService $thumbnailService): void
    {
        $board = $this->story->episodes()->first()?->boards()
            ->whereNotNull('image_path')
            ->orderBy('sequence_order')
            ->first();

        if (! $board || ! $board->image_path) {
            Flux::toast(variant: 'danger', text: __('No scene image found. Generate scene image boards first!'));

            return;
        }

        try {
            $aspectRatio = $this->story->contentTitle?->aspect_ratio ?? '16:9';
            $relPath = $thumbnailService->generate(
                sourceImagePath: $board->image_path,
                overlayText: $this->thumbnailText,
                niche: $this->thumbnailNiche,
                aspectRatio: $aspectRatio
            );

            $this->generatedThumbnailUrl = Storage::disk('public')->url($relPath);
            Flux::toast(variant: 'success', text: __('High-CTR Thumbnail generated successfully!'));
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: __('Thumbnail creation failed: ').$e->getMessage());
        }
    }

    public function openDuplicateModal(): void
    {
        $this->showDuplicateModal = true;
    }

    public function closeDuplicateModal(): void
    {
        $this->showDuplicateModal = false;
    }

    public function duplicateToLanguage(TranslationService $translator): void
    {
        DuplicateProjectLanguageJob::dispatch(
            $this->story,
            $this->duplicateTargetLanguage,
            $this->duplicateTargetVoice
        );

        $this->showDuplicateModal = false;

        $langName = $translator->getSupportedLanguages()[$this->duplicateTargetLanguage] ?? strtoupper($this->duplicateTargetLanguage);
        Flux::toast(
            variant: 'success',
            text: __('Project duplicate queued! Translating into :lang and synthesizing native speech audio.', ['lang' => $langName])
        );
    }

    public function render(TranslationService $translator)
    {
        $this->story->load([
            'contentTitle.characters',
            'contentTitle.environments',
            'contentTitle.props',
            'characters',
            'episodes.scenes',
            'episodes.boards' => function ($query) {
                $query->with('environment')->orderBy('sequence_order');
            },
        ]);

        $activeBoard = $this->activeBoardId ? SceneBoard::with('environment')->find($this->activeBoardId) : null;

        return view('livewire.story-project-detail', [
            'activeBoard' => $activeBoard,
            'supportedLanguages' => $translator->getSupportedLanguages(),
        ]);
    }
}
