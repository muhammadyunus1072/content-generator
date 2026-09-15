<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Scene;
use App\Models\SceneBoard;
use App\Models\Story;
use Exception;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class StoryDecompositionService
{
    /**
     * Splice a story's raw text into episodes.
     *
     * @return array<int, array{episode_number: int, title: string, synopsis: string, narrative_script: string}>
     */
    public function spliceStoryToEpisodes(Story $story, int $targetEpisodes = 3): array
    {
        $prompt = <<<PROMPT
You are an expert manhwa and webtoon writer and YouTube comic recap scriptwriter.
Analyze the following story and split it into {$targetEpisodes} cohesive, dramatic episodic arcs suitable for a serialized manhwa recap video (like YouTube manhwa storytelling channels).

Story Title: {$story->title}
Genre: {$story->genre}
Language: {$story->language}

Story Narrative:
{$story->premise}

Instructions:
1. Divide the story into {$targetEpisodes} sequential episodes.
2. For each episode, provide:
   - episode_number: integer (1, 2, ...)
   - title: catchy manhwa episode title (e.g. "Awakening in the Double Dungeon")
   - synopsis: 1-2 sentences summarizing the episode hook
   - narrative_script: the complete narrative text written in captivating THIRD-PERSON POINT OF VIEW ("He opened his eyes...", "The shadows whispered...", "He could not believe his eyes...")
3. Return STRICTLY a valid JSON array of objects. Do not include markdown code block tags or surrounding text.

JSON format:
[
  {
    "episode_number": 1,
    "title": "...",
    "synopsis": "...",
    "narrative_script": "..."
  }
]
PROMPT;

        try {
            $parsed = $this->callGeminiJson($prompt);

            if (is_array($parsed) && ! empty($parsed)) {
                return $parsed;
            }
        } catch (Exception $e) {
            Log::warning('Gemini episode splicing failed, using heuristic fallback', ['error' => $e->getMessage()]);
        }

        return $this->heuristicEpisodeSplice($story, $targetEpisodes);
    }

    /**
     * Split an episode narrative into distinct scenes.
     *
     * @return array<int, array{scene_number: int, title: string, setting: string, mood: string, narration_text: string}>
     */
    public function splitEpisodeToScenes(Episode $episode, int $targetScenes = 3): array
    {
        $story = $episode->story;
        $genre = $story ? $story->genre : 'Action Fantasy';
        $language = $story ? $story->language : 'en';

        $prompt = <<<PROMPT
You are a cinematic manhwa visual director.
Break down this episode narrative into {$targetScenes} distinct dramatic scenes for video storytelling.

Episode Title: {$episode->title}
Genre: {$genre}
Language: {$language}

Episode Narrative Script:
{$episode->narrative_script}

Instructions:
1. Divide into {$targetScenes} scenes representing changes in location, emotional beat, or dramatic action.
2. For each scene, specify:
   - scene_number: integer (1, 2, ...)
   - title: concise scene label (e.g. "The Whispering Cavern")
   - setting: visual location and atmosphere description (e.g. "Subterranean altar lit by crimson mana crystals")
   - mood: emotional tone (e.g. "Ominous and suspenseful")
   - narration_text: the third-person narrator story text for this specific scene.
3. Return STRICTLY a valid JSON array of objects.

JSON format:
[
  {
    "scene_number": 1,
    "title": "...",
    "setting": "...",
    "mood": "...",
    "narration_text": "..."
  }
]
PROMPT;

        try {
            $parsed = $this->callGeminiJson($prompt);

            if (is_array($parsed) && ! empty($parsed)) {
                return $parsed;
            }
        } catch (Exception $e) {
            Log::warning('Gemini scene splitting failed, using heuristic fallback', ['error' => $e->getMessage()]);
        }

        return $this->heuristicSceneSplit($episode, $targetScenes);
    }

    /**
     * Split a scene into visual storyboards / panels (shot view, camera movement, art prompt, narration chunk).
     *
     * @return array<int, array{board_number: int, narration_chunk: string, view_type: string, camera_movement: string, visual_prompt: string}>
     */
    public function splitSceneToBoards(Scene $scene, string $visualStyle = 'Dark Fantasy Manhwa'): array
    {
        $episode = $scene->episode;
        $story = $episode?->story;
        $contentTitle = $story?->contentTitle;
        $language = $story ? $story->language : 'en';

        // 1. Art Direction & Lighting Rules (HIGHEST PRIORITY FOUNDATION)
        $artDirectionContext = "Art Direction & Lighting Foundation (CRITICAL):\n";
        if ($contentTitle) {
            $baseFormula = $contentTitle->getBasePromptFormula();
            $artDirectionContext .= "- Visual Aesthetic Style (Brain Key Base Formula): {$baseFormula}\n"
                .($contentTitle->color_palette ? "- Signature Color Palette: {$contentTitle->color_palette}\n" : '')
                .($contentTitle->lighting_mood ? "- Atmospheric & Lighting Mood: {$contentTitle->lighting_mood}\n" : '')
                .($contentTitle->camera_framing ? "- Camera & Framing Language: {$contentTitle->camera_framing}\n" : '');
        } else {
            $artDirectionContext .= "- Visual Aesthetic Style: {$visualStyle}\n";
        }

        // 2. Character Consistency Sheets (Turnaround, Expressions, Outfits, Entities)
        $availableChars = $story ? $story->getAvailableCharacters() : collect();
        $characterWhitelist = $availableChars->pluck('name')->toArray();
        $whitelistString = ! empty($characterWhitelist) ? implode(', ', $characterWhitelist) : 'None registered';

        $characterContext = '';
        if ($availableChars->isNotEmpty()) {
            $charList = $availableChars->map(function ($c) {
                $sheetInfo = '';
                if (! empty($c->sheet_data['three_views'])) {
                    $sheetInfo = " | Turnaround Front: \"{$c->sheet_data['three_views']['front']}\" | Profile: \"{$c->sheet_data['three_views']['side']}\"";
                }

                return "- {$c->name} ({$c->entity_type}, {$c->gender}, age {$c->age}): Tokens: \"{$c->getConsistencyTokens()}\"{$sheetInfo}. Appearance: {$c->appearance}";
            })->join("\n");
            $characterContext = "\nWorld Bible Characters, Beasts & Model Sheets (STRICT REGISTRATION LIST):\n".$charList."\n";
        }

        // 3. Environment / Locations Context (Time of Day, Textures, Perspective)
        $environmentContext = '';
        if ($contentTitle && $contentTitle->environments()->exists()) {
            $envList = $contentTitle->environments->map(function ($env) {
                $sheetInfo = '';
                if (! empty($env->sheet_data['main_view'])) {
                    $sheetInfo = " | Sheet Main: \"{$env->sheet_data['main_view']}\"";
                }

                return "- Name: \"{$env->name}\" | Setting: {$env->setting_type} | Lighting: {$env->lighting} | Tokens: \"{$env->getConsistencyTokens()}\"{$sheetInfo}";
            })->join("\n");
            $environmentContext = "\nWorld Bible Environments & Concept Sheets:\n".$envList."\n";
        }

        // 4. Key Props / Items Context
        $propContext = '';
        if ($contentTitle && $contentTitle->props()->exists()) {
            $propList = $contentTitle->props->map(function ($p) {
                return "- Name: \"{$p->name}\" ({$p->category}): Tokens: \"{$p->getConsistencyTokens()}\"";
            })->join("\n");
            $propContext = "\nWorld Bible Key Props & Items:\n".$propList."\n";
        }

        $prompt = <<<PROMPT
You are a master manhwa / webtoon visual director and cinematic comic storyboard artist.
Break down this scene into sequential visual comic panels (storyboards) for a high-retention comic recap video.
Each board represents an illustrated comic panel displayed while the narrator speaks the third-person voiceover.

Scene Title: {$scene->title}
Setting: {$scene->setting}
Mood: {$scene->mood}
Language: {$language}

{$artDirectionContext}
{$characterContext}
{$environmentContext}
{$propContext}

Scene Narration Text:
{$scene->narration_text}

CRITICAL COMIC STORYBOARDING & PROMPT ENGINEERING RULES:
1. REGISTERED WORLD BIBLE CHARACTERS (STRICT WHITELIST): [{$whitelistString}]
   - For "characters_present", you MUST ONLY include exact names from the registered list above if they appear in this panel's narration chunk.
   - NEVER use generic pronouns ("He", "She"), generic roles ("Protagonist", "Hero", "MC", "Warrior", "The Boy"), or unlisted character names.
   - If no character from the whitelist is present in a panel (e.g. pure scenery or establishing shot), return an empty array: "characters_present": [].
2. ZERO HALLUCINATION DIRECTIVE:
   - ONLY depict elements directly described or implied by the Scene Narration Text and World Bible entities provided.
   - DO NOT invent or insert fictional names, props, weapons, or abilities not present in the user's input.
3. STRUCTURED SCENE COMPILATION (NO RAW PROMPTS):
   - You act as the Director. Do not write a flat 'visual_prompt'. Instead, provide structured data.
   - "focus_type": one of ["character", "prop", "environment", "interaction", "crowd", "body_part"]
   - "shot_framing": one of ["wide_establishing", "medium_shot", "close_up", "extreme_macro_close_up", "dutch_angle"]
   - "primary_subject_id": The main focus (from World Bible, or descriptive if not registered)
   - "secondary_subject_ids": Array of other interacting subjects
   - "action_description": What is happening? (e.g. "Character A clenches their fist while Character B watches")
   - "crowd_density": one of ["none", "sparse", "dense", "bustling"]
   - "lighting_mood_override": The dynamic lighting/mood for this exact beat (e.g. "Midnight, high contrast, dramatic shadows")
4. Divide the narration into short natural sentence chunks (1-2 sentences per board, 10-25 words).
5. Motion comic animation & audio directives:
   - "animation_effect": choose from ["ken_burns", "parallax", "slide_in", "camera_shake", "impact_frame"] matching panel intensity.
   - "transition_type": choose from ["crossfade", "cut", "slide_left", "fade_black"].
   - "speech_bubbles": if character speaks or dialogue is present, array of objects: [{"speaker": "Character Name", "text": "Dialogue text", "style": "manga", "position": "top-right", "emotion": "intense"}], otherwise [].
   - "sfx_tags": array of relevant sound effect tags from ["slash", "explosion", "heartbeat", "sword_draw", "thunder", "punch", "magic_cast"], or [].
   - "mood_tag": one of ["action", "romance", "suspense", "comedy", "horror", "calm"].
   - "has_particles": boolean (true for atmospheric scenes like embers, sparks, rain, snow).
   - "particle_type": null or one of ["rain", "snow", "sparks", "fire_embers", "dust"].
6. Return STRICTLY a valid JSON array of objects.

JSON format:
[
  {
    "board_number": 1,
    "narration_chunk": "...",
    "environment_name": "...",
    "characters_present": ["..."],
    "props_present": ["..."],
    "view_type": "wide_establishing",
    "camera_movement": "zoom_in",
    "animation_effect": "ken_burns",
    "transition_type": "crossfade",
    "speech_bubbles": [],
    "sfx_tags": [],
    "mood_tag": "action",
    "has_particles": false,
    "particle_type": null,
    "scene_data": {
      "focus_type": "interaction",
      "shot_framing": "medium_shot",
      "primary_subject_id": "char_1",
      "secondary_subject_ids": [],
      "action_description": "...",
      "crowd_density": "sparse",
      "lighting_mood_override": "..."
    }
  }
]
PROMPT;

        try {
            $parsed = $this->callGeminiJson($prompt);

            if (is_array($parsed) && ! empty($parsed)) {
                return $parsed;
            }
        } catch (Exception $e) {
            Log::warning('Gemini storyboard splitting failed, using heuristic fallback', ['error' => $e->getMessage()]);
        }

        return $this->heuristicBoardSplit($scene, $visualStyle);
    }

    /**
     * Persist generated episodes into database for a story.
     *
     * @return array<int, Episode>
     */
    public function createEpisodesForStory(Story $story, int $targetEpisodes = 3): array
    {
        $episodesData = $this->spliceStoryToEpisodes($story, $targetEpisodes);
        $created = [];

        foreach ($episodesData as $index => $data) {
            $episode = Episode::create([
                'story_id' => $story->id,
                'episode_number' => $data['episode_number'] ?? ($index + 1),
                'title' => $data['title'] ?? ('Episode '.($index + 1)),
                'synopsis' => $data['synopsis'] ?? '',
                'narrative_script' => $data['narrative_script'] ?? '',
                'status' => 'draft',
            ]);
            $created[] = $episode;
        }

        $story->update(['status' => 'episodes_created']);

        return $created;
    }

    /**
     * Persist scenes and boards for an entire episode.
     */
    public function generateScenesAndBoardsForEpisode(Episode $episode): Episode
    {
        $scenesData = $this->splitEpisodeToScenes($episode);
        $visualStyle = $episode->story ? $episode->story->visual_style : 'Dark Fantasy Manhwa';
        $contentTitle = $episode->story?->contentTitle;

        // Lookup map for environments
        $envMap = [];
        if ($contentTitle) {
            $envMap = $contentTitle->environments->pluck('id', 'name')->toArray();
        }

        // Delete existing scenes and boards if regenerating
        $episode->boards()->delete();
        $episode->scenes()->delete();

        $globalBoardSeq = 1;

        foreach ($scenesData as $sIndex => $sData) {
            $scene = Scene::create([
                'episode_id' => $episode->id,
                'scene_number' => $sData['scene_number'] ?? ($sIndex + 1),
                'title' => $sData['title'] ?? ('Scene '.($sIndex + 1)),
                'setting' => $sData['setting'] ?? '',
                'mood' => $sData['mood'] ?? '',
                'narration_text' => $sData['narration_text'] ?? '',
            ]);

            $boardsData = $this->splitSceneToBoards($scene, $visualStyle);

            foreach ($boardsData as $bIndex => $bData) {
                $envName = $bData['environment_name'] ?? null;
                $envId = ($envName && isset($envMap[$envName])) ? $envMap[$envName] : null;

                // Fuzzy environment matching if exact map match fails
                if (! $envId && $envName && $contentTitle) {
                    $matchedEnv = $contentTitle->environments->first(fn ($e) => str_contains(strtolower($e->name), strtolower(trim($envName))) ||
                        str_contains(strtolower(trim($envName)), strtolower($e->name))
                    );
                    if ($matchedEnv) {
                        $envId = $matchedEnv->id;
                    }
                }

                if (! $envId && $contentTitle && $contentTitle->environments()->exists()) {
                    $envId = $contentTitle->environments()->first()?->id;
                }

                // Sanitize and resolve characters_present
                $rawChars = $bData['characters_present'] ?? [];
                $sanitizedChars = [];
                $story = $episode->story;
                if ($story) {
                    foreach ($rawChars as $rawName) {
                        $resolved = $story->resolveCharacterByName($rawName);
                        if ($resolved && ! in_array($resolved->name, $sanitizedChars, true)) {
                            $sanitizedChars[] = $resolved->name;
                        }
                    }
                }

                SceneBoard::create([
                    'episode_id' => $episode->id,
                    'scene_id' => $scene->id,
                    'environment_id' => $envId,
                    'characters_present' => $sanitizedChars,
                    'props_present' => $bData['props_present'] ?? [],
                    'board_number' => $bData['board_number'] ?? ($bIndex + 1),
                    'sequence_order' => $globalBoardSeq++,
                    'narration_chunk' => $bData['narration_chunk'] ?? '',
                    'view_type' => $bData['view_type'] ?? 'wide_shot',
                    'camera_movement' => $bData['camera_movement'] ?? 'zoom_in',
                    'animation_effect' => $bData['animation_effect'] ?? 'ken_burns',
                    'transition_type' => $bData['transition_type'] ?? 'crossfade',
                    'speech_bubbles' => $bData['speech_bubbles'] ?? [],
                    'bubble_style' => $bData['bubble_style'] ?? 'manga',
                    'sfx_tags' => $bData['sfx_tags'] ?? [],
                    'mood_tag' => $bData['mood_tag'] ?? ($scene->mood ?: 'action'),
                    'has_particles' => (bool) ($bData['has_particles'] ?? false),
                    'particle_type' => $bData['particle_type'] ?? null,
                    'scene_data' => $bData['scene_data'] ?? [
                        'focus_type' => 'interaction',
                        'shot_framing' => 'medium_shot',
                        'primary_subject_id' => $sanitizedChars[0] ?? null,
                        'secondary_subject_ids' => array_slice($sanitizedChars, 1),
                        'action_description' => $bData['visual_prompt'] ?? ($bData['narration_chunk'] ?? ''),
                        'crowd_density' => 'none',
                        'lighting_mood_override' => '',
                    ],
                    'duration_seconds' => 4.0,
                    'status' => 'pending',
                ]);
            }
        }

        $episode->update(['status' => 'storyboarded']);

        return $episode->load(['scenes', 'boards']);
    }

    /**
     * Send prompt to Gemini and extract clean JSON.
     */
    private function callGeminiJson(string $prompt): array
    {
        if (app()->environment('testing') && ! config('gemini.enable_in_tests', false)) {
            throw new Exception('Gemini is disabled in testing environment to force deterministic heuristic fallbacks.');
        }

        $modelName = env('GEMINI_MODEL', 'gemini-1.5-flash');

        $result = Gemini::generativeModel(model: $modelName)->generateContent($prompt);
        $text = trim($result->text());

        return $this->cleanAndDecodeJson($text);
    }

    /**
     * Clean markdown backticks and decode JSON.
     */
    public function cleanAndDecodeJson(string $raw): array
    {
        $clean = trim($raw);

        if (str_starts_with($clean, '```json')) {
            $clean = substr($clean, 7);
        } elseif (str_starts_with($clean, '```')) {
            $clean = substr($clean, 3);
        }

        if (str_ends_with($clean, '```')) {
            $clean = substr($clean, 0, -3);
        }

        $clean = trim($clean);

        // If surrounded by extra text, find brackets [ ... ]
        $firstBracket = strpos($clean, '[');
        $lastBracket = strrpos($clean, ']');

        if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
            $clean = substr($clean, $firstBracket, $lastBracket - $firstBracket + 1);
        }

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new Exception('Invalid JSON response: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Heuristic fallback when Gemini is unreachable or offline.
     */
    private function heuristicEpisodeSplice(Story $story, int $targetEpisodes): array
    {
        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $story->premise) ?: []),
            fn ($p) => strlen($p) > 0
        ));

        if (empty($paragraphs)) {
            $paragraphs = [$story->premise];
        }

        $count = count($paragraphs);
        $chunkSize = (int) max(1, ceil($count / $targetEpisodes));
        $chunks = array_chunk($paragraphs, $chunkSize);

        $episodes = [];
        foreach ($chunks as $i => $pList) {
            $epNum = $i + 1;
            $script = implode("\n\n", $pList);
            $episodes[] = [
                'episode_number' => $epNum,
                'title' => "{$story->title} - Part {$epNum}",
                'synopsis' => substr($pList[0] ?? $story->title, 0, 140).'...',
                'narrative_script' => $script,
            ];
        }

        return $episodes;
    }

    /**
     * Heuristic scene splitter fallback.
     */
    private function heuristicSceneSplit(Episode $episode, int $targetScenes): array
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', trim($episode->narrative_script ?: '')) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        if (empty($sentences)) {
            $sentences = [$episode->synopsis ?: 'The adventure began in total silence.'];
        }

        $chunkSize = (int) max(1, ceil(count($sentences) / $targetScenes));
        $chunks = array_chunk($sentences, $chunkSize);

        $scenes = [];
        $moods = ['Tense and mysterious', 'Dynamic action', 'Dramatic realization', 'Eerie anticipation'];
        $settings = ['Ancient stone ruins', 'Desolate battleground', 'Gilded throne room', 'Dimly lit alleyway'];

        foreach ($chunks as $i => $sList) {
            $sNum = $i + 1;
            $scenes[] = [
                'scene_number' => $sNum,
                'title' => "Scene {$sNum}: ".substr($sList[0] ?? '', 0, 30).'...',
                'setting' => $settings[$i % count($settings)],
                'mood' => $moods[$i % count($moods)],
                'narration_text' => implode(' ', $sList),
            ];
        }

        return $scenes;
    }

    /**
     * Heuristic storyboard splitter fallback.
     */
    private function heuristicBoardSplit(Scene $scene, string $visualStyle): array
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', trim($scene->narration_text ?: '')) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences)));

        if (empty($sentences)) {
            $sentences = ['The silence was suddenly broken by an ominous hum.'];
        }

        $story = $scene->episode?->story;
        $contentTitle = $story?->contentTitle;
        $availableChars = $story ? $story->getAvailableCharacters() : collect();
        $primaryEnv = $contentTitle?->environments()->first();
        $defaultEnvName = $primaryEnv ? $primaryEnv->name : ($scene->setting ?: '');
        $envTokenSnippet = $primaryEnv ? ', '.$primaryEnv->getConsistencyTokens() : '';

        $viewTypes = ['wide_shot', 'close_up', 'low_angle', 'dutch_angle', 'overhead_view', 'pan_view'];
        $movements = ['zoom_in', 'zoom_out', 'pan_left', 'pan_right', 'vertical_scroll'];

        $boards = [];
        foreach ($sentences as $i => $sentence) {
            $bNum = $i + 1;
            $view = $viewTypes[$i % count($viewTypes)];
            $move = $movements[$i % count($movements)];

            $cleanSnippet = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', substr($sentence, 0, 80)));

            // Only attach characters that are actually mentioned in this sentence
            $presentChars = [];
            $charTokens = [];
            foreach ($availableChars as $char) {
                $nameParts = preg_split('/[\s,\-_()]+/', $char->name) ?: [];
                $matched = (stripos($sentence, $char->name) !== false);
                if (! $matched) {
                    foreach ($nameParts as $part) {
                        if (strlen($part) >= 3 && preg_match('/\b'.preg_quote($part, '/').'\b/i', $sentence)) {
                            $matched = true;
                            break;
                        }
                    }
                }

                if ($matched) {
                    $presentChars[] = $char->name;
                    $charTokens[] = $char->getConsistencyTokens();
                }
            }

            // Fallback: If no characters matched by name but there is only one registered character in the story/world bible,
            // assume this solo character is the protagonist present.
            if (empty($presentChars) && $availableChars->count() === 1) {
                $soloChar = $availableChars->first();
                $presentChars[] = $soloChar->name;
                $charTokens[] = $soloChar->getConsistencyTokens();
            }

            // Only attach props that are actually mentioned in this sentence
            $presentProps = [];
            if ($contentTitle) {
                foreach ($contentTitle->props as $prop) {
                    if (stripos($sentence, $prop->name) !== false) {
                        $presentProps[] = $prop->name;
                    }
                }
            }

            $promptElements = array_filter([
                $visualStyle,
                "{$view} composition",
                $defaultEnvName ? "{$defaultEnvName}{$envTokenSnippet}" : null,
                ! empty($presentChars) ? implode(', ', $charTokens).' featuring '.$cleanSnippet : $cleanSnippet,
            ]);
            $prompt = implode(', ', $promptElements);

            $boards[] = [
                'board_number' => $bNum,
                'narration_chunk' => $sentence,
                'environment_name' => $defaultEnvName,
                'characters_present' => array_values(array_unique($presentChars)),
                'props_present' => array_values(array_unique($presentProps)),
                'view_type' => $view,
                'camera_movement' => $move,
                'animation_effect' => ($i % 3 === 2) ? 'camera_shake' : (($i % 2 === 1) ? 'parallax' : 'ken_burns'),
                'transition_type' => 'crossfade',
                'speech_bubbles' => ! empty($presentChars) ? [
                    [
                        'speaker' => $presentChars[0],
                        'text' => substr($sentence, 0, 50),
                        'style' => 'manga',
                        'position' => 'top-right',
                        'emotion' => 'action',
                    ],
                ] : [],
                'sfx_tags' => ($i % 2 === 1) ? ['slash'] : [],
                'mood_tag' => $scene->mood ?: 'action',
                'has_particles' => false,
                'particle_type' => null,
                'visual_prompt' => $prompt,
            ];
        }

        return $boards;
    }
}
