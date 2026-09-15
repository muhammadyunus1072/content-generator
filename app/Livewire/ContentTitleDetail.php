<?php

namespace App\Livewire;

use App\Jobs\GenerateStoryScenesJob;
use App\Models\AiConnection;
use App\Models\Character;
use App\Models\ContentFactoryTemplate;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Prop;
use App\Models\Story;
use App\Services\Ai\AiModelManagerService;
use App\Services\Ai\ContentTitleAiGeneratorService;
use App\Services\ReferenceSheetAnalyzerService;
use App\Services\TextToSpeechService;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContentTitleDetail extends Component
{
    use WithFileUploads;

    public ContentTitle $contentTitle;

    // Edit Title Settings State
    public bool $showEditTitleModal = false;

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editGenre = 'Action Fantasy';

    public string $promptLevel1 = '';

    public string $promptLevel2 = '';

    public string $promptLevel5 = '';

    public array $promptTree = [
        'Manhwa / Webtoon' => [
            'level2' => ['Modern / Slice of Life', 'Historical / RoFan', 'Murim / Martial Arts', 'Hunters / Dungeons'],
            'level5' => ['masterpiece, best quality, highly detailed, official art, webtoon cover art, crisp lines, cel shaded, vibrant colors, studio redice style'],
        ],
        'Realistic / Cinematic Photography' => [
            'level2' => ['Contemporary Drama', 'Historical Period Piece', 'Sci-Fi / Near Future'],
            'level5' => ['Shot on Arri Alexa 65, IMAX, anamorphic lens, Kodak Portra 400, film grain, cinematic color grading, 8k, photorealistic'],
        ],
        'Children\'s Book Illustration' => [
            'level2' => ['Whimsical Fantasy', 'Everyday Adventures', 'Space / Sci-Fi for Kids'],
            'level5' => ['watercolor style, soft pastel colors, cute, whimsical, highly detailed children\'s book illustration, Eric Carle style, Oliver Jeffers style, soothing'],
        ],
        'Japanese Anime' => [
            'level2' => ['Shonen Action', 'Shojo Romance', 'Isekai / Fantasy'],
            'level5' => ['studio ghibli style, studio ufotable style, makoto shinkai style, Kyoto Animation, masterpiece, key visual, highly detailed anime background, cel shaded, 4k'],
        ],
        '3D Animation (Pixar/Disney)' => [
            'level2' => ['Modern City', 'Anthropomorphic Animals', 'Magical Kingdom'],
            'level5' => ['3d render, octane render, unreal engine 5, pixar style, disney style, ray tracing, subsurface scattering, highly detailed textures, 8k resolution'],
        ],
        'Vector Illustration / Flat Art' => [
            'level2' => ['Tech / Startup', 'Nature / Outdoors', 'Conceptual'],
            'level5' => ['flat vector illustration, minimalistic, adobe illustrator style, clean lines, behance style, dribbble style, scalable vector graphics, solid colors'],
        ],
        'Cyberpunk / Sci-Fi Concept Art' => [
            'level2' => ['Cyberpunk Dystopia', 'Space Opera', 'Hard Sci-Fi'],
            'level5' => ['concept art, trending on artstation, greg rutkowski style, highly detailed sci-fi, unreal engine 5 render, cinematic lighting, intricate mechanical details, 8k'],
        ],
        'Dark Fantasy / Gothic' => [
            'level2' => ['Bloodborne / Souls-like', 'Grimdark Fantasy', 'Vampire / Gothic'],
            'level5' => ['dark fantasy concept art, gothic horror, masterpiece, trending on artstation, highly detailed armor, gloomy, atmospheric, volumetric fog, 8k'],
        ],
        'High Fantasy / Mythological' => [
            'level2' => ['Epic Fantasy', 'Mythological', 'Magic Academy'],
            'level5' => ['high fantasy concept art, dungeons and dragons style, magic the gathering art, greg rutkowski, masterpiece, 8k, epic scale, intricate details'],
        ],
        'Dark / Horror / Creepypasta' => [
            'level2' => ['Found Footage / Analog Horror', 'Eldritch / Cosmic Horror', 'Slender / Creepypasta'],
            'level5' => ['horror concept art, analog horror, VHS glitch, liminal space, terrifying, photorealistic, unsettling, masterpiece, dark and gritty, macabre'],
        ],
    ];

    public string $editAspectRatio = '9:16';

    public string $editVoiceName = 'id-ID-ArdiNeural';

    public ?int $editImageModelConnectionId = null;

    public ?int $editTextModelConnectionId = null;

    public string $activeTab = 'world_bible'; // 'world_bible' or 'stories'

    public string $bibleSection = 'characters'; // 'characters', 'environments', 'props', 'art_direction'

    // --- UNIFIED ASSET MODAL STATE ---
    public bool $showAssetModal = false;

    public string $assetModalType = 'character'; // 'character', 'environment', 'prop'

    public ?int $editingAssetId = null;

    public string $creationMode = 'prompt'; // 'prompt' (AI Generate from prompt) or 'upload' (Direct Upload sheet)

    public $modalUploadFile = null;

    // --- GRANULAR CHARACTER / CREATURE FORM FIELDS ---
    public string $charName = '';

    public string $charEntityType = 'human'; // 'human', 'creature_beast', 'android_mech', 'monster_demon'

    public string $charGender = ''; // '', 'female', 'male', 'androgynous', 'non_gendered'

    public ?int $charAge = null;

    public string $charBodyBuild = '';

    public string $charSkinMaterial = '';

    // Adaptive: Humanoid Features
    public string $charHairStyle = '';

    public string $charHairColor = '';

    public string $charEyes = '';

    public string $charFacialFeatures = '';

    public string $charUpperWear = '';

    public string $charLowerWear = '';

    public string $charFootwear = '';

    // Adaptive: Creature / Beast / Monster Features
    public string $charHeadMuzzle = '';

    public string $charFurScales = '';

    public string $charEyesFangs = '';

    public string $charPawsClaws = '';

    public string $charTailWings = '';

    public string $charCollarHarness = '';

    // Adaptive: Android / Mech Features
    public string $charHeadVisor = '';

    public string $charPlatingChassis = '';

    public string $charSensorsLights = '';

    public string $charTorsoArmor = '';

    public string $charLimbsJoints = '';

    // Accessories & Colors
    public string $charAccessories = '';

    public string $charColorPrimary = '';

    public string $charColorSecondary = '';

    public string $charColorAccent = '';

    // Sheet Layout Switches
    public bool $includeTurnaround = true;

    public bool $includeExpressionInset = true;

    public bool $includeItemInset = true;

    // Real-Time Live Editable Assembled Prompt
    public string $compiledPrompt = '';

    public bool $manualPromptOverride = false;

    // --- ENVIRONMENT FORM FIELDS ---
    public string $envName = '';

    public string $envSettingType = 'interior';

    public string $envTimeOfDay = 'night';

    public string $envLighting = '';

    public string $envAtmosphere = '';

    public string $envPrompt = '';

    public string $envDescription = '';

    // --- PROP FORM FIELDS ---
    public string $propName = '';

    public string $propCategory = 'gear';

    public string $propPrompt = '';

    public string $propDescription = '';

    // Inline Direct Upload State
    public $inlineUploadFile = null;

    public ?string $inlineUploadTargetType = null;

    public ?int $inlineUploadTargetId = null;

    // Inspect Sheet Modal State
    public bool $showSheetModal = false;

    public ?string $inspectingSheetTitle = null;

    public ?string $inspectingSheetImageUrl = null;

    public ?array $inspectingSheetData = null;

    // Art Direction Form
    public string $artColorPalette = '';

    public string $artLightingMood = '';

    public string $artCameraFraming = '';

    // New Story Form
    public string $storyTitle = '';

    public string $narrativeScript = '';

    public array $selectedCharacterIds = [];

    // --- AI GENERATOR MODALS STATE ---
    // 1. World Bible AI Generation Modal
    public bool $showAiWorldBibleModal = false;

    public ?int $aiBibleTemplateId = null;

    public string $aiBibleScope = 'all'; // 'all', 'characters', 'environments', 'props'

    public string $aiBiblePrompt = '';

    public int $aiCharCount = 2;

    public int $aiEnvCount = 2;

    public int $aiPropCount = 1;

    public bool $aiAutoGenerateSheets = false;

    // 2. Story Episode AI Generation Modal
    public bool $showAiEpisodeModal = false;

    public ?int $aiEpisodeTemplateId = null;

    public string $aiEpisodeHook = '';

    public array $aiEpisodeCharacterIds = [];

    public array $aiEpisodeEnvironmentIds = [];

    public string $aiEpisodePacing = 'standard'; // 'short', 'standard', 'extended'

    public function mount(ContentTitle $contentTitle): void
    {
        $this->contentTitle = $contentTitle;
        $this->selectedCharacterIds = $this->contentTitle->characters()->pluck('characters.id')->toArray();
        $this->artColorPalette = $this->contentTitle->color_palette ?? '';
        $this->artLightingMood = $this->contentTitle->lighting_mood ?? '';
        $this->artCameraFraming = $this->contentTitle->camera_framing ?? '';

        if (is_array($this->contentTitle->prompt_config)) {
            $this->promptLevel1 = $this->contentTitle->prompt_config['level1'] ?? '';
            $this->promptLevel2 = $this->contentTitle->prompt_config['level2'] ?? '';
            $this->promptLevel5 = $this->contentTitle->prompt_config['level5'] ?? '';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['world_bible', 'stories']) ? $tab : 'world_bible';
    }

    public function setBibleSection(string $section): void
    {
        $this->bibleSection = in_array($section, ['characters', 'environments', 'props', 'art_direction']) ? $section : 'characters';
    }

    // --- REACTIVE PROMPT COMPILATION ---

    public function updated($propertyName): void
    {
        if ($propertyName === 'compiledPrompt') {
            $this->manualPromptOverride = true;
        }

        // Auto-recompile prompt if character fields change and user hasn't typed in the compiledPrompt directly
        if ($this->assetModalType === 'character' && ! $this->manualPromptOverride && ! in_array($propertyName, ['compiledPrompt', 'modalUploadFile', 'inlineUploadFile'])) {
            $this->recompileCharacterPrompt();
        }
    }

    public function updatedPromptLevel1(): void
    {
        $this->promptLevel2 = '';
        $this->promptLevel5 = '';

        if (isset($this->promptTree[$this->promptLevel1])) {
            $this->promptLevel2 = $this->promptTree[$this->promptLevel1]['level2'][0] ?? '';
            $this->promptLevel5 = $this->promptTree[$this->promptLevel1]['level5'][0] ?? '';
        }
    }

    public function updatedCharEntityType(): void
    {
        $this->recompileCharacterPrompt();
    }

    public function appendSuggestionTag(string $fieldName, string $tag): void
    {
        if (property_exists($this, $fieldName)) {
            $current = trim($this->{$fieldName});
            if (empty($current)) {
                $this->{$fieldName} = $tag;
            } else {
                $this->{$fieldName} = rtrim($current, ', ').', '.$tag;
            }
            $this->recompileCharacterPrompt();
        }
    }

    public function recompileCharacterPrompt(): void
    {
        if ($this->manualPromptOverride) {
            return;
        }

        $parts = [];

        // 1. Identity & Style
        $name = trim($this->charName) ?: 'Character';
        $entityLabel = match ($this->charEntityType) {
            'creature_beast' => 'Creature / Beast',
            'android_mech' => 'Android / Mech',
            'monster_demon' => 'Monster / Boss',
            default => 'Character',
        };
        $parts[] = "{$entityLabel} model sheet of {$name}";

        if ($this->charAge && $this->charEntityType === 'human') {
            $parts[] = "{$this->charAge} years old";
        }
        if (! empty(trim($this->charGender)) && $this->charGender !== 'non_gendered') {
            $parts[] = ucfirst($this->charGender);
        }
        if (! empty(trim($this->charBodyBuild))) {
            $parts[] = trim($this->charBodyBuild);
        }
        if (! empty(trim($this->charSkinMaterial))) {
            $parts[] = trim($this->charSkinMaterial);
        }

        // 2. Entity-Specific Anatomy
        if ($this->charEntityType === 'human') {
            if (! empty(trim($this->charFacialFeatures))) {
                $parts[] = trim($this->charFacialFeatures);
            }
            if (! empty(trim($this->charEyes))) {
                $parts[] = trim($this->charEyes);
            }
            if (! empty(trim($this->charHairStyle))) {
                $parts[] = trim($this->charHairStyle);
            }
            if (! empty(trim($this->charHairColor))) {
                $parts[] = trim($this->charHairColor).' hair';
            }
            if (! empty(trim($this->charUpperWear))) {
                $parts[] = 'wearing '.trim($this->charUpperWear);
            }
            if (! empty(trim($this->charLowerWear))) {
                $parts[] = trim($this->charLowerWear);
            }
            if (! empty(trim($this->charFootwear))) {
                $parts[] = trim($this->charFootwear);
            }
        } elseif (in_array($this->charEntityType, ['creature_beast', 'monster_demon'])) {
            if (! empty(trim($this->charHeadMuzzle))) {
                $parts[] = trim($this->charHeadMuzzle);
            }
            if (! empty(trim($this->charEyesFangs))) {
                $parts[] = trim($this->charEyesFangs);
            }
            if (! empty(trim($this->charFurScales))) {
                $parts[] = trim($this->charFurScales);
            }
            if (! empty(trim($this->charPawsClaws))) {
                $parts[] = trim($this->charPawsClaws);
            }
            if (! empty(trim($this->charTailWings))) {
                $parts[] = trim($this->charTailWings);
            }
            if (! empty(trim($this->charCollarHarness))) {
                $parts[] = trim($this->charCollarHarness);
            }
        } elseif ($this->charEntityType === 'android_mech') {
            if (! empty(trim($this->charHeadVisor))) {
                $parts[] = trim($this->charHeadVisor);
            }
            if (! empty(trim($this->charPlatingChassis))) {
                $parts[] = trim($this->charPlatingChassis);
            }
            if (! empty(trim($this->charSensorsLights))) {
                $parts[] = trim($this->charSensorsLights);
            }
            if (! empty(trim($this->charTorsoArmor))) {
                $parts[] = trim($this->charTorsoArmor);
            }
            if (! empty(trim($this->charLimbsJoints))) {
                $parts[] = trim($this->charLimbsJoints);
            }
        }

        // 3. Accessories & Jewelry
        if (! empty(trim($this->charAccessories))) {
            $parts[] = trim($this->charAccessories);
        }

        // 4. Color Palette
        $colors = array_filter([
            $this->charColorPrimary ? "Primary: {$this->charColorPrimary}" : null,
            $this->charColorSecondary ? "Secondary: {$this->charColorSecondary}" : null,
            $this->charColorAccent ? "Accent: {$this->charColorAccent}" : null,
        ]);
        if (! empty($colors)) {
            $parts[] = 'color palette ('.implode(', ', $colors).')';
        }

        // 5. Turnaround & Layout Directives (Strictly Orthographic)
        if ($this->includeTurnaround) {
            $parts[] = 'three-view turnaround orthographic reference sheet (front view, side profile view, back view)';
        }
        if ($this->includeExpressionInset) {
            $parts[] = 'facial expression charts inset';
        }
        if ($this->includeItemInset) {
            $parts[] = 'outfit and equipment detail insets';
        }

        $parts[] = 'isolated on neutral background, clean orthographic character sheet';

        $this->compiledPrompt = implode(', ', array_filter($parts));
    }

    // --- MODAL CONTROLS & ASSET MANAGEMENT ---

    public function openCreateAssetModal(string $type): void
    {
        $this->resetAssetForm();
        $this->assetModalType = $type;
        $this->creationMode = 'prompt';
        $this->showAssetModal = true;
        $this->manualPromptOverride = false;
        if ($type === 'character') {
            $this->recompileCharacterPrompt();
        }
    }

    public function editAsset(string $type, int $id): void
    {
        $this->resetAssetForm();
        $this->assetModalType = $type;
        $this->editingAssetId = $id;
        $this->creationMode = 'prompt';

        if ($type === 'character') {
            $char = Character::find($id);
            if ($char) {
                $this->charName = $char->name;
                $this->charEntityType = $char->entity_type ?? 'human';
                $this->charGender = $char->gender ?? '';
                $this->charAge = $char->age ?? null;
                $this->charBodyBuild = $char->appearance ?? '';
                $this->compiledPrompt = $char->prompt_keywords ?? '';
                $this->manualPromptOverride = ! empty($char->prompt_keywords);
                $this->showAssetModal = true;
            }
        } elseif ($type === 'environment') {
            $env = Environment::find($id);
            if ($env) {
                $this->envName = $env->name;
                $this->envSettingType = $env->setting_type ?? 'interior';
                $this->envTimeOfDay = $env->time_of_day ?? 'night';
                $this->envLighting = $env->lighting ?? '';
                $this->envAtmosphere = $env->atmosphere ?? '';
                $this->envPrompt = $env->prompt_keywords ?? '';
                $this->envDescription = $env->description ?? '';
                $this->showAssetModal = true;
            }
        } elseif ($type === 'prop') {
            $prop = Prop::find($id);
            if ($prop) {
                $this->propName = $prop->name;
                $this->propCategory = $prop->category ?? 'gear';
                $this->propPrompt = $prop->prompt_keywords ?? '';
                $this->propDescription = $prop->description ?? '';
                $this->showAssetModal = true;
            }
        }
    }

    public function saveAsset(ReferenceSheetAnalyzerService $analyzer): void
    {
        try {
            if ($this->assetModalType === 'character') {
                $this->validate([
                    'charName' => 'required|string|max:100',
                ]);

                if (empty(trim($this->compiledPrompt))) {
                    $this->recompileCharacterPrompt();
                }

                $summaryAppearance = trim($this->charBodyBuild.' '.$this->charUpperWear.' '.$this->charHeadMuzzle.' '.$this->charPlatingChassis);

                if ($this->editingAssetId) {
                    $char = Character::find($this->editingAssetId);
                    $char->update([
                        'name' => $this->charName,
                        'entity_type' => $this->charEntityType,
                        'gender' => $this->charGender,
                        'age' => $this->charAge,
                        'prompt_keywords' => $this->compiledPrompt,
                        'appearance' => $summaryAppearance ?: $this->compiledPrompt,
                    ]);
                } else {
                    $char = Character::create([
                        'user_id' => Auth::id(),
                        'name' => $this->charName,
                        'entity_type' => $this->charEntityType,
                        'gender' => $this->charGender,
                        'age' => $this->charAge,
                        'prompt_keywords' => $this->compiledPrompt,
                        'appearance' => $summaryAppearance ?: $this->compiledPrompt,
                    ]);
                    // Auto-bind to current Content Title
                    $this->contentTitle->characters()->syncWithoutDetaching([$char->id]);
                    $this->selectedCharacterIds[] = $char->id;
                }

                // Handle Reference Sheet generation / upload
                if ($this->creationMode === 'prompt') {
                    $this->generateSheetForItem('character', $char, $analyzer, $this->compiledPrompt);
                } elseif ($this->creationMode === 'upload' && $this->modalUploadFile) {
                    $this->uploadSheetForItem('character', $char, $this->modalUploadFile, $analyzer);
                }

                Flux::toast(variant: 'success', text: __('Character model sheet generated & saved!'));
            } elseif ($this->assetModalType === 'environment') {
                $this->validate([
                    'envName' => 'required|string|max:100',
                    'envPrompt' => 'required|string|max:1000',
                ]);

                if ($this->editingAssetId) {
                    $env = Environment::find($this->editingAssetId);
                    $env->update([
                        'name' => $this->envName,
                        'setting_type' => $this->envSettingType,
                        'time_of_day' => $this->envTimeOfDay,
                        'lighting' => $this->envLighting,
                        'atmosphere' => $this->envAtmosphere,
                        'prompt_keywords' => $this->envPrompt,
                        'description' => $this->envDescription ?: $this->envPrompt,
                    ]);
                } else {
                    $env = Environment::create([
                        'user_id' => Auth::id(),
                        'content_title_id' => $this->contentTitle->id,
                        'name' => $this->envName,
                        'setting_type' => $this->envSettingType,
                        'time_of_day' => $this->envTimeOfDay,
                        'lighting' => $this->envLighting,
                        'atmosphere' => $this->envAtmosphere,
                        'prompt_keywords' => $this->envPrompt,
                        'description' => $this->envDescription ?: $this->envPrompt,
                    ]);
                }

                if ($this->creationMode === 'prompt') {
                    $this->generateSheetForItem('environment', $env, $analyzer, $this->envPrompt);
                } elseif ($this->creationMode === 'upload' && $this->modalUploadFile) {
                    $this->uploadSheetForItem('environment', $env, $this->modalUploadFile, $analyzer);
                }

                Flux::toast(variant: 'success', text: __('Environment concept sheet processed!'));
            } elseif ($this->assetModalType === 'prop') {
                $this->validate([
                    'propName' => 'required|string|max:100',
                    'propPrompt' => 'required|string|max:1000',
                ]);

                if ($this->editingAssetId) {
                    $prop = Prop::find($this->editingAssetId);
                    $prop->update([
                        'name' => $this->propName,
                        'category' => $this->propCategory,
                        'prompt_keywords' => $this->propPrompt,
                        'description' => $this->propDescription ?: $this->propPrompt,
                    ]);
                } else {
                    $prop = Prop::create([
                        'user_id' => Auth::id(),
                        'content_title_id' => $this->contentTitle->id,
                        'name' => $this->propName,
                        'category' => $this->propCategory,
                        'prompt_keywords' => $this->propPrompt,
                        'description' => $this->propDescription ?: $this->propPrompt,
                    ]);
                }

                if ($this->creationMode === 'prompt') {
                    $this->generateSheetForItem('prop', $prop, $analyzer, $this->propPrompt);
                } elseif ($this->creationMode === 'upload' && $this->modalUploadFile) {
                    $this->uploadSheetForItem('prop', $prop, $this->modalUploadFile, $analyzer);
                }

                Flux::toast(variant: 'success', text: __('Prop design sheet processed!'));
            }

            $this->showAssetModal = false;
            $this->resetAssetForm();
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'Error saving asset: '.$e->getMessage());
        }
    }

    // --- DIRECT ACTIONS FROM CARD ---

    public function directGenerateSheet(string $type, int $id, ReferenceSheetAnalyzerService $analyzer): void
    {
        try {
            $item = match ($type) {
                'character' => Character::find($id),
                'environment' => Environment::find($id),
                'prop' => Prop::find($id),
                default => null,
            };

            if ($item) {
                $this->generateSheetForItem($type, $item, $analyzer, $item->prompt_keywords);
                Flux::toast(variant: 'success', text: __('Reference Sheet regenerated & deconstructed!'));
            }
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'Generation failed: '.$e->getMessage());
        }
    }

    public function directUploadSheet(string $type, int $id, ReferenceSheetAnalyzerService $analyzer): void
    {
        $this->validate([
            'inlineUploadFile' => 'required|image|max:15360',
        ]);

        try {
            $item = match ($type) {
                'character' => Character::find($id),
                'environment' => Environment::find($id),
                'prop' => Prop::find($id),
                default => null,
            };

            if ($item && $this->inlineUploadFile) {
                $this->uploadSheetForItem($type, $item, $this->inlineUploadFile, $analyzer);
                $this->reset(['inlineUploadFile', 'inlineUploadTargetType', 'inlineUploadTargetId']);
                Flux::toast(variant: 'success', text: __('Reference Sheet uploaded & deconstructed with Gemini Vision!'));
            }
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'Upload failed: '.$e->getMessage());
        }
    }

    protected function generateSheetForItem(string $type, $item, ReferenceSheetAnalyzerService $analyzer, ?string $customPrompt = null): void
    {
        $description = $item->description ?? $item->appearance ?? $item->prompt_keywords;
        $filename = "sheets/{$type}_{$item->id}_".time().'.png';
        $absPath = Storage::disk('public')->path($filename);
        File::ensureDirectoryExists(dirname($absPath));

        $modelConnection = $this->contentTitle->image_connection_id ?: $this->contentTitle->image_provider;

        $result = $analyzer->generateSheetImage(
            name: $item->name,
            category: $type,
            description: $description,
            outputAbsPath: $absPath,
            visualStyle: $this->contentTitle->visual_style,
            customPrompt: $customPrompt,
            providerOrConnectionId: $modelConnection
        );

        $item->update([
            'sheet_image_path' => $filename,
            'sheet_data' => $result['sheet_data'],
            'prompt_keywords' => ! empty($item->prompt_keywords) ? $item->prompt_keywords : ($result['sheet_data']['consistency_prompt_tokens'] ?? null),
        ]);
    }

    protected function uploadSheetForItem(string $type, $item, $uploadedFile, ReferenceSheetAnalyzerService $analyzer): void
    {
        $ext = $uploadedFile->getClientOriginalExtension() ?: 'png';
        $filename = "sheets/{$type}_{$item->id}_".time().".{$ext}";
        $absPath = Storage::disk('public')->path($filename);
        File::ensureDirectoryExists(dirname($absPath));

        $uploadedFile->storeAs('', $filename, 'public');

        $sheetData = $analyzer->analyzeSheetImage($absPath, $type, $item->name);

        $item->update([
            'sheet_image_path' => $filename,
            'sheet_data' => $sheetData,
            'prompt_keywords' => ! empty($item->prompt_keywords) ? $item->prompt_keywords : ($sheetData['consistency_prompt_tokens'] ?? null),
        ]);
    }

    public function inspectSheet(string $type, int $id): void
    {
        $item = match ($type) {
            'character' => Character::find($id),
            'environment' => Environment::find($id),
            'prop' => Prop::find($id),
            default => null,
        };

        if ($item && $item->sheet_image_path) {
            $this->inspectingSheetTitle = "{$item->name} - Reference Sheet";
            $this->inspectingSheetImageUrl = $item->sheet_image_url;
            $this->inspectingSheetData = $item->sheet_data;
            $this->showSheetModal = true;
        }
    }

    public function deleteAsset(string $type, int $id): void
    {
        if ($type === 'character') {
            $this->contentTitle->characters()->detach($id);
            $this->selectedCharacterIds = array_diff($this->selectedCharacterIds, [$id]);
            Flux::toast(variant: 'success', text: __('Character unlinked from this Content Title.'));
        } elseif ($type === 'environment') {
            $env = Environment::where('id', $id)->where('content_title_id', $this->contentTitle->id)->first();
            if ($env) {
                $env->delete();
                Flux::toast(variant: 'success', text: __('Environment deleted.'));
            }
        } elseif ($type === 'prop') {
            $prop = Prop::where('id', $id)->where('content_title_id', $this->contentTitle->id)->first();
            if ($prop) {
                $prop->delete();
                Flux::toast(variant: 'success', text: __('Prop deleted.'));
            }
        }
    }

    public function toggleCharacter(int $characterId): void
    {
        $this->contentTitle->characters()->toggle($characterId);
        $this->selectedCharacterIds = $this->contentTitle->characters()->pluck('characters.id')->toArray();
        Flux::toast(variant: 'success', text: __('Character assignment updated.'));
    }

    public function saveArtDirection(): void
    {
        $this->contentTitle->update([
            'color_palette' => $this->artColorPalette,
            'lighting_mood' => $this->artLightingMood,
            'camera_framing' => $this->artCameraFraming,
        ]);
        Flux::toast(variant: 'success', text: __('Art Direction rules updated!'));
    }

    public function applyArtPreset(string $presetKey): void
    {
        $presets = [
            'solo_leveling' => [
                'palette' => 'Obsidian black, deep purple-navy, vibrant neon cyan mana glow',
                'lighting' => 'High contrast dramatic chiaroscuro, volumetric shadow auras, glowing eyes',
                'camera' => 'Dynamic extreme low angles, diagonal Dutch tilts, wide dungeon establishing shots',
            ],
            'cyberpunk_shibuya' => [
                'palette' => 'Charcoal dark aluminum, neon lavender, electric magenta, cyan holographic glow',
                'lighting' => 'Volumetric neon rain reflections, screen backlit glow, cinematic bokeh',
                'camera' => 'Over-the-shoulder workstation angles, wide Shibuya night skyline pans, 35mm lens',
            ],
            'fantasy_forest' => [
                'palette' => 'Lush mossy greens, deep earth browns, bioluminescent sky blue, golden sunbeams',
                'lighting' => 'Ethereal morning mist god rays, glowing spore lighting, soft dappled shadows',
                'camera' => 'Wide panoramic nature vistas, low angle tree canopy shots, macro plant close-ups',
            ],
        ];

        if (isset($presets[$presetKey])) {
            $this->artColorPalette = $presets[$presetKey]['palette'];
            $this->artLightingMood = $presets[$presetKey]['lighting'];
            $this->artCameraFraming = $presets[$presetKey]['camera'];
            $this->saveArtDirection();
        }
    }

    public function resetAssetForm(): void
    {
        $this->reset([
            'editingAssetId',
            'modalUploadFile',
            'charName',
            'charBodyBuild',
            'charSkinMaterial',
            'charHairStyle',
            'charHairColor',
            'charEyes',
            'charFacialFeatures',
            'charUpperWear',
            'charLowerWear',
            'charFootwear',
            'charHeadMuzzle',
            'charFurScales',
            'charEyesFangs',
            'charPawsClaws',
            'charTailWings',
            'charCollarHarness',
            'charHeadVisor',
            'charPlatingChassis',
            'charSensorsLights',
            'charTorsoArmor',
            'charLimbsJoints',
            'charAccessories',
            'charColorPrimary',
            'charColorSecondary',
            'charColorAccent',
            'compiledPrompt',
            'envName',
            'envLighting',
            'envAtmosphere',
            'envPrompt',
            'envDescription',
            'propName',
            'propPrompt',
            'propDescription',
        ]);
        $this->charEntityType = 'human';
        $this->charGender = '';
        $this->charAge = null;
        $this->manualPromptOverride = false;
        $this->includeTurnaround = true;
        $this->includeExpressionInset = true;
        $this->includeItemInset = true;
        $this->envSettingType = 'interior';
        $this->envTimeOfDay = 'night';
        $this->propCategory = 'gear';
    }

    // --- STORY CREATION ---

    public function createStory(): void
    {
        $this->validate([
            'storyTitle' => 'required|string|max:255',
            'narrativeScript' => 'required|string|min:20',
        ]);

        $story = Story::create([
            'user_id' => Auth::id(),
            'content_title_id' => $this->contentTitle->id,
            'title' => $this->storyTitle,
            'premise' => $this->narrativeScript,
            'genre' => $this->contentTitle->genre,
            'visual_style' => $this->contentTitle->visual_style,
            'aspect_ratio' => $this->contentTitle->aspect_ratio,
            'voice_name' => $this->contentTitle->voice_name,
            'language' => str_starts_with($this->contentTitle->voice_name, 'id-') ? 'id' : 'en',
            'status' => 'pending',
        ]);

        $charIdsToSync = ! empty($this->selectedCharacterIds)
            ? $this->selectedCharacterIds
            : $this->contentTitle->characters()->pluck('characters.id')->toArray();

        if (! empty($charIdsToSync)) {
            $story->characters()->sync($charIdsToSync);
        }

        GenerateStoryScenesJob::dispatch($story);

        Flux::toast(
            variant: 'success',
            text: __('Story episode created! Generating scene breakdown using World Bible assets.')
        );

        $this->reset(['storyTitle', 'narrativeScript']);
        $this->activeTab = 'stories';
    }

    public function deleteStory(int $id): void
    {
        $story = Story::where('id', $id)->where('content_title_id', $this->contentTitle->id)->first();
        if ($story) {
            $story->delete();
            Flux::toast(variant: 'success', text: __('Story deleted.'));
        }
    }

    public function openEditTitleModal(): void
    {
        $this->editTitle = $this->contentTitle->title;
        $this->editDescription = $this->contentTitle->description ?? '';
        $this->editGenre = $this->contentTitle->genre ?? 'Action Fantasy';

        if (is_array($this->contentTitle->prompt_config)) {
            $this->promptLevel1 = $this->contentTitle->prompt_config['level1'] ?? '';
            $this->promptLevel2 = $this->contentTitle->prompt_config['level2'] ?? '';
            $this->promptLevel5 = $this->contentTitle->prompt_config['level5'] ?? '';
        } elseif (! empty($this->contentTitle->visual_style)) {
            // Fallback for older entries: try to match a known Level 1, otherwise just set it to Level 5 and pick a default Level 1
            $this->promptLevel1 = 'Manhwa / Webtoon';
            $this->updatedPromptLevel1(); // Set defaults for L2, L5
            $this->promptLevel5 = $this->contentTitle->visual_style;
        }

        $this->editAspectRatio = $this->contentTitle->aspect_ratio ?? '9:16';
        $this->editVoiceName = $this->contentTitle->voice_name ?? 'id-ID-ArdiNeural';
        $this->editImageModelConnectionId = $this->contentTitle->image_connection_id;
        $this->editTextModelConnectionId = $this->contentTitle->text_connection_id;
        $this->showEditTitleModal = true;
    }

    public function saveTitleSettings(AiModelManagerService $aiManager): void
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
            'promptLevel1' => 'required|string',
            'promptLevel2' => 'required|string',
            'promptLevel5' => 'required|string',
            'editAspectRatio' => 'required|in:9:16,16:9',
            'editVoiceName' => 'required|string',
            'editImageModelConnectionId' => 'nullable|integer',
            'editTextModelConnectionId' => 'nullable|integer',
        ]);

        $imageConnection = $this->editImageModelConnectionId
            ? AiConnection::find($this->editImageModelConnectionId)
            : $aiManager->getDefaultModel('image');

        $textConnection = $this->editTextModelConnectionId
            ? AiConnection::find($this->editTextModelConnectionId)
            : $aiManager->getDefaultModel('text');

        $this->contentTitle->update([
            'title' => $this->editTitle,
            'description' => $this->editDescription,
            'genre' => $this->editGenre,
            'visual_style' => $this->promptLevel1 ?: $this->editVisualStyle,
            'prompt_config' => [
                'level1' => $this->promptLevel1,
                'level2' => $this->promptLevel2,
                'level5' => $this->promptLevel5,
            ],
            'aspect_ratio' => $this->editAspectRatio,
            'voice_name' => $this->editVoiceName,
            'image_provider' => $imageConnection ? $imageConnection->provider : ($this->contentTitle->image_provider ?: 'cloudflare'),
            'image_connection_id' => $imageConnection?->id,
            'text_connection_id' => $textConnection?->id,
        ]);

        $this->contentTitle->refresh();
        Flux::toast(variant: 'success', text: __('Content Title settings updated!'));
        $this->showEditTitleModal = false;
    }

    public function closeEditTitleModal(): void
    {
        $this->showEditTitleModal = false;
    }

    // --- AI GENERATION ACTIONS ---

    public function openAiWorldBibleModal(?string $scope = 'all'): void
    {
        $this->aiBibleScope = in_array($scope, ['all', 'characters', 'environments', 'props']) ? $scope : 'all';
        $this->aiBiblePrompt = '';
        $this->aiCharCount = 2;
        $this->aiEnvCount = 2;
        $this->aiPropCount = 1;
        $this->aiAutoGenerateSheets = false;

        // Auto-match template based on Title's genre or style
        if (! $this->aiBibleTemplateId) {
            $matchingTemplate = ContentFactoryTemplate::active()
                ->where('genre', $this->contentTitle->genre)
                ->orWhere('visual_style', $this->contentTitle->visual_style)
                ->first();
            $this->aiBibleTemplateId = $matchingTemplate?->id ?? ContentFactoryTemplate::active()->first()?->id;
        }

        $this->showAiWorldBibleModal = true;
    }

    public function generateWorldBibleWithAi(ContentTitleAiGeneratorService $generator): void
    {
        $this->validate([
            'aiBibleScope' => 'required|in:all,characters,environments,props',
            'aiCharCount' => 'required|integer|min:1|max:5',
            'aiEnvCount' => 'required|integer|min:1|max:5',
            'aiPropCount' => 'required|integer|min:1|max:5',
        ]);

        try {
            $template = $this->aiBibleTemplateId ? ContentFactoryTemplate::find($this->aiBibleTemplateId) : null;

            $results = $generator->generateWorldBible($this->contentTitle, $template, [
                'scope' => $this->aiBibleScope,
                'user_prompt' => $this->aiBiblePrompt,
                'character_count' => $this->aiCharCount,
                'environment_count' => $this->aiEnvCount,
                'prop_count' => $this->aiPropCount,
                'auto_generate_sheets' => $this->aiAutoGenerateSheets,
                'user_id' => Auth::id(),
            ]);

            $counts = [];
            if (! empty($results['characters'])) {
                $counts[] = count($results['characters']).' '.__('character(s)');
                $this->selectedCharacterIds = $this->contentTitle->characters()->pluck('characters.id')->toArray();
            }
            if (! empty($results['environments'])) {
                $counts[] = count($results['environments']).' '.__('environment(s)');
            }
            if (! empty($results['props'])) {
                $counts[] = count($results['props']).' '.__('prop(s)');
            }

            $summary = ! empty($counts) ? implode(', ', $counts) : __('World Bible elements');
            Flux::toast(variant: 'success', text: __('AI generated: ').$summary);

            $this->showAiWorldBibleModal = false;
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'AI World Bible generation failed: '.$e->getMessage());
        }
    }

    public function openAiEpisodeModal(): void
    {
        $this->aiEpisodeHook = '';
        $this->aiEpisodePacing = 'standard';
        $this->aiEpisodeCharacterIds = $this->contentTitle->characters()->pluck('characters.id')->toArray();
        $this->aiEpisodeEnvironmentIds = $this->contentTitle->environments()->pluck('environments.id')->toArray();

        if (! $this->aiEpisodeTemplateId) {
            $matchingTemplate = ContentFactoryTemplate::active()
                ->where('genre', $this->contentTitle->genre)
                ->orWhere('visual_style', $this->contentTitle->visual_style)
                ->first();
            $this->aiEpisodeTemplateId = $matchingTemplate?->id ?? ContentFactoryTemplate::active()->first()?->id;
        }

        $this->showAiEpisodeModal = true;
    }

    public function generateEpisodeWithAi(ContentTitleAiGeneratorService $generator): void
    {
        $this->validate([
            'aiEpisodePacing' => 'required|in:short,standard,extended',
        ]);

        try {
            $template = $this->aiEpisodeTemplateId ? ContentFactoryTemplate::find($this->aiEpisodeTemplateId) : null;

            $story = $generator->generateStoryEpisode($this->contentTitle, $template, [
                'episode_hook' => $this->aiEpisodeHook,
                'character_ids' => $this->aiEpisodeCharacterIds,
                'environment_ids' => $this->aiEpisodeEnvironmentIds,
                'pacing' => $this->aiEpisodePacing,
                'user_id' => Auth::id(),
            ]);

            Flux::toast(variant: 'success', text: __('Episode created with AI! Scene breakdown initiated.'));

            $this->showAiEpisodeModal = false;
            $this->activeTab = 'stories';
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'AI Episode generation failed: '.$e->getMessage());
        }
    }

    public function render(AiModelManagerService $aiManager)
    {
        $this->contentTitle->load([
            'characters',
            'environments' => fn ($q) => $q->latest(),
            'props' => fn ($q) => $q->latest(),
            'stories' => fn ($q) => $q->with(['characters', 'episodes.boards'])->latest(),
            'imageConnection',
            'textConnection',
        ]);

        $allCharacters = Character::latest()->get();
        $voices = TextToSpeechService::getAvailableVoices();
        $availableImageModels = $aiManager->getAvailableModels('image', Auth::id());
        $availableTextModels = $aiManager->getAvailableModels('text', Auth::id());
        $templates = ContentFactoryTemplate::active()->orderBy('sort_order')->get();

        return view('livewire.content-title-detail', [
            'allCharacters' => $allCharacters,
            'voices' => $voices,
            'availableImageModels' => $availableImageModels,
            'availableTextModels' => $availableTextModels,
            'templates' => $templates,
        ]);
    }
}
