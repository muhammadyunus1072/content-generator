<?php

namespace App\Services;

use App\Models\Character;
use App\Models\ContentTitle;
use Exception;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class CharacterBibleService
{
    /**
     * Generate an AI-assisted character profile including personality, appearance, and color theme.
     *
     * @return array{name: string, gender: string, age: int, role: string, appearance: string, prompt_keywords: string, personality: string, color_theme: string}
     */
    public function generateCharacterProfile(string $name, string $genre = 'Action Fantasy', ?string $role = 'supporting'): array
    {
        $prompt = <<<PROMPT
You are a master character designer and world bible builder for serialized manhwa, anime, and webtoons.
Create a rich, consistent character profile for a story in the {$genre} genre.

Character Name: {$name}
Suggested Role: {$role}

Generate a strictly valid JSON object with the following fields:
- name: string (e.g. "{$name}")
- gender: string ("male", "female", or "other")
- age: integer (e.g. 17-35)
- role: string ("protagonist", "antagonist", "supporting", "narrator", or "mentor")
- appearance: string (2-3 sentences of vivid visual description suitable for artist reference)
- prompt_keywords: string (SDXL/Flux positive prompt tokens with commas, including outfit, hair, eyes, aura)
- personality: string (key traits, motivations, demeanor, speaking style)
- color_theme: string (hex or color name, e.g. "#4F46E5" or "Deep Obsidian & Neon Cyan")

Return strictly JSON without markdown code blocks.
PROMPT;

        try {
            $response = Gemini::generativeModel('gemini-2.5-flash')->generateContent($prompt);
            $text = trim($response->text());
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            $parsed = json_decode(trim($text), true);

            if (is_array($parsed) && ! empty($parsed['name'])) {
                return [
                    'name' => $parsed['name'] ?? $name,
                    'gender' => $parsed['gender'] ?? 'male',
                    'age' => (int) ($parsed['age'] ?? 24),
                    'role' => $parsed['role'] ?? $role,
                    'appearance' => $parsed['appearance'] ?? '',
                    'prompt_keywords' => $parsed['prompt_keywords'] ?? '',
                    'personality' => $parsed['personality'] ?? '',
                    'color_theme' => $parsed['color_theme'] ?? '#3B82F6',
                ];
            }
        } catch (Exception $e) {
            Log::warning('Gemini character profile generation fallback', ['error' => $e->getMessage()]);
        }

        // Fallback default profile
        return [
            'name' => $name,
            'gender' => 'male',
            'age' => 24,
            'role' => $role ?? 'supporting',
            'appearance' => 'Sharp features, determined gaze, stylized manhwa hero aesthetics with dramatic lighting.',
            'prompt_keywords' => "{$name}, manhwa anime style, highly detailed, sharp jawline, glowing eyes, cinematic lighting, 8k",
            'personality' => 'Focused, calm under pressure, decisive.',
            'color_theme' => '#3B82F6',
        ];
    }

    /**
     * Resolve TTS voice configuration for a character.
     *
     * @return array{voice_id: string, tts_provider: string, tts_rate: string, tts_pitch: string}
     */
    public function resolveVoiceForCharacter(Character $character, string $defaultVoice = 'id-ID-ArdiNeural'): array
    {
        $voiceId = $character->voice_id;

        if (empty($voiceId)) {
            // Pick based on gender if available
            if ($character->gender === 'female') {
                $voiceId = 'id-ID-GadisNeural';
            } else {
                $voiceId = $defaultVoice;
            }
        }

        return [
            'voice_id' => $voiceId,
            'tts_provider' => $character->tts_provider ?? 'edge-tts',
            'tts_rate' => '+0%',
            'tts_pitch' => '+0Hz',
        ];
    }

    /**
     * Export complete character bible for a ContentTitle as structured JSON.
     *
     * @return array<int, array{id: int, name: string, role: ?string, gender: ?string, age: ?int, appearance: ?string, personality: ?string, prompt_keywords: ?string, color_theme: ?string, voice_id: ?string, tts_provider: ?string, relationships: ?array}>
     */
    public function exportBible(ContentTitle $contentTitle): array
    {
        return $contentTitle->characters()
            ->get()
            ->map(fn (Character $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'role' => $c->role,
                'gender' => $c->gender,
                'age' => $c->age,
                'appearance' => $c->appearance,
                'personality' => $c->personality,
                'prompt_keywords' => $c->prompt_keywords,
                'color_theme' => $c->color_theme,
                'voice_id' => $c->voice_id,
                'tts_provider' => $c->tts_provider,
                'relationships' => $c->relationships,
            ])
            ->toArray();
    }
}
