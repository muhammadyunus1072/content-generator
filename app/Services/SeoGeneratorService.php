<?php

namespace App\Services;

use App\Models\EpubProject;
use App\Models\Story;
use App\Services\Ai\AiModelManagerService;
use Exception;
use Illuminate\Support\Facades\Log;

class SeoGeneratorService
{
    public function __construct(
        protected AiModelManagerService $aiManager
    ) {}

    /**
     * Generate complete SEO and social metadata kit for a Story.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     tags: array<int, string>,
     *     hashtags: array<int, string>,
     *     hook_line: string
     * }
     */
    public function generateForStory(Story $story): array
    {
        $contentTitle = $story->contentTitle;
        $genre = $contentTitle?->genre ?? 'Entertainment';
        $visualStyle = $contentTitle?->visual_style ?? 'Cinematic';
        $premise = $story->premise ?? $story->title;

        // Gather chapter/episode context
        $episodesInfo = $story->episodes->map(function ($ep, $idx) {
            return 'Chapter '.($idx + 1).': '.$ep->title.' - '.($ep->summary ?? '');
        })->implode("\n");

        return $this->generateMetadata(
            title: $story->title,
            premise: $premise,
            genre: $genre,
            visualStyle: $visualStyle,
            context: $episodesInfo
        );
    }

    /**
     * Generate complete SEO and social metadata kit for an EpubProject.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     tags: array<int, string>,
     *     hashtags: array<int, string>,
     *     hook_line: string
     * }
     */
    public function generateForEpub(EpubProject $project): array
    {
        $context = 'Total Scenes: '.$project->total_scenes.' scenes rendered with narration voice '.$project->voice_name;

        return $this->generateMetadata(
            title: $project->title,
            premise: 'Complete audiobook and narrated summary of '.$project->title,
            genre: 'Book Summary & Audiobook',
            visualStyle: 'Narrated Storybook',
            context: $context
        );
    }

    /**
     * Core generator calling AI text model or fallback.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     tags: array<int, string>,
     *     hashtags: array<int, string>,
     *     hook_line: string
     * }
     */
    public function generateMetadata(
        string $title,
        string $premise,
        string $genre,
        string $visualStyle,
        string $context = ''
    ): array {
        $prompt = <<<PROMPT
You are a world-class YouTube growth strategist, TikTok viral retention consultant, and SEO copywriter.
Analyze the following video concept and produce an ultra-optimized SEO and monetization metadata package.

--- CONCEPT DETAILS ---
Title / Working Title: {$title}
Premise: {$premise}
Genre: {$genre}
Visual Style: {$visualStyle}
Context: {$context}

--- REQUIREMENTS ---
1. "title": A high-CTR, irresistible YouTube title under 95 characters. Use curiosity gaps, power words, and clean capitalization.
2. "description": A high-converting YouTube description (around 600-1200 characters) containing:
   - First 2 lines: Powerful summary hook with relevant keywords for YouTube search indexing.
   - Chapter Breakdown / Timestamps placeholder (00:00 Intro, 01:30 Awakening, etc.).
   - Monetization Call to Action (Affiliate link disclosure placeholder, Patreon / Discord community invite).
   - Relevant hashtags at the bottom.
3. "tags": An array of 12-18 high-volume search tags (as strings).
4. "hashtags": An array of 5-8 viral TikTok / Shorts hashtags (e.g. ["#manhwa", "#anime", "#storytime"]).
5. "hook_line": The exact first 2 seconds spoken sentence or on-screen text hook that guarantees 90%+ retention through the first 5 seconds.

Return ONLY a valid JSON object matching this schema:
{
  "title": "string",
  "description": "string",
  "tags": ["tag1", "tag2"],
  "hashtags": ["#tag1", "#tag2"],
  "hook_line": "string"
}
PROMPT;

        $fallback = [
            'title' => $title.' - Full Story Breakdown (Explained)',
            'description' => "Welcome to the full story breakdown of {$title}.\n\nIn this episode, we dive deep into the characters, plot twists, and hidden lore that make this world unforgettable.\n\n⏱️ TIMESTAMPS:\n00:00 - Introduction & Hook\n01:15 - The Incident Begins\n04:30 - Rising Conflict\n07:45 - The Climax & Revelations\n\n🔔 Subscribe for more weekly recap stories and animated deep dives!\n💬 Comment below: What was your favorite moment?\n\n#Storytime #Recap #Animation #Viral #Shorts",
            'tags' => [
                strtolower(str_replace(' ', '', $title)),
                'recap',
                'story breakdown',
                'audiobook',
                'summary',
                'explained',
                'full story',
                'animation',
                'webtoon recap',
                'entertainment',
                'lore',
                'best stories',
            ],
            'hashtags' => ['#storytime', '#recap', '#viral', '#foryou', '#trending'],
            'hook_line' => "You won't believe what happens in the next 60 seconds of this story...",
        ];

        try {
            $raw = $this->aiManager->generateText($prompt);

            // Clean json codeblocks
            $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw = preg_replace('/\s*```$/', '', trim($raw));

            $decoded = json_decode($raw, true);

            if (is_array($decoded) && isset($decoded['title'], $decoded['description'])) {
                return [
                    'title' => (string) $decoded['title'],
                    'description' => (string) $decoded['description'],
                    'tags' => is_array($decoded['tags'] ?? null) ? array_values(array_map('strval', $decoded['tags'])) : $fallback['tags'],
                    'hashtags' => is_array($decoded['hashtags'] ?? null) ? array_values(array_map('strval', $decoded['hashtags'])) : $fallback['hashtags'],
                    'hook_line' => (string) ($decoded['hook_line'] ?? $fallback['hook_line']),
                ];
            }
        } catch (Exception $e) {
            Log::warning('SeoGeneratorService AI generation failed: '.$e->getMessage());
        }

        return $fallback;
    }
}
