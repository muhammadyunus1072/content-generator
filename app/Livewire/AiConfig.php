<?php

namespace App\Livewire;

use App\Models\AiConnection;
use App\Services\Ai\AiModelManagerService;
use App\Services\Ai\Providers\DockerReplicateImageProvider;
use App\Services\TextToSpeechService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AiConfig extends Component
{
    public string $activeCategory = 'text'; // 'text', 'image', 'audio_tts', 'audio_stt'

    // Form / Modal State
    public bool $showModal = false;

    public ?int $editingConnectionId = null;

    public string $formName = '';

    public string $formCategory = 'text';

    public string $formProvider = 'gemini';

    public string $formModelId = '';

    public string $formApiKey = '';

    public string $formBaseUrl = '';

    public bool $formIsDefault = false;

    public bool $formIsActive = true;

    public ?int $formNumSteps = 20;

    public ?float $formStrength = 0.75;

    public ?float $formGuidance = 7.5;

    public ?string $formApiSchema = '';

    // Testing state
    public array $testingStatus = []; // connection_id => ['testing' => bool, 'status' => string, 'message' => string]

    // Voice Catalog showcase state
    public string $voiceCategoryFilter = 'all';

    public string $voiceSearch = '';

    public function setCategory(string $category): void
    {
        $this->activeCategory = in_array($category, ['text', 'image', 'audio_tts', 'audio_stt']) ? $category : 'text';
    }

    public function setVoiceCategoryFilter(string $category): void
    {
        $this->voiceCategoryFilter = $category;
    }

    public function selectVoiceAsDefault(string $voiceKey, string $voiceName, AiModelManagerService $manager): void
    {
        $defaultTts = $manager->getDefaultModel('audio_tts', Auth::id());

        if ($defaultTts) {
            $defaultTts->update([
                'name' => "Edge-TTS - {$voiceName}",
                'model_id' => $voiceKey,
            ]);
            $manager->setDefaultConnection($defaultTts);
        } else {
            $conn = AiConnection::create([
                'user_id' => Auth::id(),
                'name' => "Edge-TTS - {$voiceName}",
                'category' => 'audio_tts',
                'provider' => 'edge_tts',
                'model_id' => $voiceKey,
                'is_default' => true,
                'is_active' => true,
            ]);
            $manager->setDefaultConnection($conn);
        }

        Flux::toast(variant: 'success', text: "{$voiceName} is now set as your primary Voice Narrator!");
    }

    public function openNewModal(?string $category = null): void
    {
        $this->resetForm();
        $this->formCategory = $category ?: $this->activeCategory;
        $this->showModal = true;
    }

    public function applyPreset(string $presetName, AiModelManagerService $manager): void
    {
        $presets = $manager->getPresetTemplates()[$this->activeCategory] ?? [];
        foreach ($presets as $preset) {
            if ($preset['name'] === $presetName) {
                $this->formName = $preset['name'];
                $this->formCategory = $this->activeCategory;
                $this->formProvider = $preset['provider'];
                $this->formModelId = $preset['model_id'];
                $this->formBaseUrl = $preset['base_url'] ?? '';
                $this->formIsDefault = $preset['is_default'] ?? false;
                $this->formIsActive = true;
                if (! empty($preset['options'])) {
                    $this->formNumSteps = $preset['options']['num_steps'] ?? 20;
                    $this->formGuidance = $preset['options']['guidance'] ?? 7.0;
                }
                if (! empty($preset['api_schema'])) {
                    $this->formApiSchema = json_encode($preset['api_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                } else {
                    $this->formApiSchema = '';
                }
                $this->showModal = true;
                break;
            }
        }
    }

    public function updatedFormModelId(string $value): void
    {
        if ($this->formProvider === 'cloudflare' && empty($this->formApiSchema)) {
            $manager = app(AiModelManagerService::class);
            $schema = $manager->getDefaultSchemaForModel($value);
            $this->formApiSchema = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function editConnection(int $id): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $this->editingConnectionId = $conn->id;
            $this->formName = $conn->name;
            $this->formCategory = $conn->category;
            $this->formProvider = $conn->provider;
            $this->formModelId = $conn->model_id;
            $this->formApiKey = $conn->api_key ?? '';
            $this->formBaseUrl = $conn->base_url ?? '';
            $this->formIsDefault = $conn->is_default;
            $this->formIsActive = $conn->is_active;
            $this->formNumSteps = $conn->options['num_steps'] ?? $conn->options['steps'] ?? 20;
            $this->formStrength = isset($conn->options['strength']) ? (float) $conn->options['strength'] : 0.75;
            $this->formGuidance = isset($conn->options['guidance']) ? (float) $conn->options['guidance'] : 7.5;
            if (! empty($conn->options['api_schema'])) {
                $this->formApiSchema = json_encode($conn->options['api_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } elseif ($conn->provider === 'cloudflare') {
                $manager = app(AiModelManagerService::class);
                $this->formApiSchema = json_encode($manager->getDefaultSchemaForModel($conn->model_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                $this->formApiSchema = '';
            }
            $this->showModal = true;
        }
    }

    public function saveConnection(AiModelManagerService $manager): void
    {
        $this->validate([
            'formName' => 'required|string|max:100',
            'formCategory' => 'required|in:text,image,audio_tts,audio_stt',
            'formProvider' => 'required|string',
            'formModelId' => 'required|string|max:150',
            'formBaseUrl' => 'nullable|string|max:255',
            'formNumSteps' => 'nullable|integer|min:1|max:100',
            'formStrength' => 'nullable|numeric|min:0|max:1',
            'formGuidance' => 'nullable|numeric|min:1|max:20',
        ]);

        $options = [];
        if ($this->formCategory === 'image') {
            $options['num_steps'] = (int) ($this->formNumSteps ?: 20);
            $options['strength'] = (float) ($this->formStrength ?? 0.75);
            $options['guidance'] = (float) ($this->formGuidance ?? 7.5);
        }

        if (! empty($this->formApiSchema)) {
            $decoded = json_decode($this->formApiSchema, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('formApiSchema', 'Invalid JSON Schema syntax.');

                return;
            }
            $options['api_schema'] = $decoded;
        }

        $normalizedModelId = trim($this->formModelId);
        if ($this->formProvider === 'cloudflare' && str_starts_with($normalizedModelId, 'cf/')) {
            $normalizedModelId = '@'.$normalizedModelId;
        }

        if ($this->editingConnectionId) {
            $conn = AiConnection::find($this->editingConnectionId);
            if ($conn) {
                $updateData = [
                    'name' => $this->formName,
                    'category' => $this->formCategory,
                    'provider' => $this->formProvider,
                    'model_id' => $normalizedModelId,
                    'base_url' => $this->formBaseUrl,
                    'is_default' => $this->formIsDefault,
                    'is_active' => $this->formIsActive,
                ];

                if ($this->formCategory === 'image') {
                    $existingOptions = $conn->options ?? [];
                    $updateData['options'] = array_merge($existingOptions, $options);
                }

                if (! empty($this->formApiKey)) {
                    $updateData['api_key'] = $this->formApiKey;
                }

                $conn->update($updateData);

                if ($this->formIsDefault) {
                    $manager->setDefaultConnection($conn);
                }

                Flux::toast(variant: 'success', text: __('AI Connection updated!'));
            }
        } else {
            $conn = AiConnection::create([
                'user_id' => Auth::id(),
                'name' => $this->formName,
                'category' => $this->formCategory,
                'provider' => $this->formProvider,
                'model_id' => $normalizedModelId,
                'api_key' => $this->formApiKey ?: null,
                'base_url' => $this->formBaseUrl ?: null,
                'options' => $options,
                'is_default' => $this->formIsDefault,
                'is_active' => $this->formIsActive,
            ]);

            if ($this->formIsDefault) {
                $manager->setDefaultConnection($conn);
            }

            Flux::toast(variant: 'success', text: __('New AI model connection configured!'));
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function testConnection(int $id, AiModelManagerService $manager): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $this->testingStatus[$id] = ['testing' => true, 'status' => '', 'message' => 'Testing connection...'];

            $result = $manager->testConnection($conn);

            $this->testingStatus[$id] = [
                'testing' => false,
                'status' => $result['status'],
                'message' => $result['message'],
            ];

            if ($result['status'] === 'success') {
                Flux::toast(variant: 'success', text: $conn->name.': '.__('Connection test passed!').' ('.$result['latency_ms'].'ms)');
            } else {
                Flux::toast(variant: 'danger', text: $conn->name.': '.__('Connection test failed: ').$result['message']);
            }
        }
    }

    public function warmupConnection(int $id, AiModelManagerService $manager): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $this->testingStatus[$id] = ['testing' => true, 'status' => '', 'message' => 'Warming up GPU weights...'];

            if (in_array($conn->provider, ['docker_cog', 'docker_replicate'])) {
                /** @var DockerReplicateImageProvider $provider */
                $provider = app(DockerReplicateImageProvider::class);
                $result = $provider->warmupInference($conn);
            } else {
                $result = $manager->testConnection($conn);
            }

            $this->testingStatus[$id] = [
                'testing' => false,
                'status' => $result['status'],
                'message' => $result['message'],
            ];

            if ($result['status'] === 'success') {
                Flux::toast(variant: 'success', text: $conn->name.': '.$result['message']);
            } else {
                Flux::toast(variant: 'danger', text: $conn->name.': '.$result['message']);
            }
        }
    }

    public function makeDefault(int $id, AiModelManagerService $manager): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $manager->setDefaultConnection($conn);
            Flux::toast(variant: 'success', text: $conn->name.' '.__('is now the default model for ').$conn->category);
        }
    }

    public function toggleActive(int $id): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $conn->update(['is_active' => ! $conn->is_active]);
            Flux::toast(variant: 'success', text: __('Model status updated.'));
        }
    }

    public function deleteConnection(int $id): void
    {
        $conn = AiConnection::find($id);
        if ($conn) {
            $conn->delete();
            Flux::toast(variant: 'success', text: __('Connection deleted.'));
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingConnectionId',
            'formName',
            'formModelId',
            'formApiKey',
            'formBaseUrl',
            'formIsDefault',
            'formApiSchema',
        ]);
        $this->formCategory = $this->activeCategory;
        $this->formProvider = 'gemini';
        $this->formIsActive = true;
    }

    public function render(AiModelManagerService $manager)
    {
        $connections = $manager->getAvailableModels($this->activeCategory, Auth::id());
        $presets = $manager->getPresetTemplates()[$this->activeCategory] ?? [];
        $defaultTtsConnection = $manager->getDefaultModel('audio_tts', Auth::id());

        // Process Voice Catalog if on audio_tts tab
        $allVoices = TextToSpeechService::getDetailedVoiceCatalog();
        $filteredVoices = collect($allVoices)->filter(function ($voice) {
            if ($this->voiceCategoryFilter !== 'all' && $voice['category'] !== $this->voiceCategoryFilter) {
                return false;
            }

            if (! empty($this->voiceSearch)) {
                $search = strtolower($this->voiceSearch);

                return str_contains(strtolower($voice['name']), $search) ||
                       str_contains(strtolower($voice['language']), $search) ||
                       str_contains(strtolower($voice['personality']), $search) ||
                       str_contains(strtolower($voice['key']), $search);
            }

            return true;
        })->values()->all();

        return view('livewire.ai-config', [
            'connections' => $connections,
            'presets' => $presets,
            'voiceCatalog' => $filteredVoices,
            'defaultTtsConnection' => $defaultTtsConnection,
        ]);
    }
}
