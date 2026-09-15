<?php

namespace App\Services\Ai;

use App\Jobs\GenerateStoryScenesJob;
use App\Models\Character;
use App\Models\ContentFactoryTemplate;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Prop;
use App\Models\Story;
use App\Services\ReferenceSheetAnalyzerService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentTitleAiGeneratorService
{
    public function __construct(
        protected AiModelManagerService $aiManager,
        protected ?ReferenceSheetAnalyzerService $sheetAnalyzer = null
    ) {
        $this->sheetAnalyzer = $sheetAnalyzer ?? app(ReferenceSheetAnalyzerService::class);
    }

    /**
     * Generate structured World Bible assets (characters, environments, props) via LLM.
     *
     * @param  array{
     *     scope?: string,
     *     user_prompt?: string,
     *     character_count?: int,
     *     environment_count?: int,
     *     prop_count?: int,
     *     auto_generate_sheets?: bool,
     *     user_id?: ?int
     * }  $options
     * @return array{
     *     characters: array<int, Character>,
     *     environments: array<int, Environment>,
     *     props: array<int, Prop>
     * }
     */
    public function generateWorldBible(ContentTitle $title, ?ContentFactoryTemplate $template = null, array $options = []): array
    {
        $scope = $options['scope'] ?? 'all'; // 'all', 'characters', 'environments', 'props'
        $userPrompt = trim($options['user_prompt'] ?? '');
        $charCount = max(1, min(5, (int) ($options['character_count'] ?? 2)));
        $envCount = max(1, min(5, (int) ($options['environment_count'] ?? 2)));
        $propCount = max(1, min(5, (int) ($options['prop_count'] ?? 1)));
        $autoGenerateSheets = (bool) ($options['auto_generate_sheets'] ?? false);
        $userId = $options['user_id'] ?? Auth::id() ?? $title->user_id;

        $prompt = $this->buildWorldBiblePrompt($title, $template, $scope, $userPrompt, $charCount, $envCount, $propCount);

        $rawResponse = $this->aiManager->generateText($prompt, $title->textConnection);
        $data = $this->parseJsonPayload($rawResponse);

        $createdCharacters = [];
        $createdEnvironments = [];
        $createdProps = [];

        // 1. Process Characters
        if (in_array($scope, ['all', 'characters']) && ! empty($data['characters'])) {
            foreach ($data['characters'] as $charData) {
                if (empty($charData['name'])) {
                    continue;
                }

                $char = Character::create([
                    'user_id' => $userId,
                    'name' => $charData['name'],
                    'gender' => $charData['gender'] ?? '',
                    'entity_type' => $charData['entity_type'] ?? 'human',
                    'age' => isset($charData['age']) ? (int) $charData['age'] : null,
                    'appearance' => $charData['appearance'] ?? '',
                    'role' => $charData['role'] ?? 'supporting',
                    'personality' => $charData['personality'] ?? '',
                    'prompt_keywords' => $charData['prompt_keywords'] ?? ($charData['name'].', '.$title->visual_style),
                ]);

                $title->characters()->syncWithoutDetaching([$char->id]);

                if ($autoGenerateSheets) {
                    try {
                        $this->generateSheetForAsset('character', $char, $title);
                    } catch (Exception $e) {
                        Log::warning('AI Sheet auto-generation skipped for character '.$char->name.': '.$e->getMessage());
                    }
                }

                $createdCharacters[] = $char;
            }
        }

        // 2. Process Environments
        if (in_array($scope, ['all', 'environments']) && ! empty($data['environments'])) {
            foreach ($data['environments'] as $envData) {
                if (empty($envData['name'])) {
                    continue;
                }

                $env = Environment::create([
                    'user_id' => $userId,
                    'content_title_id' => $title->id,
                    'name' => $envData['name'],
                    'setting_type' => $envData['setting_type'] ?? 'interior',
                    'time_of_day' => $envData['time_of_day'] ?? 'night',
                    'atmosphere' => $envData['atmosphere'] ?? '',
                    'lighting' => $envData['lighting'] ?? '',
                    'description' => $envData['description'] ?? ($envData['prompt_keywords'] ?? ''),
                    'prompt_keywords' => $envData['prompt_keywords'] ?? ($envData['name'].', '.$title->visual_style),
                ]);

                if ($autoGenerateSheets) {
                    try {
                        $this->generateSheetForAsset('environment', $env, $title);
                    } catch (Exception $e) {
                        Log::warning('AI Sheet auto-generation skipped for environment '.$env->name.': '.$e->getMessage());
                    }
                }

                $createdEnvironments[] = $env;
            }
        }

        // 3. Process Props
        if (in_array($scope, ['all', 'props']) && ! empty($data['props'])) {
            foreach ($data['props'] as $propData) {
                if (empty($propData['name'])) {
                    continue;
                }

                $prop = Prop::create([
                    'user_id' => $userId,
                    'content_title_id' => $title->id,
                    'name' => $propData['name'],
                    'category' => $propData['category'] ?? 'gear',
                    'description' => $propData['description'] ?? ($propData['prompt_keywords'] ?? ''),
                    'prompt_keywords' => $propData['prompt_keywords'] ?? ($propData['name'].', '.$title->visual_style),
                ]);

                if ($autoGenerateSheets) {
                    try {
                        $this->generateSheetForAsset('prop', $prop, $title);
                    } catch (Exception $e) {
                        Log::warning('AI Sheet auto-generation skipped for prop '.$prop->name.': '.$e->getMessage());
                    }
                }

                $createdProps[] = $prop;
            }
        }

        // 4. Update Art Direction if provided and previously blank
        if (! empty($data['art_direction']) && is_array($data['art_direction'])) {
            $updates = [];
            if (empty($title->color_palette) && ! empty($data['art_direction']['color_palette'])) {
                $updates['color_palette'] = $data['art_direction']['color_palette'];
            }
            if (empty($title->lighting_mood) && ! empty($data['art_direction']['lighting_mood'])) {
                $updates['lighting_mood'] = $data['art_direction']['lighting_mood'];
            }
            if (empty($title->camera_framing) && ! empty($data['art_direction']['camera_framing'])) {
                $updates['camera_framing'] = $data['art_direction']['camera_framing'];
            }
            if (! empty($updates)) {
                $title->update($updates);
            }
        }

        return [
            'characters' => $createdCharacters,
            'environments' => $createdEnvironments,
            'props' => $createdProps,
        ];
    }

    /**
     * Generate an engaging Story Episode script and record utilizing existing World Bible assets.
     *
     * @param  array{
     *     episode_hook?: string,
     *     character_ids?: array<int>,
     *     environment_ids?: array<int>,
     *     pacing?: string,
     *     user_id?: ?int
     * }  $options
     */
    public function generateStoryEpisode(ContentTitle $title, ?ContentFactoryTemplate $template = null, array $options = []): Story
    {
        $episodeHook = trim($options['episode_hook'] ?? '');
        $characterIds = $options['character_ids'] ?? $title->characters()->pluck('characters.id')->toArray();
        $environmentIds = $options['environment_ids'] ?? $title->environments()->pluck('environments.id')->toArray();
        $pacing = $options['pacing'] ?? 'standard'; // 'short', 'standard', 'extended'
        $userId = $options['user_id'] ?? Auth::id() ?? $title->user_id;

        $characters = Character::whereIn('id', $characterIds)->get();
        $environments = Environment::whereIn('id', $environmentIds)->get();

        $prompt = $this->buildStoryEpisodePrompt($title, $template, $characters, $environments, $episodeHook, $pacing);

        $rawResponse = $this->aiManager->generateText($prompt, $title->textConnection);
        $data = $this->parseJsonPayload($rawResponse);

        $storyTitle = ! empty($data['title']) ? trim($data['title']) : ($title->title.' - Episode '.($title->stories()->count() + 1));
        $narrativeScript = ! empty($data['narrative_script']) ? trim($data['narrative_script']) : ($data['synopsis'] ?? $episodeHook);

        $story = Story::create([
            'user_id' => $userId,
            'content_title_id' => $title->id,
            'title' => $storyTitle,
            'premise' => $narrativeScript,
            'genre' => $title->genre,
            'visual_style' => $title->visual_style,
            'aspect_ratio' => $title->aspect_ratio,
            'voice_name' => $title->voice_name,
            'voice_mode' => $title->voice_mode ?? 'multi_actor',
            'language' => str_starts_with($title->voice_name, 'id-') ? 'id' : 'en',
            'status' => 'pending',
        ]);

        if (! empty($characterIds)) {
            $story->characters()->sync($characterIds);
        }

        // Dispatch background scene breakdown job
        GenerateStoryScenesJob::dispatch($story);

        return $story;
    }

    /**
     * Build prompt for World Bible asset generation.
     */
    protected function buildWorldBiblePrompt(
        ContentTitle $title,
        ?ContentFactoryTemplate $template,
        string $scope,
        string $userPrompt,
        int $charCount,
        int $envCount,
        int $propCount
    ): string {
        $genre = $title->genre;
        $visualStyle = $title->visual_style;
        $desc = $title->description ?? '';
        $templateContext = $template ? "Template Preset: {$template->name} ({$template->niche})\nStyle Instructions: {$template->visual_style}" : '';

        return <<<PROMPT
You are an expert World Bible Architect and Cinematic Production Director for serialized visual content (Webtoons, Comics, Anime, and Recaps).
Generate a rich, cohesive, and visually distinctive World Bible package tailored for this Content Title:

Title: "{$title->title}"
Genre: {$genre}
Visual Style: {$visualStyle}
Description: {$desc}
{$templateContext}
Target Scope: {$scope}
Custom Directives / Theme: {$userPrompt}

Requirements:
1. Generate cohesive elements that feel part of the exact same world, lore, and visual rules.
2. For Characters (Target count: {$charCount}):
   - Include diverse roles (protagonist, rival/antagonist, sidekick, mentor, or creature/monster).
   - "entity_type": must be one of "human", "creature_beast", "android_mech", "monster_demon".
   - "gender": "male", "female", "androgynous", or "non_gendered".
   - "age": realistic integer (or null for non-humans).
   - "appearance": detailed clothing, colors, face, body build, distinctive silhouette.
   - "prompt_keywords": strictly orthographic reference sheet keywords (e.g., "three-view turnaround orthographic reference sheet, front view, side view, back view, neutral background").
3. For Environments (Target count: {$envCount}):
   - Provide iconic recurring locations (e.g. main base/room, ominous danger zone, mystical landmark).
   - "setting_type": "interior" or "exterior".
   - "time_of_day": "day", "night", "golden_hour", "dawn", "dusk".
   - "atmosphere": mood and weather (e.g. "rain-slicked neon reflections, volumetric haze").
   - "lighting": lighting style (e.g. "chiaroscuro dramatic shadows, glowing blue mana crystals").
   - "prompt_keywords": wide angle establishing shot keywords.
4. For Props & Gear (Target count: {$propCount}):
   - Signature weapons, gadgets, relics, or iconic everyday gear.
   - "category": "weapon", "gear", "vehicle", "artifact", or "consumable".
   - "prompt_keywords": product concept turnaround sheet keywords.
5. Provide Art Direction ("art_direction"):
   - "color_palette": signature 3-4 color triad.
   - "lighting_mood": cinematic lighting instructions.
   - "camera_framing": dynamic camera angles.

Return STRICTLY a valid JSON object without markdown formatting, backticks, or preamble:
{
  "characters": [
    {
      "name": "...",
      "entity_type": "human",
      "gender": "male",
      "age": 21,
      "role": "protagonist",
      "personality": "...",
      "appearance": "...",
      "prompt_keywords": "..."
    }
  ],
  "environments": [
    {
      "name": "...",
      "setting_type": "exterior",
      "time_of_day": "night",
      "atmosphere": "...",
      "lighting": "...",
      "description": "...",
      "prompt_keywords": "..."
    }
  ],
  "props": [
    {
      "name": "...",
      "category": "weapon",
      "description": "...",
      "prompt_keywords": "..."
    }
  ],
  "art_direction": {
    "color_palette": "...",
    "lighting_mood": "...",
    "camera_framing": "..."
  }
}
PROMPT;
    }

    /**
     * Build prompt for Story Episode generation.
     */
    protected function buildStoryEpisodePrompt(
        ContentTitle $title,
        ?ContentFactoryTemplate $template,
        $characters,
        $environments,
        string $episodeHook,
        string $pacing
    ): string {
        $genre = $title->genre;
        $visualStyle = $title->visual_style;
        $desc = $title->description ?? '';

        $charsBio = $characters->map(fn ($c) => "- {$c->name} ({$c->role}, {$c->gender}): {$c->appearance}")->implode("\n");
        $envsBio = $environments->map(fn ($e) => "- {$e->name} ({$e->setting_type}, {$e->time_of_day}): {$e->atmosphere}")->implode("\n");

        $wordCountTarget = match ($pacing) {
            'short' => '250-400 words (fast paced, punchy short-form recap)',
            'extended' => '600-900 words (deep narrative, suspenseful buildup)',
            default => '400-600 words (balanced episodic arc)',
        };

        $templateDirectives = $template ? "Niche: {$template->niche}\nFormat Directive: {$template->description}" : '';

        return <<<PROMPT
You are a premier serialized storyteller and YouTube recap scriptwriter for top-tier visual webtoons and animated series.
Write an exhilarating story episode script based on the following Content Title and established World Bible:

Title: "{$title->title}"
Genre: {$genre}
Visual Style: {$visualStyle}
Description: {$desc}
{$templateDirectives}

Established World Bible Characters:
{$charsBio}

Established World Bible Locations:
{$envsBio}

Episode Directive / Premise Hook:
{$episodeHook}

Length Target: {$wordCountTarget}

Instructions:
1. Write in captivating, third-person present-tense storytelling voice ("He steps into...", "The shadows flicker...", "She tightens her grip...").
2. The script must directly feature the provided characters and take place in the provided locations.
3. Structure with a gripping opening hook, escalating tension/conflict, dynamic dialogue or encounter, and a cliffhanger or impactful resolution.
4. Give the episode a dramatic title (e.g., "Episode 1: The Hollow Whispers", "The Crimson Awakening").

Return STRICTLY a valid JSON object without markdown formatting, backticks, or preamble:
{
  "title": "Episode Title Here",
  "synopsis": "1-2 sentence high concept summary",
  "narrative_script": "The complete full-length third-person narrative script text here..."
}
PROMPT;
    }

    /**
     * Generate reference sheet image for a newly created asset.
     */
    protected function generateSheetForAsset(string $type, $item, ContentTitle $title): void
    {
        $filename = "sheets/{$type}_{$item->id}_".time().'.png';
        $absPath = Storage::disk('public')->path($filename);
        File::ensureDirectoryExists(dirname($absPath));

        $modelConnection = $title->image_connection_id ?: $title->image_provider;

        $result = $this->sheetAnalyzer->generateSheetImage(
            name: $item->name,
            category: $type,
            description: $item->appearance ?? $item->description ?? $item->prompt_keywords,
            outputAbsPath: $absPath,
            visualStyle: $title->visual_style,
            customPrompt: $item->prompt_keywords,
            providerOrConnectionId: $modelConnection
        );

        $item->update([
            'sheet_image_path' => $filename,
            'sheet_data' => $result['sheet_data'] ?? null,
        ]);
    }

    /**
     * Parse raw LLM output into an array, stripping code fences or stray markdown.
     *
     * @return array<string, mixed>
     */
    protected function parseJsonPayload(string $raw): array
    {
        $cleaned = trim($raw);

        // Strip ```json ... ``` or ``` ... ```
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $cleaned, $matches)) {
            $cleaned = trim($matches[1]);
        } elseif (preg_match('/\{[\s\S]*\}/', $cleaned, $matches)) {
            $cleaned = trim($matches[0]);
        }

        $decoded = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        Log::warning('ContentTitleAiGeneratorService: JSON decode failed', [
            'error' => json_last_error_msg(),
            'raw' => substr($raw, 0, 500),
        ]);

        return [];
    }
}
