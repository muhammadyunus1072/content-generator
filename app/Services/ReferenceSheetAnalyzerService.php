<?php

namespace App\Services;

use App\Services\Ai\AiModelManagerService;
use Exception;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ReferenceSheetAnalyzerService
{
    public function __construct(
        protected ?ImageGenerationService $imageService = null,
        protected ?AiModelManagerService $aiManager = null
    ) {
        $this->imageService = $imageService ?? app(ImageGenerationService::class);
        $this->aiManager = $aiManager ?? app(AiModelManagerService::class);
    }

    /**
     * Analyze a reference sheet image using Gemini Multimodal Vision.
     *
     * @return array<string, mixed> Structured sheet metadata & consistency tokens
     */
    public function analyzeSheetImage(string $imageAbsolutePath, string $category = 'character', ?string $name = null): array
    {
        if (! File::exists($imageAbsolutePath)) {
            throw new Exception("Reference sheet image not found at: {$imageAbsolutePath}");
        }

        $rawMime = File::mimeType($imageAbsolutePath) ?: 'image/jpeg';
        $geminiMime = MimeType::tryFrom($rawMime) ?? MimeType::IMAGE_JPEG;
        $imageBytes = File::get($imageAbsolutePath);
        $base64Image = base64_encode($imageBytes);

        $prompt = $this->buildAnalysisPrompt($category, $name);

        try {
            $blob = new Blob(
                mimeType: $geminiMime,
                data: $base64Image
            );

            $model = env('GEMINI_MODEL', 'gemini-2.0-flash');
            $response = Gemini::generativeModel(model: $model)->generateContent([
                $prompt,
                $blob,
            ]);

            $rawText = trim($response->text());

            // Clean markdown code blocks
            $rawText = preg_replace('/^```(?:json)?\s*/i', '', $rawText);
            $rawText = preg_replace('/\s*```$/', '', $rawText);

            $decoded = json_decode($rawText, true);

            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        } catch (Exception $e) {
            Log::warning('ReferenceSheetAnalyzerService Gemini Vision call failed: '.$e->getMessage());
        }

        return $this->getFallbackSheetData($category, $name);
    }

    /**
     * Generate an AI reference sheet image and automatically deconstruct it into structured sheet data.
     *
     * @return array{image_path: string, sheet_data: array<string, mixed>}
     */
    public function generateSheetImage(
        string $name,
        string $category,
        string $description,
        string $outputAbsPath,
        string $visualStyle = 'Dark Fantasy Manhwa',
        ?string $customPrompt = null,
        string|int|null $providerOrConnectionId = null
    ): array {
        if (! empty(trim($customPrompt ?? ''))) {
            $prompt = $customPrompt;
        } else {
            $prompt = match ($category) {
                'character' => "Character model sheet of {$name}, {$description}, three views turnaround front side back, facial expression chart, outfit variations, color swatches, clean neutral background, {$visualStyle}",
                'environment' => "Environment concept sheet of {$name}, {$description}, main interior wide view, exterior angle, detailed components, day and night lighting studies, color swatches, {$visualStyle}",
                'prop' => "Prop design sheet of {$name}, {$description}, multiple angles turnaround, mechanical callouts, materials swatches, clean neutral background, {$visualStyle}",
                default => "Concept design model sheet of {$name}, {$description}, multiple angles, color swatches, {$visualStyle}",
            };
        }

        // Generate the 16:9 model sheet image
        $this->imageService->generateAndSave(
            prompt: $prompt,
            outputAbsolutePath: $outputAbsPath,
            providerOrConnectionId: $providerOrConnectionId,
            aspectRatio: '16:9'
        );

        // Analyze and deconstruct the newly generated sheet
        $sheetData = $this->analyzeSheetImage($outputAbsPath, $category, $name);

        return [
            'image_path' => $outputAbsPath,
            'sheet_data' => $sheetData,
        ];
    }

    /**
     * Build strict JSON extraction prompt for Gemini Vision.
     */
    protected function buildAnalysisPrompt(string $category, ?string $name): string
    {
        return match ($category) {
            'character' => <<<PROMPT
You are a lead character designer and technical anime concept artist analyzing a Character / Creature Model Sheet.
Analyze the provided reference sheet image in extreme detail and return a strictly valid JSON object:
{
  "name": "{$name}",
  "entity_type": "human or creature or animal or android",
  "three_views": {
    "front": "Detailed description of character from front (hair, face, torso, clothing, footwear)",
    "side": "Detailed side profile description (hair volume, posture, backpack/gear, footwear)",
    "back": "Detailed back view description (haircut from back, hood/collar, backpack, trousers)"
  },
  "expressions": {
    "neutral": "Eyes and mouth appearance when neutral",
    "serious": "Expression description",
    "happy": "Expression description",
    "angry_or_alert": "Expression description"
  },
  "clothing_and_gear": {
    "outfit": "Complete clothing breakdown with specific cuts and silhouettes",
    "accessories": "Backpacks, necklaces, weapons, tech equipment, or collars",
    "footwear": "Shoes / sneakers / boots / paws description"
  },
  "materials": "Specific material textures identified on the sheet (e.g. Selvedge denim, loopback cotton hoodie, nylon backpack, metallic claws, glowing fur)",
  "color_palette": [
    {"name": "Hair / Fur", "color": "e.g. Deep Indigo or Espresso Brown"},
    {"name": "Eyes", "color": "e.g. Violet or Glowing Amber"},
    {"name": "Primary Outfit", "color": "e.g. Lavender Purple or Charcoal Navy"},
    {"name": "Accents", "color": "e.g. Neon Cyan or Gold"}
  ],
  "consistency_prompt_tokens": "Ultra-condensed SDXL/Flux token string (under 40 tokens) that guarantees facial, clothing, and anatomy consistency in any generated scene."
}
Return ONLY valid JSON. No conversational text.
PROMPT,

            'environment' => <<<PROMPT
You are a background concept artist analyzing an Environment / Location Concept Sheet.
Analyze the provided reference sheet image in extreme detail and return a strictly valid JSON object:
{
  "name": "{$name}",
  "setting_type": "interior / exterior / sci-fi / fantasy / urban",
  "main_view": "Detailed description of the central environment perspective",
  "exterior_view": "Description of exterior window/outside surroundings or landmark",
  "time_of_day_variants": {
    "daylight": "Lighting, sky, and shadow tone during day",
    "sunset": "Warm orange/purple hues and ambient glow",
    "night": "Deep indigos, neon/bioluminescent glows, and contrast lighting"
  },
  "materials_and_textures": "List of architectural materials (e.g. brushed dark aluminum, bioluminescent tree roots, mossy stone, carbon fiber, neon screens)",
  "key_components": ["Sub-component 1 (e.g. VJ mixer desk)", "Sub-component 2 (e.g. Server tower)", "Sub-component 3 (e.g. Stone arch)"],
  "color_palette": [
    {"name": "Base Architecture", "color": "e.g. Charcoal Metal or Lush Green"},
    {"name": "Ambient Light", "color": "e.g. Deep Indigo or Golden Daylight"},
    {"name": "Accent Glow", "color": "e.g. Cyan Screen Glow or Blue Bioluminescence"}
  ],
  "consistency_prompt_tokens": "Ultra-condensed SDXL/Flux prompt token string (under 40 tokens) that guarantees architectural and lighting consistency in any scene."
}
Return ONLY valid JSON. No conversational text.
PROMPT,

            'prop' => <<<PROMPT
You are a prop and weapon designer analyzing an Item / Prop Model Sheet.
Analyze the provided reference sheet image in extreme detail and return a strictly valid JSON object:
{
  "name": "{$name}",
  "category": "weapon / gear / gadget / artifact / furniture",
  "appearance": "Comprehensive description of the item structure and dimensions",
  "angles": {
    "front": "Front view layout",
    "side": "Side profile layout",
    "isometric": "3D isometric or detail insets"
  },
  "materials": "Physical textures and surface finish (e.g. Etched metal, rubberized pads, distressed leather, glowing runes)",
  "color_palette": [
    {"name": "Primary", "color": "e.g. Matte Black"},
    {"name": "Secondary", "color": "e.g. Brushed Silver"},
    {"name": "Accent", "color": "e.g. Neon Purple"}
  ],
  "consistency_prompt_tokens": "Ultra-condensed SDXL/Flux token string (under 30 tokens) capturing the item accurately."
}
Return ONLY valid JSON. No conversational text.
PROMPT,

            default => 'Return valid JSON containing name, description, color_palette, and consistency_prompt_tokens.',
        };
    }

    /**
     * Fallback structured sheet data if Vision API is unreachable.
     */
    protected function getFallbackSheetData(string $category, ?string $name): array
    {
        $name = $name ?: 'Asset';

        return match ($category) {
            'character' => [
                'name' => $name,
                'entity_type' => 'human',
                'three_views' => [
                    'front' => "Front view of {$name}",
                    'side' => "Side profile view of {$name}",
                    'back' => "Back view of {$name}",
                ],
                'expressions' => [
                    'neutral' => 'Neutral expression',
                    'serious' => 'Serious focused expression',
                    'happy' => 'Smiling expression',
                ],
                'clothing_and_gear' => [
                    'outfit' => 'Character outfit matching design specifications',
                    'accessories' => '',
                    'footwear' => '',
                ],
                'materials' => '',
                'color_palette' => [],
                'consistency_prompt_tokens' => "{$name}, character reference sheet",
            ],
            'environment' => [
                'name' => $name,
                'setting_type' => 'interior',
                'main_view' => "Detailed wide view of {$name}",
                'exterior_view' => '',
                'time_of_day_variants' => [
                    'daylight' => 'Daylight lighting',
                    'night' => 'Night atmospheric lighting',
                ],
                'materials_and_textures' => '',
                'key_components' => [],
                'color_palette' => [],
                'consistency_prompt_tokens' => "{$name}, environment concept view",
            ],
            'prop' => [
                'name' => $name,
                'category' => 'gear',
                'appearance' => "Prop design of {$name}",
                'angles' => [
                    'front' => 'Front view',
                    'side' => 'Side view',
                ],
                'materials' => '',
                'color_palette' => [],
                'consistency_prompt_tokens' => "{$name}, item prop design",
            ],
            default => [
                'name' => $name,
                'consistency_prompt_tokens' => "{$name}, design sheet",
            ],
        };
    }
}
