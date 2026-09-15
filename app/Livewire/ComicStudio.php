<?php

namespace App\Livewire;

use App\Jobs\ExportComicArchiveJob;
use App\Jobs\ExportWebtoonPackJob;
use App\Jobs\GenerateMangaPagesJob;
use App\Jobs\GenerateMultiActorVoiceJob;
use App\Jobs\GenerateSpeechBubbleJob;
use App\Jobs\GenerateThumbnailAssetsJob;
use App\Jobs\GenerateWebtoonStripJob;
use App\Jobs\RenderMotionComicJob;
use App\Models\AiConnection;
use App\Models\Character;
use App\Models\ContentFactoryTemplate;
use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\EpubProject;
use App\Models\SceneBoard;
use App\Models\Story;
use App\Services\Ai\AiModelManagerService;
use App\Services\CharacterBibleService;
use App\Services\MultiActorVoiceSynthesisService;
use App\Services\StoryToComicConverterService;
use App\Services\TextToSpeechService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;

class ComicStudio extends Component
{
    public string $tab = 'titles'; // 'titles' or 'characters'

    public ?string $editingBoardAudioUrl = null;

    public array $boardDialogueTimeline = [];

    // Content Title creation form
    public string $title = '';

    public string $description = '';

    public string $genre = 'Action Fantasy';

    public string $visualStyle = 'Dark Fantasy Manhwa, sharp digital lines, glowing aura, webtoon keyframe';

    public string $aspectRatio = '16:9';

    public string $voiceName = 'en-US-ChristopherNeural';

    public string $voiceMode = 'multi_actor'; // 'multi_actor' or 'solo_narrator'

    public ?string $ttsRate = '+5%';

    public ?string $ttsPitch = '-5Hz';

    public ?int $imageModelConnectionId = null;

    public ?int $textModelConnectionId = null;

    public array $selectedCharacterIds = [];

    // Character Bible form state
    public ?int $editingCharacterId = null;

    public string $characterName = '';

    public string $characterGender = 'male';

    public ?int $characterAge = 24;

    public string $characterRole = 'protagonist';

    public string $characterAppearance = '';

    public string $characterPromptKeywords = '';

    public string $characterPersonality = '';

    public string $characterColorTheme = '#3B82F6';

    public ?string $characterVoiceId = 'en-US-GuyNeural';

    // Panel Bubble & SFX Editor modal state
    public bool $showBubbleModal = false;

    public ?int $editingBoardId = null;

    public string $bubbleSpeaker = '';

    public string $bubbleText = '';

    public string $bubbleStyle = 'manga';

    public string $bubblePosition = 'top-right';

    public string $bubbleEmotion = 'intense';

    public array $boardBubbles = [];

    // SFX Typography state
    public array $boardSfxTypography = [];

    public string $sfxText = '';

    public string $sfxStyle = 'action_speed';

    public string $sfxColor = '#EF4444';

    public int $sfxRotation = -12;

    public int $boardGutterSpacing = 80;

    public string $pageLayoutHint = 'auto'; // auto, splash, wide_half, small_quarter

    public string $boardAnimationEffect = 'ken_burns';

    public string $boardTransitionType = 'crossfade';

    public string $boardMoodTag = 'action';

    public bool $boardHasParticles = false;

    public ?string $boardParticleType = null;

    // Timeline Keyframing state & Interactive Canvas
    public array $boardKeyframes = [];

    public int $kfTimePct = 50;

    public float $kfZoom = 1.25;

    public int $kfXPct = 50;

    public int $kfYPct = 50;

    public int $kfShake = 0;

    public ?string $editingBoardImageUrl = null;

    // 1-Click Story / EPUB Import modal state
    public bool $showImportModal = false;

    public string $importSource = 'story'; // 'story' or 'epub'

    public ?int $selectedStoryId = null;

    public ?int $selectedEpubId = null;

    public bool $openCreateForm = false;

    #[Url]
    public ?int $template_id = null;

    public function mount(?int $template_id = null): void
    {
        $templateId = $template_id ?? $this->template_id ?? request()->query('template_id');
        if ($templateId) {
            $template = ContentFactoryTemplate::find($templateId);
            if ($template) {
                $this->visualStyle = $template->visual_style;
                $this->genre = $template->genre;
                $this->aspectRatio = $template->aspect_ratio;
                $this->voiceName = $template->voice_name;
                $this->ttsRate = $template->tts_rate;
                $this->ttsPitch = $template->tts_pitch;
                $this->voiceMode = $template->voice_mode ?? 'multi_actor';
                if (! empty($template->example_titles) && is_array($template->example_titles)) {
                    $this->title = $template->example_titles[0];
                } else {
                    $this->title = $template->name;
                }
                $this->description = $template->description;
                $this->openCreateForm = true;
            }
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['titles', 'characters']) ? $tab : 'titles';
    }

