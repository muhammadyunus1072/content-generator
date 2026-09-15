<?php

namespace App\Services;

use App\Services\Ai\AiModelManagerService;
use Exception;
use Illuminate\Support\Facades\Log;

class WorldBibleGeneratorService
{
    /**
     * Generate structured character sheet from a user concept hint.
     *
     * @return array{name: string, gender: string, age: int, prompt_keywords: string, appearance: string}
     */
    public function generateCharacter(string $hint, string $genre = 'Action Fantasy', string $visualStyle = 'Dark Fantasy Manhwa'): array
    {
        $prompt = <<<PROMPT
You are an expert character designer and prompt engineer.
Create a structured character sheet for a {$genre} story in {$visualStyle} style based strictly on this user concept:
"{$hint}"

STRICT FIDELITY & ZERO HALLUCINATION DIRECTIVES:
1. Derive all attributes exclusively from the user's concept idea. Do not invent unrelated lore, items, or identities.
2. Provide:
   - name: character name fitting the concept
   - gender: "female", "male", "other", or "" if unspecified
   - age: integer or null
   - appearance: 2-3 sentences describing appearance derived from the concept
   - prompt_keywords: concentrated visual tokens derived from the idea and visual style.
3. Return STRICTLY a valid JSON object. Do not include markdown code block tags or surrounding text.

JSON format:
{
  "name": "Character Name",
  "gender": "male or female or unspecified",
  "age": 20,
  "appearance": "Description matching concept...",
  "prompt_keywords": "visual tokens matching concept, {$visualStyle}"
}
PROMPT;

        return $this->callGeminiJson($prompt, [
            'name' => 'Character',
            'gender' => '',
            'age' => null,
            'appearance' => $hint,
            'prompt_keywords' => $hint.', '.$visualStyle,
        ]);
    }

    /**
     * Generate structured environment/location from a user concept hint.
     *
     * @return array{name: string, setting_type: string, time_of_day: string, atmosphere: string, lighting: string, prompt_keywords: string, description: string}
     */
    public function generateEnvironment(string $hint, string $genre = 'Action Fantasy', string $visualStyle = 'Dark Fantasy Manhwa'): array
    {
        $prompt = <<<PROMPT
You are a lead environment concept artist and visual director.
Create a structured environment / location setting for a {$genre} story in {$visualStyle} style based strictly on this user concept:
"{$hint}"

STRICT FIDELITY & ZERO HALLUCINATION DIRECTIVES:
1. Derive all setting details exclusively from the user's concept idea. Do not invent unrelated lore or objects.
2. Provide:
   - name: atmospheric location name matching the concept
   - setting_type: one of "interior", "exterior", "urban", "fantasy", "sci-fi"
   - time_of_day: one of "night", "day", "golden_hour", "twilight", "subterranean"
   - atmosphere: 1 sentence describing mood and sensory details from the concept
   - lighting: 1 sentence describing light sources and shadow depth
   - prompt_keywords: concentrated visual tokens for the background environment (no characters).
   - description: 1-2 sentence lore summary
3. Return STRICTLY a valid JSON object.

JSON format:
{
  "name": "Location Name",
  "setting_type": "interior",
  "time_of_day": "day",
  "atmosphere": "Atmosphere matching concept...",
  "lighting": "Lighting matching concept...",
  "prompt_keywords": "environment visual tokens matching concept, {$visualStyle}",
  "description": "Lore summary matching concept..."
}
PROMPT;

        return $this->callGeminiJson($prompt, [
            'name' => 'Environment',
            'setting_type' => 'interior',
            'time_of_day' => 'day',
            'atmosphere' => 'Atmospheric setting',
            'lighting' => 'Natural lighting',
            'prompt_keywords' => $hint.', '.$visualStyle,
            'description' => $hint,
        ]);
    }

    /**
     * Generate structured key prop / item from a user concept hint.
     *
     * @return array{name: string, category: string, description: string, prompt_keywords: string}
     */
    public function generateProp(string $hint, string $genre = 'Action Fantasy', string $visualStyle = 'Dark Fantasy Manhwa'): array
    {
        $prompt = <<<PROMPT
You are a conceptual prop and item designer.
Create a key item / prop sheet for a {$genre} story in {$visualStyle} style based strictly on this user concept:
"{$hint}"

STRICT FIDELITY & ZERO HALLUCINATION DIRECTIVES:
1. Derive all prop details exclusively from the user's concept idea. Do not invent unrelated lore, magical abilities, or items.
2. Provide:
   - name: evocative prop or item name matching the concept
   - category: one of "weapon", "artifact", "tech", "clothing", "vehicle", "gear"
   - description: 1-2 sentences describing its design and function based on the concept
   - prompt_keywords: concise visual tokens describing the item (isolated focus, materials, lighting).
3. Return STRICTLY a valid JSON object.

JSON format:
{
  "name": "Item Name",
  "category": "gear",
  "description": "Item description matching concept...",
  "prompt_keywords": "item visual tokens matching concept, isolated product focus, {$visualStyle}"
}
PROMPT;

        return $this->callGeminiJson($prompt, [
            'name' => 'Prop Item',
            'category' => 'gear',
            'description' => $hint,
            'prompt_keywords' => $hint.', '.$visualStyle,
        ]);
    }

    /**
     * Generate structured art direction guidelines for a Content Title.
     *
     * @return array{color_palette: string, lighting_mood: string, camera_framing: string}
     */
    public function generateArtDirection(string $genre = 'Action Fantasy', string $visualStyle = 'Dark Fantasy Manhwa'): array
    {
        $prompt = <<<PROMPT
You are an executive art director for a visual story.
Provide visual art direction guidelines for a {$genre} show in {$visualStyle} style.

Instructions:
1. Provide:
   - color_palette: 3-5 distinct harmonious colors
   - lighting_mood: atmospheric lighting description
   - camera_framing: camera composition style
2. Return STRICTLY a valid JSON object.

JSON format:
{
  "color_palette": "Harmonious color palette...",
  "lighting_mood": "Atmospheric lighting description...",
  "camera_framing": "Camera framing and composition style..."
}
PROMPT;

        return $this->callGeminiJson($prompt, [
            'color_palette' => 'Cohesive palette matching visual style',
            'lighting_mood' => 'Atmospheric lighting',
            'camera_framing' => 'Dynamic cinematic framing',
        ]);
    }

    public function __construct(
        protected ?AiModelManagerService $aiManager = null
    ) {
        $this->aiManager = $aiManager ?? app(AiModelManagerService::class);
    }

    /**
     * Call AI Text Model and parse strictly to array.
     */
    protected function callGeminiJson(string $prompt, array $fallback): array
    {
        try {
            $raw = $this->aiManager->generateText($prompt);

            // Remove markdown backticks if returned
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);

            $decoded = json_decode($raw, true);

            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        } catch (Exception $e) {
            Log::warning('WorldBibleGeneratorService AI call failed: '.$e->getMessage());
        }

        return $fallback;
    }
}
