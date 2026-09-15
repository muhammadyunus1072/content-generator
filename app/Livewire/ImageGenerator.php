<?php

namespace App\Livewire;

use App\Models\AiConnection;
use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Prop;
use App\Services\Ai\AiModelManagerService;
use App\Services\PromptCompilerService;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImageGenerator extends Component
{
    use WithFileUploads;

    public string $prompt = 'A futuristic city in the clouds';

    public string $negativePrompt = '';

    public string $style = 'none';

    public string $aspectRatio = '16:9';

    public float $strength = 0.75;

    public float $guidance = 7.5;

    public ?int $selectedConnectionId = null;

    public $referenceImage = null;

    public ?string $generatedImageUrl = null;

    public ?string $errorMessage = null;

    public array $generationHistory = [];

    // Prompt Engine Mode: 'director' (structured template engine) or 'raw'
    public string $promptMode = 'director';

    // Global Foundation (Brain Key)
    public ?int $selectedContentTitleId = null;

    public string $level1 = 'Dark Fantasy Manhwa, sharp dynamic lineart, cel-shaded with painterly textures';

    public string $level2 = 'Hunter Apocalypse / Reincarnation, grim dungeon vibe';

    public string $level5 = 'Unreal Engine 5 render, raytracing, dynamic rim lighting, 8k, masterpiece';

    // Dynamic Scene Directing (Level 3 & 4)
    public string $focusType = 'single_character'; // single_character, interaction, body_part, prop, environment, crowd

    public string $shotFraming = 'dynamic mid-shot action scene';

    public string $primarySubject = 'Sung Jin, sharp jawline, glowing violet eyes, black trenchcoat';

    public string $secondarySubjects = '';

    public string $actionDescription = 'holding curved daggers as shadow energy crackles along the blades';

    public string $environment = 'Ruined Throne Room of the Sunken Citadel, crumbling stone pillars';

    public string $crowdDensity = 'none'; // none, sparse, dense, bustling

    public string $lightingMood = 'Midnight, high contrast, dramatic shadows, purple bioluminescence';

    // Live compiled preview
    public string $compiledPrompt = '';

    public function mount(AiModelManagerService $aiManager): void
    {
        $defaultModel = $aiManager->getDefaultModel('image', Auth::id());
        if ($defaultModel) {
            $this->selectedConnectionId = $defaultModel->id;
            if (isset($defaultModel->options['strength'])) {
                $this->strength = (float) $defaultModel->options['strength'];
            }
            if (isset($defaultModel->options['guidance'])) {
                $this->guidance = (float) $defaultModel->options['guidance'];
            }
        }

        // Try to preselect the user's latest ContentTitle if available
        $latestTitle = ContentTitle::where('user_id', Auth::id())->latest()->first();
        if ($latestTitle) {
            $this->selectedContentTitleId = $latestTitle->id;
            $this->applyContentTitleSettings($latestTitle);
        }

        $this->recompilePrompt();
    }

    public function updated($propertyName): void
    {
        $engineFields = [
            'selectedContentTitleId',
            'level1',
            'level2',
            'level5',
            'focusType',
            'shotFraming',
            'primarySubject',
            'secondarySubjects',
            'actionDescription',
            'environment',
            'crowdDensity',
            'lightingMood',
            'promptMode',
        ];

        if (in_array($propertyName, $engineFields, true)) {
            $this->recompilePrompt();
        }
    }

    public function updatedSelectedContentTitleId(?int $value): void
    {
        if ($value) {
            $title = ContentTitle::find($value);
            if ($title) {
                $this->applyContentTitleSettings($title);
            }
        }
        $this->recompilePrompt();
    }

    private function applyContentTitleSettings(ContentTitle $title): void
    {
        $config = $title->prompt_config ?? [];
        if (! empty($config['level1'])) {
            $this->level1 = $config['level1'];
        } elseif (! empty($title->visual_style)) {
            $this->level1 = $title->visual_style;
        }

        if (! empty($config['level2'])) {
            $this->level2 = $config['level2'];
        } elseif (! empty($title->genre)) {
            $this->level2 = $title->genre;
        }

        if (! empty($config['level5'])) {
            $this->level5 = $config['level5'];
        }

        if (! empty($title->aspect_ratio)) {
            $this->aspectRatio = $title->aspect_ratio;
        }

        if ($title->image_connection_id) {
            $this->selectedConnectionId = $title->image_connection_id;
        }

        // Prefill subjects if available
        $firstChar = $title->characters()->first();
        if ($firstChar) {
            $this->primarySubject = $firstChar->name;
        }

        $firstEnv = $title->environments()->first();
        if ($firstEnv) {
            $this->environment = $firstEnv->name;
        }
    }

    public function recompilePrompt(): void
    {
        $compiler = app(PromptCompilerService::class);

        $contentTitle = $this->selectedContentTitleId
            ? ContentTitle::find($this->selectedContentTitleId)
            : null;

        $sceneData = [
            'focus_type' => $this->focusType,
            'shot_framing' => $this->shotFraming,
            'primary_subject_id' => $this->primarySubject,
            'secondary_subject_ids' => ! empty($this->secondarySubjects) ? array_map('trim', explode(',', $this->secondarySubjects)) : [],
            'action_description' => $this->actionDescription,
            'environment_id' => $this->environment,
            'crowd_density' => $this->crowdDensity,
            'lighting_mood_override' => $this->lightingMood,
        ];

        $customBase = [
            'level1' => $this->level1,
            'level2' => $this->level2,
            'level5' => $this->level5,
        ];

        $this->compiledPrompt = $compiler->compilePrompt($contentTitle, $sceneData, $customBase);

        if ($this->promptMode === 'director') {
            $this->prompt = $this->compiledPrompt;
        }
    }

    public function loadPreset(string $presetKey): void
    {
        match ($presetKey) {
            'manhwa_action' => $this->setPresetData(
                focusType: 'interaction',
                shotFraming: 'dynamic mid-shot action scene',
                primarySubject: 'Arthur, silver-trimmed black battle tunic, glowing azure runic blade',
                secondarySubjects: 'High Orc Chieftain in blood-red bone armor with massive spiked club',
                actionDescription: 'clashing blade against club with sparks and mana shockwaves bursting violently',
                environment: 'Ruined Temple Courtyard, cracked stone pillars and floating embers',
                crowdDensity: 'sparse',
                lightingMood: 'Dramatic chiaroscuro, cyan mana glare contrasting fiery red torchlight',
                level1: 'Digital Manhwa Webtoon illustration, crisp dynamic lineart, vibrant cel-shading',
                level2: 'Dark Fantasy Hunter Dungeon, high-stakes apocalypse',
                level5: 'Unreal Engine 5 render, raytracing, dynamic rim lighting, 8k, masterpiece'
            ),
            'macro_eyes' => $this->setPresetData(
                focusType: 'body_part',
                shotFraming: 'extreme macro close-up on glowing eyes',
                primarySubject: 'Sung Jin, piercing luminous violet eyes with ethereal shadow mist billowing from pupils',
                secondarySubjects: '',
                actionDescription: 'widening with chilling murderous intent, ancient purple runes reflected in the iris',
                environment: 'Abyssal Darkness',
                crowdDensity: 'none',
                lightingMood: 'Pitch black background, high-contrast luminous violet eye glow cutting through darkness',
                level1: 'Modern Anime & Manhwa aesthetic, hyper-detailed linework, crisp iris reflections',
                level2: 'Shadow Monarch Reincarnation, supernatural dread',
                level5: 'macro depth of field, volumetric fog, particle effects, 8k, masterpiece'
            ),
            'macro_fist' => $this->setPresetData(
                focusType: 'body_part',
                shotFraming: 'extreme close-up on clenching fist',
                primarySubject: 'Fallen Knight, leather-wrapped gauntlet with cracked obsidian armor plate',
                secondarySubjects: '',
                actionDescription: 'clenching fist tightly until glowing golden mana veins erupt along the knuckles',
                environment: 'Scorched battlefield covered in soot and rain',
                crowdDensity: 'none',
                lightingMood: 'Dim overcast storm lighting, vivid golden electricity arcing between fingers',
                level1: 'Dark Fantasy Manhwa, heavy textured ink hatching, grim graphic novel vibe',
                level2: 'Souls-like grim apocalypse, ancient fallen kingdom',
                level5: '8k resolution, photorealistic metallic textures, macro lens bokeh, masterpiece'
            ),
            'bustling_crowd' => $this->setPresetData(
                focusType: 'crowd',
                shotFraming: 'wide cinematic tracking shot',
                primarySubject: 'Kael, mysterious hooded wanderer with a sheathed dragonbone katana',
                secondarySubjects: '',
                actionDescription: 'weaving swiftly through the packed crowd, glancing back over his shoulder',
                environment: 'Grand Bazaar of Oakhaven, colorful fabric canopies, smoking street food stalls',
                crowdDensity: 'bustling',
                lightingMood: 'Golden hour late afternoon, warm sunbeams filtering through dusty air',
                level1: 'High-end Korean Webtoon art style, rich atmospheric color grading, detailed backgrounds',
                level2: 'Fantasy Adventure, bustling metropolitan district',
                level5: 'volumetric sun rays, depth of field, 8k, vibrant colors, masterpiece'
            ),
            'prop_relic' => $this->setPresetData(
                focusType: 'prop',
                shotFraming: 'cinematic low-angle close-up',
                primarySubject: 'Heart of the Void, jagged pulsing crystalline core bound in blackened iron chains',
                secondarySubjects: '',
                actionDescription: 'bleeding dark ethereal mist into the cracked pedestal below',
                environment: 'Ancient Vault of Forgotten Gods, moss-covered marble altar',
                crowdDensity: 'none',
                lightingMood: 'Eerie bioluminescent purple and cyan glow illuminating dust motes',
                level1: 'Grimdark digital oil painting, detailed textured surfaces, painterly lighting',
                level2: 'Elden Ring dark fantasy aesthetic, forbidden artifact',
                level5: 'raytracing, subsurface scattering, 8k, hyper-detailed textures, masterpiece'
            ),
            'landscape' => $this->setPresetData(
                focusType: 'environment',
                shotFraming: 'epic panoramic wide establishing shot',
                primarySubject: 'Solitary cloaked traveler standing on cliff edge',
                secondarySubjects: '',
                actionDescription: 'overlooking the colossal floating continent suspended amidst swirling storm clouds',
                environment: 'Sky Archipelago of Aetheria, cascading waterfalls into the abyss below',
                crowdDensity: 'none',
                lightingMood: 'Sunrise breaking through thunderous clouds, radiant golden and violet sky',
                level1: 'Makoto Shinkai anime aesthetic, lush vibrant skies, photorealistic cloud formations',
                level2: 'Epic High Fantasy Worldbuilding',
                level5: 'volumetric atmospheric perspective, 8k, breathtaking scale, masterpiece'
            ),
            default => null,
        };

        $this->recompilePrompt();
        Flux::toast(variant: 'success', text: __('Loaded template preset: ').$presetKey);
    }

    private function setPresetData(
        string $focusType,
        string $shotFraming,
        string $primarySubject,
        string $secondarySubjects,
        string $actionDescription,
        string $environment,
        string $crowdDensity,
        string $lightingMood,
        string $level1,
        string $level2,
        string $level5
    ): void {
        $this->focusType = $focusType;
        $this->shotFraming = $shotFraming;
        $this->primarySubject = $primarySubject;
        $this->secondarySubjects = $secondarySubjects;
        $this->actionDescription = $actionDescription;
        $this->environment = $environment;
        $this->crowdDensity = $crowdDensity;
        $this->lightingMood = $lightingMood;
        $this->level1 = $level1;
        $this->level2 = $level2;
        $this->level5 = $level5;
    }

    public function syncToRawPrompt(): void
    {
        $this->recompilePrompt();
        $this->prompt = $this->compiledPrompt;
        $this->promptMode = 'raw';
        Flux::toast(variant: 'success', text: __('Compiled prompt copied to Raw Prompt editor!'));
    }

    public function updatedSelectedConnectionId(?int $value): void
    {
        if ($value) {
            $connection = AiConnection::find($value);
            if ($connection) {
                if (isset($connection->options['strength'])) {
                    $this->strength = (float) $connection->options['strength'];
                }
                if (isset($connection->options['guidance'])) {
                    $this->guidance = (float) $connection->options['guidance'];
                }
            }
        }
    }

    public function selectHistoryItem(int $index): void
    {
        if (isset($this->generationHistory[$index])) {
            $item = $this->generationHistory[$index];
            $this->generatedImageUrl = $item['url'];
            $this->prompt = $item['prompt'];
            $this->aspectRatio = $item['aspectRatio'] ?? '16:9';
            $this->style = $item['style'] ?? 'none';
        }
    }

    public function clearReferenceImage(): void
    {
        $this->referenceImage = null;
    }

    public function generateImage(AiModelManagerService $aiManager): void
    {
        if ($this->promptMode === 'director') {
            $this->recompilePrompt();
            $this->prompt = $this->compiledPrompt;
        }

        $this->validate([
            'prompt' => 'required|string|max:3000',
            'negativePrompt' => 'nullable|string|max:1000',
            'style' => 'required|string',
            'aspectRatio' => 'required|in:16:9,9:16,1:1',
            'strength' => 'required|numeric|min:0|max:1',
            'guidance' => 'required|numeric|min:1|max:20',
            'selectedConnectionId' => 'nullable|integer',
            'referenceImage' => 'nullable|image|max:10240',
        ]);

        $this->errorMessage = null;

        $connection = $this->selectedConnectionId
            ? AiConnection::find($this->selectedConnectionId)
            : $aiManager->getDefaultModel('image', Auth::id());

        // Clone connection options with execution-specific parameters
        if ($connection) {
            $options = $connection->options ?? [];
            $options['strength'] = $this->strength;
            $options['guidance'] = $this->guidance;
            if (! empty($this->negativePrompt)) {
                $options['negative_prompt'] = $this->negativePrompt;
            }
            $connection = clone $connection;
            $connection->options = $options;
        }

        $styledPrompt = $this->prompt;
        if ($this->style !== 'none') {
            $styledPrompt = match ($this->style) {
                'photorealistic' => "Photorealistic, 8k resolution, ultra detailed, {$this->prompt}",
                'anime' => "Modern anime aesthetic, Makoto Shinkai style, vibrant colors, {$this->prompt}",
                'manhwa' => "Dark fantasy manhwa webtoon style, high contrast, glowing particles, {$this->prompt}",
                'fantasy' => "Epic fantasy digital illustration, cinematic lighting, {$this->prompt}",
                'cyberpunk' => "Cyberpunk neon aesthetic, futuristic atmosphere, volumetric lighting, {$this->prompt}",
                'cartoon' => "3D Pixar style render, vibrant whimsical atmosphere, smooth lighting, {$this->prompt}",
                default => "{$this->prompt}, {$this->style} style",
            };
        }

        $inputImages = [];
        $tempUploadPath = null;

        if ($this->referenceImage) {
            $tempUploadPath = $this->referenceImage->getRealPath();
            if ($tempUploadPath && File::exists($tempUploadPath)) {
                // 1. Deteksi otomatis tipe file (misal: image/png atau image/jpeg)
                $mimeType = mime_content_type($tempUploadPath);

                // 2. Baca isi file fisik dan ubah ke Base64 mentah
                $base64String = base64_encode(file_get_contents($tempUploadPath));

                // 3. Gabungkan menjadi format Data URI persis seperti permintaan skema
                $dataUri = "data:{$mimeType};base64,{$base64String}";

                // 4. Masukkan ke dalam array
                $inputImages[] = $dataUri;
            }
        }

        $filename = 'gen_'.uniqid().'_'.time().'.png';
        $relativeStoragePath = "generated_images/{$filename}";
        $outputAbsPath = Storage::disk('public')->path($relativeStoragePath);

        try {
            File::ensureDirectoryExists(dirname($outputAbsPath));

            $success = $aiManager->generateImage(
                prompt: $styledPrompt,
                outputAbsPath: $outputAbsPath,
                connection: $connection,
                aspectRatio: $this->aspectRatio,
                referenceImagePaths: $inputImages
            );

            if ($success && File::exists($outputAbsPath)) {
                $this->generatedImageUrl = Storage::disk('public')->url($relativeStoragePath);

                array_unshift($this->generationHistory, [
                    'url' => $this->generatedImageUrl,
                    'prompt' => $this->prompt,
                    'model' => $connection?->name ?? 'Default Model',
                    'aspectRatio' => $this->aspectRatio,
                    'style' => $this->style,
                    'mode' => $this->promptMode,
                    'focusType' => $this->focusType,
                    'created_at' => now()->format('H:i:s'),
                ]);
                if (count($this->generationHistory) > 12) {
                    array_pop($this->generationHistory);
                }

                Flux::toast(variant: 'success', text: __('Image generated successfully!'));
            } else {
                throw new Exception(__('Image generation failed to save output file.'));
            }
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Flux::toast(variant: 'danger', text: __('Generation failed: ').$e->getMessage());
        }
    }

    public function render(AiModelManagerService $aiManager)
    {
        $availableModels = $aiManager->getAvailableModels('image', Auth::id());

        $selectedConnection = $this->selectedConnectionId
            ? AiConnection::find($this->selectedConnectionId)
            : $aiManager->getDefaultModel('image', Auth::id());

        $supportsReferenceImages = $aiManager->connectionSupportsReferenceImages($selectedConnection);

        $userTitles = ContentTitle::where('user_id', Auth::id())
            ->with(['characters', 'environments', 'props'])
            ->latest()
            ->get();

        $activeTitle = $this->selectedContentTitleId
            ? $userTitles->firstWhere('id', $this->selectedContentTitleId)
            : null;

        $availableCharacters = $activeTitle
            ? $activeTitle->characters
            : Character::where('user_id', Auth::id())->latest()->get();

        $availableEnvironments = $activeTitle
            ? $activeTitle->environments
            : Environment::where('user_id', Auth::id())->latest()->get();

        $availableProps = $activeTitle
            ? $activeTitle->props
            : Prop::where('user_id', Auth::id())->latest()->get();

        return view('livewire.image-generator', [
            'availableModels' => $availableModels,
            'supportsReferenceImages' => $supportsReferenceImages,
            'selectedConnection' => $selectedConnection,
            'userTitles' => $userTitles,
            'activeTitle' => $activeTitle,
            'availableCharacters' => $availableCharacters,
            'availableEnvironments' => $availableEnvironments,
            'availableProps' => $availableProps,
        ]);
    }
}