    public function createContentTitle(AiModelManagerService $aiManager): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'visualStyle' => 'required|string',
            'aspectRatio' => 'required|in:9:16,16:9',
            'voiceName' => 'required|string',
            'voiceMode' => 'required|in:multi_actor,solo_narrator',
            'imageModelConnectionId' => 'nullable|integer',
            'textModelConnectionId' => 'nullable|integer',
        ]);

        $imageConnection = $this->imageModelConnectionId
            ? AiConnection::find($this->imageModelConnectionId)
            : $aiManager->getDefaultModel('image');

        $textConnection = $this->textModelConnectionId
            ? AiConnection::find($this->textModelConnectionId)
            : $aiManager->getDefaultModel('text');

        $contentTitle = ContentTitle::create([
            'user_id' => Auth::id(),
            'title' => $this->title,
            'description' => $this->description,
            'genre' => $this->genre,
            'visual_style' => $this->visualStyle,
            'aspect_ratio' => $this->aspectRatio,
            'voice_name' => $this->voiceName,
            'voice_mode' => $this->voiceMode,
            'tts_rate' => $this->ttsRate,
            'tts_pitch' => $this->ttsPitch,
            'image_provider' => $imageConnection ? $imageConnection->provider : 'cloudflare',
            'image_connection_id' => $imageConnection?->id,
            'text_connection_id' => $textConnection?->id,
        ]);

        if (! empty($this->selectedCharacterIds)) {
            $contentTitle->characters()->sync($this->selectedCharacterIds);
        }

        Flux::toast(
            variant: 'success',
            text: __('Motion Comic Title created! You can now produce serialized comic episodes.')
        );

        $this->reset(['title', 'description', 'selectedCharacterIds', 'imageModelConnectionId', 'textModelConnectionId']);
    }

    public function generateAiCharacterProfile(CharacterBibleService $bibleService): void
    {
        if (empty(trim($this->characterName))) {
            Flux::toast(variant: 'warning', text: __('Please enter a character name first!'));

            return;
        }

        $profile = $bibleService->generateCharacterProfile($this->characterName, $this->genre, $this->characterRole);
        $this->characterGender = $profile['gender'] ?? 'male';
        $this->characterAge = (int) ($profile['age'] ?? 24);
        $this->characterRole = $profile['role'] ?? 'protagonist';
        $this->characterAppearance = $profile['appearance'] ?? '';
        $this->characterPromptKeywords = $profile['prompt_keywords'] ?? '';
        $this->characterPersonality = $profile['personality'] ?? '';
        $this->characterColorTheme = $profile['color_theme'] ?? '#3B82F6';

        Flux::toast(variant: 'success', text: __('AI generated character bible profile successfully!'));
    }

    public function saveCharacter(): void
    {
        $this->validate([
            'characterName' => 'required|string|max:100',
            'characterGender' => 'required|in:male,female,other',
            'characterAge' => 'nullable|integer|min:1|max:999',
            'characterRole' => 'required|string',
            'characterPromptKeywords' => 'required|string|max:500',
            'characterAppearance' => 'nullable|string|max:1500',
            'characterPersonality' => 'nullable|string|max:1000',
            'characterVoiceId' => 'nullable|string',
            'characterColorTheme' => 'nullable|string|max:50',
        ]);

        $data = [
            'name' => $this->characterName,
            'gender' => $this->characterGender,
            'age' => $this->characterAge,
            'role' => $this->characterRole,
            'prompt_keywords' => $this->characterPromptKeywords,
            'appearance' => $this->characterAppearance,
            'personality' => $this->characterPersonality,
            'voice_id' => $this->characterVoiceId,
            'color_theme' => $this->characterColorTheme,
        ];

        if ($this->editingCharacterId) {
            $character = Character::find($this->editingCharacterId);
            if ($character) {
                $character->update($data);
                Flux::toast(variant: 'success', text: __('Character Bible updated!'));
            }
        } else {
            $data['user_id'] = Auth::id();
            Character::create($data);
            Flux::toast(variant: 'success', text: __('New Character added to World Bible!'));
        }

        $this->resetCharacterForm();
    }

    public function editCharacter(int $id): void
    {
        $character = Character::find($id);
        if ($character) {
            $this->editingCharacterId = $character->id;
            $this->characterName = $character->name;
            $this->characterGender = $character->gender ?? 'male';
            $this->characterAge = $character->age;
            $this->characterRole = $character->role ?? 'protagonist';
            $this->characterAppearance = $character->appearance ?? '';
            $this->characterPromptKeywords = $character->prompt_keywords ?? '';
            $this->characterPersonality = $character->personality ?? '';
            $this->characterVoiceId = $character->voice_id ?? 'en-US-GuyNeural';
            $this->characterColorTheme = $character->color_theme ?? '#3B82F6';
        }
    }

    public function resetCharacterForm(): void
    {
        $this->reset([
            'editingCharacterId',
            'characterName',
            'characterGender',
            'characterAge',
            'characterRole',
            'characterAppearance',
            'characterPromptKeywords',
            'characterPersonality',
            'characterVoiceId',
            'characterColorTheme',
        ]);
        $this->characterGender = 'male';
        $this->characterAge = 24;
        $this->characterRole = 'protagonist';
        $this->characterVoiceId = 'en-US-GuyNeural';
        $this->characterColorTheme = '#3B82F6';
    }

    public function deleteCharacter(int $id): void
    {
        $character = Character::find($id);
        if ($character) {
            $character->delete();
            Flux::toast(variant: 'success', text: __('Character deleted from bible.'));
        }
    }

    public function openBubbleEditor(int $boardId): void
    {
        $board = SceneBoard::find($boardId);
        if (! $board) {
            return;
        }

        $this->editingBoardId = $board->id;
        $this->editingBoardImageUrl = $board->image_url;
        $this->editingBoardAudioUrl = $board->audio_path ? Storage::disk('public')->url($board->audio_path) : null;
        $this->boardDialogueTimeline = is_array($board->dialogue_audio_timeline) ? $board->dialogue_audio_timeline : [];
        $this->boardBubbles = is_array($board->speech_bubbles) ? $board->speech_bubbles : [];
        $this->boardSfxTypography = is_array($board->sfx_typography) ? $board->sfx_typography : [];
        $this->boardKeyframes = is_array($board->camera_keyframes) ? $board->camera_keyframes : [];
        $this->boardGutterSpacing = $board->gutter_spacing ?? 80;
        $this->pageLayoutHint = $board->page_layout_hint ?? 'auto';
        $this->boardAnimationEffect = $board->animation_effect ?? 'ken_burns';
        $this->boardTransitionType = $board->transition_type ?? 'crossfade';
        $this->boardMoodTag = $board->mood_tag ?? 'action';
        $this->boardHasParticles = (bool) $board->has_particles;
        $this->boardParticleType = $board->particle_type;

        $this->bubbleSpeaker = ! empty($board->characters_present) ? $board->characters_present[0] : '';
        $this->bubbleText = '';
        $this->bubbleStyle = 'manga';
        $this->bubblePosition = 'top-right';
        $this->bubbleEmotion = 'neutral';

        $this->sfxText = '';
        $this->sfxStyle = 'action_speed';
        $this->sfxColor = '#EF4444';
        $this->sfxRotation = -12;

        $this->kfTimePct = 50;
        $this->kfZoom = 1.25;
        $this->kfXPct = 50;
        $this->kfYPct = 50;
        $this->kfShake = 0;

        $this->showBubbleModal = true;
    }

    public function addBubbleToBoard(): void
    {
        if (empty(trim($this->bubbleText))) {
            Flux::toast(variant: 'warning', text: __('Please enter dialogue text!'));

            return;
        }

        $this->boardBubbles[] = [
            'speaker' => trim($this->bubbleSpeaker),
            'text' => trim($this->bubbleText),
            'style' => $this->bubbleStyle,
            'position' => $this->bubblePosition,
            'emotion' => $this->bubbleEmotion ?: 'neutral',
            'x_pct' => 50,
            'y_pct' => 30,
        ];

        $this->bubbleText = '';
        Flux::toast(variant: 'success', text: __('Bubble dialogue line added!'));
    }

    public function updateBubbleCoords(int $index, float|int $xPct, float|int $yPct): void
    {
        if (isset($this->boardBubbles[$index])) {
            $this->boardBubbles[$index]['x_pct'] = round(max(5, min(95, (float) $xPct)), 1);
            $this->boardBubbles[$index]['y_pct'] = round(max(5, min(95, (float) $yPct)), 1);
        }
    }

    public function removeBubble(int $index): void
    {
        if (isset($this->boardBubbles[$index])) {
            unset($this->boardBubbles[$index]);
            $this->boardBubbles = array_values($this->boardBubbles);
        }
    }

    public function addSfxToBoard(): void
    {
        if (empty(trim($this->sfxText))) {
            Flux::toast(variant: 'warning', text: __('Please enter SFX text!'));

            return;
        }

        $this->boardSfxTypography[] = [
            'text' => trim($this->sfxText),
            'style' => $this->sfxStyle,
            'color' => $this->sfxColor,
            'rotation' => (int) $this->sfxRotation,
            'x_pct' => 50,
            'y_pct' => 50,
            'x' => '50%',
            'y' => '50%',
            'size' => 64,
        ];

        $this->sfxText = '';
        Flux::toast(variant: 'success', text: __('SFX Typography lettering added!'));
    }

    public function updateSfxCoords(int $index, float|int $xPct, float|int $yPct): void
    {
        if (isset($this->boardSfxTypography[$index])) {
            $xClamped = round(max(5, min(95, (float) $xPct)), 1);
            $yClamped = round(max(5, min(95, (float) $yPct)), 1);
            $this->boardSfxTypography[$index]['x_pct'] = $xClamped;
            $this->boardSfxTypography[$index]['y_pct'] = $yClamped;
            $this->boardSfxTypography[$index]['x'] = "{$xClamped}%";
            $this->boardSfxTypography[$index]['y'] = "{$yClamped}%";
        }
    }

    public function removeSfx(int $index): void
    {
        if (isset($this->boardSfxTypography[$index])) {
            unset($this->boardSfxTypography[$index]);
            $this->boardSfxTypography = array_values($this->boardSfxTypography);
        }
    }

    public function addKeyframe(): void
    {
        $this->boardKeyframes[] = [
            'time_pct' => max(0, min(100, (int) $this->kfTimePct)),
            'zoom' => round(max(1.0, min(3.0, (float) $this->kfZoom)), 2),
            'x_pct' => max(0, min(100, (int) $this->kfXPct)),
            'y_pct' => max(0, min(100, (int) $this->kfYPct)),
            'shake' => max(0, min(20, (int) $this->kfShake)),
        ];

        usort($this->boardKeyframes, fn ($a, $b) => ($a['time_pct'] ?? 0) <=> ($b['time_pct'] ?? 0));
        Flux::toast(variant: 'success', text: __('Camera keyframe added to timeline!'));
    }

    public function removeKeyframe(int $index): void
    {
        if (isset($this->boardKeyframes[$index])) {
            unset($this->boardKeyframes[$index]);
            $this->boardKeyframes = array_values($this->boardKeyframes);
        }
    }

    public function saveBoardConfig(): void
    {
        $board = SceneBoard::find($this->editingBoardId);
        if ($board) {
            $board->update([
                'speech_bubbles' => $this->boardBubbles,
                'sfx_typography' => $this->boardSfxTypography,
                'camera_keyframes' => ! empty($this->boardKeyframes) ? $this->boardKeyframes : null,
                'gutter_spacing' => $this->boardGutterSpacing,
                'page_layout_hint' => $this->pageLayoutHint,
                'animation_effect' => $this->boardAnimationEffect,
                'transition_type' => $this->boardTransitionType,
                'mood_tag' => $this->boardMoodTag,
                'has_particles' => $this->boardHasParticles,
                'particle_type' => $this->boardParticleType,
            ]);

            // Dispatch speech bubble burn job if bubbles or SFX exist and panel has image
            if ((! empty($this->boardBubbles) || ! empty($this->boardSfxTypography)) && $board->image_path) {
                GenerateSpeechBubbleJob::dispatch($board);
                Flux::toast(variant: 'success', text: __('Panel config saved and comic lettering queued for rendering!'));
            } else {
                Flux::toast(variant: 'success', text: __('Panel configuration updated!'));
            }
        }

        $this->showBubbleModal = false;
        $this->editingBoardId = null;
    }

    public function synthesizeBoardVoice(int $boardId, MultiActorVoiceSynthesisService $voiceService): void
    {
        $board = SceneBoard::find($boardId);
        if (! $board) {
            return;
        }

        // Save current bubbles and config before synthesizing
        $board->update([
            'speech_bubbles' => $this->boardBubbles,
            'sfx_typography' => $this->boardSfxTypography,
        ]);

        try {
            $result = $voiceService->synthesizeBoardDialogue($board);
            $this->editingBoardAudioUrl = Storage::disk('public')->url($result['audio_path']);
            $this->boardDialogueTimeline = $result['timeline'];
            Flux::toast(variant: 'success', text: __('Multi-actor dialogue synthesized with emotional pacing!'));
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: __('Voice synthesis failed: ').$e->getMessage());
        }
    }

    public function synthesizeEpisodeVoices(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        $boards = $episode->sceneBoards()->get();
        foreach ($boards as $board) {
            GenerateMultiActorVoiceJob::dispatch($board);
        }

        Flux::toast(variant: 'success', text: __('Voice acting generation queued for all panels in episode!'));
    }

    public function toggleEpisodeVoiceMode(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        $currentMode = $episode->getEffectiveVoiceMode();
        $newMode = $currentMode === 'multi_actor' ? 'solo_narrator' : 'multi_actor';
        $episode->update(['voice_mode' => $newMode]);

        Flux::toast(
            variant: 'success',
            text: $newMode === 'multi_actor'
                ? __('Episode voice mode switched to Multi-Actor Voice Drama!')
                : __('Episode voice mode switched to Solo 3rd Person Narrator!')
        );
    }

    public function renderMotionComic(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        RenderMotionComicJob::dispatch($episode);
        GenerateThumbnailAssetsJob::dispatch($episode);

        Flux::toast(
            variant: 'success',
            text: __('Motion Comic render queued! Animation effects and audio mix are processing in background.')
        );
    }

    public function generateWebtoonStrip(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        GenerateWebtoonStripJob::dispatch($episode);

        Flux::toast(
            variant: 'success',
            text: __('Webtoon strip stitching queued! Slices (800x1280px) are generating in background.')
        );
    }

    public function exportWebtoonPack(int $episodeId): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        ExportWebtoonPackJob::dispatch($episode);

        Flux::toast(
            variant: 'success',
            text: __('Webtoon publishing pack export queued! ZIP pack for Canvas & Tapas is compiling.')
        );
    }

    public function generateMangaPages(int $episodeId, string $direction = 'rtl'): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        GenerateMangaPagesJob::dispatch($episode, $direction);

        Flux::toast(
            variant: 'success',
            text: __('Manga multi-panel pages generation queued! Pages are rendering in background.')
        );
    }

    public function exportComicArchive(int $episodeId, string $format = 'both'): void
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            return;
        }

        ExportComicArchiveJob::dispatch($episode, $format);

        Flux::toast(
            variant: 'success',
            text: __('Comic Book Archive (.cbz & .pdf) export queued!')
        );
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    public function importFromStoryOrEpub(StoryToComicConverterService $converter): void
    {
        if ($this->importSource === 'story') {
            if (! $this->selectedStoryId) {
                Flux::toast(variant: 'warning', text: __('Please select a Story to convert!'));

                return;
            }

            $story = Story::find($this->selectedStoryId);
            if ($story) {
                $converter->convertStoryToComic($story);
                Flux::toast(variant: 'success', text: __('Story converted to Comic Studio format with speech bubbles!'));
            }
        } else {
            if (! $this->selectedEpubId) {
                Flux::toast(variant: 'warning', text: __('Please select an EPUB novel to convert!'));

                return;
            }

            $epub = EpubProject::find($this->selectedEpubId);
            if ($epub) {
                $converter->convertEpubProjectToComic($epub);
                Flux::toast(variant: 'success', text: __('EPUB novel successfully converted to Comic Title and Panels!'));
            }
        }

        $this->showImportModal = false;
        $this->reset(['selectedStoryId', 'selectedEpubId']);
    }

    public function render(AiModelManagerService $aiManager)
    {
        $contentTitles = ContentTitle::with(['characters', 'stories.episodes.boards'])
            ->latest()
            ->get();

        $characters = Character::latest()->get();
        $voices = TextToSpeechService::getAvailableVoices();
        $availableImageModels = $aiManager->getAvailableModels('image', Auth::id());
        $availableTextModels = $aiManager->getAvailableModels('text', Auth::id());
        $availableStories = Story::latest()->take(20)->get();
        $availableEpubs = EpubProject::latest()->take(20)->get();

        return view('livewire.comic-studio', [
            'contentTitles' => $contentTitles,
            'characters' => $characters,
            'voices' => $voices,
            'availableImageModels' => $availableImageModels,
            'availableTextModels' => $availableTextModels,
            'availableStories' => $availableStories,
            'availableEpubs' => $availableEpubs,
        ]);
    }
}
