<?php

namespace App\Livewire;

use App\Models\AiConnection;
use App\Models\Character;
use App\Models\ContentFactoryTemplate;
use App\Models\ContentTitle;
use App\Services\Ai\AiModelManagerService;
use App\Services\TextToSpeechService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class StoryStudio extends Component
{
    public string $tab = 'titles'; // 'titles' or 'characters'

    // Content Title creation form
    public string $title = '';

    public string $description = '';

    public string $genre = 'Action Fantasy';

    public string $visualStyle = 'Dark Fantasy Manhwa';

    public string $aspectRatio = '9:16';

    public string $voiceName = 'id-ID-ArdiNeural';

    public ?string $ttsRate = null;

    public ?string $ttsPitch = null;

    public ?int $imageModelConnectionId = null;

    public ?int $textModelConnectionId = null;

    public array $selectedCharacterIds = [];

    // Edit Content Title form state
    public bool $showEditTitleModal = false;

    public ?int $editingTitleId = null;

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editGenre = 'Action Fantasy';

    public string $editVisualStyle = 'Dark Fantasy Manhwa';

    public string $editAspectRatio = '9:16';

    public string $editVoiceName = 'id-ID-ArdiNeural';

    public ?string $editTtsRate = null;

    public ?string $editTtsPitch = null;

    public ?int $editImageModelConnectionId = null;

    public ?int $editTextModelConnectionId = null;

    public array $editSelectedCharacterIds = [];

    // Character form
    public ?int $editingCharacterId = null;

    public string $characterName = '';

    public string $characterGender = 'female';

    public ?int $characterAge = 23;

    public string $characterAppearance = '';

    public string $characterPromptKeywords = '';

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
            text: __('Content Title created! You can now add episode stories.')
        );

        $this->reset(['title', 'description', 'selectedCharacterIds', 'imageModelConnectionId', 'textModelConnectionId']);
    }

    public function openEditContentTitle(int $id): void
    {
        $contentTitle = ContentTitle::with('characters')->find($id);
        if ($contentTitle) {
            $this->editingTitleId = $contentTitle->id;
            $this->editTitle = $contentTitle->title;
            $this->editDescription = $contentTitle->description ?? '';
            $this->editGenre = $contentTitle->genre ?? 'Action Fantasy';
            $this->editVisualStyle = $contentTitle->visual_style ?? 'Dark Fantasy Manhwa';
            $this->editAspectRatio = $contentTitle->aspect_ratio ?? '9:16';
            $this->editVoiceName = $contentTitle->voice_name ?? 'id-ID-ArdiNeural';
            $this->editTtsRate = $contentTitle->tts_rate;
            $this->editTtsPitch = $contentTitle->tts_pitch;
            $this->editImageModelConnectionId = $contentTitle->image_connection_id;
            $this->editTextModelConnectionId = $contentTitle->text_connection_id;
            $this->editSelectedCharacterIds = $contentTitle->characters->pluck('id')->toArray();
            $this->showEditTitleModal = true;
        }
    }

    public function updateContentTitle(AiModelManagerService $aiManager): void
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'editVisualStyle' => 'required|string',
            'editAspectRatio' => 'required|in:9:16,16:9',
            'editVoiceName' => 'required|string',
            'editImageModelConnectionId' => 'nullable|integer',
            'editTextModelConnectionId' => 'nullable|integer',
        ]);

        $contentTitle = ContentTitle::find($this->editingTitleId);
        if (! $contentTitle) {
            return;
        }

        $imageConnection = $this->editImageModelConnectionId
            ? AiConnection::find($this->editImageModelConnectionId)
            : $aiManager->getDefaultModel('image');

        $textConnection = $this->editTextModelConnectionId
            ? AiConnection::find($this->editTextModelConnectionId)
            : $aiManager->getDefaultModel('text');

        $contentTitle->update([
            'title' => $this->editTitle,
            'description' => $this->editDescription,
            'genre' => $this->editGenre,
            'visual_style' => $this->editVisualStyle,
            'aspect_ratio' => $this->editAspectRatio,
            'voice_name' => $this->editVoiceName,
            'tts_rate' => $this->editTtsRate,
            'tts_pitch' => $this->editTtsPitch,
            'image_provider' => $imageConnection ? $imageConnection->provider : ($contentTitle->image_provider ?: 'cloudflare'),
            'image_connection_id' => $imageConnection?->id,
            'text_connection_id' => $textConnection?->id,
        ]);

        $contentTitle->characters()->sync($this->editSelectedCharacterIds);

        Flux::toast(variant: 'success', text: __('Content Title updated successfully!'));
        $this->showEditTitleModal = false;
        $this->editingTitleId = null;
    }

    public function closeEditContentTitleModal(): void
    {
        $this->showEditTitleModal = false;
        $this->editingTitleId = null;
    }

    public function deleteContentTitle(int $id): void
    {
        $contentTitle = ContentTitle::find($id);
        if ($contentTitle) {
            $contentTitle->delete();
            Flux::toast(
                variant: 'success',
                text: __('Content Title deleted successfully.')
            );
        }
    }

    public function saveCharacter(): void
    {
        $this->validate([
            'characterName' => 'required|string|max:100',
            'characterGender' => 'required|in:male,female,other',
            'characterAge' => 'nullable|integer|min:1|max:999',
            'characterPromptKeywords' => 'required|string|max:500',
            'characterAppearance' => 'nullable|string|max:1500',
        ]);

        if ($this->editingCharacterId) {
            $character = Character::find($this->editingCharacterId);
            if ($character) {
                $character->update([
                    'name' => $this->characterName,
                    'gender' => $this->characterGender,
                    'age' => $this->characterAge,
                    'prompt_keywords' => $this->characterPromptKeywords,
                    'appearance' => $this->characterAppearance,
                ]);
                Flux::toast(variant: 'success', text: __('Character updated successfully!'));
            }
        } else {
            Character::create([
                'user_id' => Auth::id(),
                'name' => $this->characterName,
                'gender' => $this->characterGender,
                'age' => $this->characterAge,
                'prompt_keywords' => $this->characterPromptKeywords,
                'appearance' => $this->characterAppearance,
            ]);
            Flux::toast(variant: 'success', text: __('Character sheet saved!'));
        }

        $this->resetCharacterForm();
    }

    public function editCharacter(int $id): void
    {
        $character = Character::find($id);
        if ($character) {
            $this->editingCharacterId = $character->id;
            $this->characterName = $character->name;
            $this->characterGender = $character->gender ?? 'female';
            $this->characterAge = $character->age;
            $this->characterAppearance = $character->appearance ?? '';
            $this->characterPromptKeywords = $character->prompt_keywords ?? '';
        }
    }

    public function resetCharacterForm(): void
    {
        $this->reset([
            'editingCharacterId',
            'characterName',
            'characterGender',
            'characterAge',
            'characterAppearance',
            'characterPromptKeywords',
        ]);
        $this->characterGender = 'female';
        $this->characterAge = 23;
    }

    public function deleteCharacter(int $id): void
    {
        $character = Character::find($id);
        if ($character) {
            $character->delete();
            Flux::toast(variant: 'success', text: __('Character deleted.'));
        }
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

        return view('livewire.story-studio', [
            'contentTitles' => $contentTitles,
            'characters' => $characters,
            'voices' => $voices,
            'availableImageModels' => $availableImageModels,
            'availableTextModels' => $availableTextModels,
        ]);
    }
}
