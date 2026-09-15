<?php

namespace App\Services;

use App\Models\Character;
use App\Models\ContentTitle;
use App\Models\Environment;
use App\Models\Prop;

class PromptCompilerService
{
    /**
     * Compile a final image prompt from the global Brain Key and Scene Director JSON.
     *
     * @param  ContentTitle|null  $contentTitle  The title providing the global Brain Key
     * @param  array  $sceneData  The structured JSON output from the AI Director
     * @param  array  $customBase  Optional custom Level 1, 2, 5 overrides if contentTitle is null or overridden
     * @return string The compiled image prompt
     */
    public function compilePrompt(?ContentTitle $contentTitle = null, array $sceneData = [], array $customBase = []): string
    {
        $level1 = $customBase['level1'] ?? null;
        $level2 = $customBase['level2'] ?? null;
        $level5 = $customBase['level5'] ?? null;

        if ($contentTitle) {
            $config = $contentTitle->prompt_config ?? [];
            if (empty($level1)) {
                $level1 = $config['level1'] ?? $contentTitle->visual_style;
            }
            if (empty($level2)) {
                $level2 = $config['level2'] ?? $contentTitle->genre;
            }
            if (empty($level5)) {
                $level5 = $config['level5'] ?? null;
            }
        }

        $promptParts = [];

        // 1. Base Art Style (Level 1) & Sub-Genre (Level 2)
        if (! empty($level1)) {
            $promptParts[] = trim($level1);
        }
        if (! empty($level2)) {
            $promptParts[] = trim($level2);
        }

        // 2. Shot & Subject (Level 3 - Dynamic Directing)
        $focusType = $sceneData['focus_type'] ?? 'single_character';
        $shotFraming = $sceneData['shot_framing'] ?? 'medium shot';
        $actionDesc = trim($sceneData['action_description'] ?? '');

        // Resolve Primary Subject
        $primarySubjectId = $sceneData['primary_subject_id'] ?? $sceneData['primary_subject'] ?? null;
        $primarySubjectStr = $this->resolveSubject($primarySubjectId);

        // Resolve Secondary Subjects
        $secondarySubjectsStr = '';
        $secondaries = $sceneData['secondary_subject_ids'] ?? $sceneData['secondary_subjects'] ?? [];
        if (is_string($secondaries) && ! empty($secondaries)) {
            $secondaries = array_map('trim', explode(',', $secondaries));
        }
        if (! empty($secondaries) && is_array($secondaries)) {
            $resolvedSecondaries = [];
            foreach ($secondaries as $secId) {
                $res = $this->resolveSubject($secId);
                if ($res) {
                    $resolvedSecondaries[] = $res;
                }
            }
            if (! empty($resolvedSecondaries)) {
                $secondarySubjectsStr = implode(', ', $resolvedSecondaries);
            }
        }

        // Build focal composition based on focus_type
        if ($focusType === 'environment') {
            $envId = $sceneData['environment_id'] ?? $sceneData['environment'] ?? null;
            $envStr = $this->resolveEnvironment($envId);
            $envClause = $envStr ? "{$shotFraming} of {$envStr}" : "{$shotFraming} of cinematic landscape";
            if ($actionDesc) {
                $envClause .= ", {$actionDesc}";
            }
            if ($primarySubjectStr && $primarySubjectStr !== 'subject') {
                $envClause .= ", featuring {$primarySubjectStr} in distance";
            }
            $promptParts[] = $envClause;
        } elseif ($focusType === 'body_part') {
            $bodyClause = "{$shotFraming} of {$primarySubjectStr}";
            if ($actionDesc) {
                $bodyClause .= ", {$actionDesc}";
            }
            $promptParts[] = $bodyClause;
        } elseif ($focusType === 'prop') {
            $propClause = "{$shotFraming} of {$primarySubjectStr}";
            if ($actionDesc) {
                $propClause .= ", {$actionDesc}";
            }
            $promptParts[] = $propClause;
        } else {
            // Standard single character, interaction, or crowd
            $subjectClause = "{$shotFraming} of {$primarySubjectStr}";
            if ($secondarySubjectsStr) {
                $subjectClause .= " and {$secondarySubjectsStr}";
            }
            if ($actionDesc) {
                $subjectClause .= ", {$actionDesc}";
            }
            $promptParts[] = $subjectClause;
        }

        // 3. Environment & Location (if not already the primary focal environment)
        if ($focusType !== 'environment') {
            $envId = $sceneData['environment_id'] ?? $sceneData['environment'] ?? null;
            if ($envId) {
                $envStr = $this->resolveEnvironment($envId);
                if ($envStr) {
                    $promptParts[] = "in {$envStr}";
                }
            }
        }

        // 4. Crowd Density
        $crowdDensity = $sceneData['crowd_density'] ?? 'none';
        if ($crowdDensity && $crowdDensity !== 'none') {
            $crowdClause = match ($crowdDensity) {
                'sparse' => 'sparse onlookers in blurred background',
                'dense' => 'dense crowd of people in background',
                'bustling' => 'bustling crowded atmosphere in background',
                default => "{$crowdDensity} crowd in background",
            };
            $promptParts[] = $crowdClause;
        }

        // 5. Dynamic Mood & Lighting (Level 4 - Dynamic Scene beat)
        $lighting = $sceneData['lighting_mood_override'] ?? $sceneData['lighting_mood'] ?? null;
        if (! empty($lighting)) {
            $promptParts[] = trim($lighting);
        }

        // 6. Quality & Render Engine Modifiers (Level 5)
        if (! empty($level5)) {
            $promptParts[] = trim($level5);
        }

        return implode(', ', array_filter($promptParts));
    }

    public function resolveSubject(string|int|null $subjectId): string
    {
        if (! $subjectId) {
            return 'subject';
        }

        $subjectStr = trim((string) $subjectId);
        if ($subjectStr === '') {
            return 'subject';
        }

        // Check if it's a numeric ID
        if (is_numeric($subjectStr)) {
            $char = Character::find((int) $subjectStr);
            if ($char) {
                return "[{$char->name}, {$char->appearance}]";
            }
            $prop = Prop::find((int) $subjectStr);
            if ($prop) {
                return "[{$prop->name}, {$prop->appearance}]";
            }
        }

        // Check if it's a character by name
        $char = Character::where('name', $subjectStr)->first();
        if ($char) {
            return "[{$char->name}, {$char->appearance}]";
        }

        // Check if it's a prop by name
        $prop = Prop::where('name', $subjectStr)->first();
        if ($prop) {
            return "[{$prop->name}, {$prop->appearance}]";
        }

        return $subjectStr; // Fallback to raw string if not found
    }

    public function resolveEnvironment(string|int|null $envId): string
    {
        if (! $envId) {
            return '';
        }

        $envStr = trim((string) $envId);
        if ($envStr === '') {
            return '';
        }

        if (is_numeric($envStr)) {
            $env = Environment::find((int) $envStr);
            if ($env) {
                return "[{$env->name}, {$env->setting_type}, {$env->lighting}]";
            }
        }

        $env = Environment::where('name', $envStr)->first();
        if ($env) {
            return "[{$env->name}, {$env->setting_type}, {$env->lighting}]";
        }

        return $envStr; // Fallback
    }
}
