<?php

namespace App\Services;

use App\Models\Video;
use Exception;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;

class ContentGenerationService
{
    /**
     * Generate suggested short clips from the video transcription.
     *
     * @return array<array{title: string, description: string, start_seconds: float, end_seconds: float, tags: array<string>}>
     *
     * @throws Exception
     */
    public function generateClips(Video $video): array
    {
        $transcription = $video->transcription;

        if (! $transcription || empty($transcription->transcript_text)) {
            throw new Exception('No transcription text available for this video.');
        }

        // Build prompt from SRT subtitle data
        $srtTranscript = $transcription->toSrt();
        $prompt = $this->buildPrompt($srtTranscript);

        $modelName = env('GEMINI_MODEL', 'gemini-1.5-flash');

        // Define exact OpenAPI response schema for structured JSON output
        $schema = new Schema(
            type: DataType::ARRAY,
            description: 'Suggested clips matching engaging moments.',
            items: new Schema(
                type: DataType::OBJECT,
                required: ['title', 'description', 'start_seconds', 'end_seconds', 'tags'],
                properties: [
                    'title' => new Schema(type: DataType::STRING, description: 'Catchy title highlighting the clip hook'),
                    'description' => new Schema(type: DataType::STRING, description: 'Engaging caption summarizing the moment'),
                    'start_seconds' => new Schema(type: DataType::NUMBER, description: 'Start time in seconds'),
                    'end_seconds' => new Schema(type: DataType::NUMBER, description: 'End time in seconds'),
                    'tags' => new Schema(
                        type: DataType::ARRAY,
                        description: 'Relevant viral tags or keywords',
                        items: new Schema(type: DataType::STRING)
                    ),
                ]
            )
        );

        // Send content generation request to Gemini in JSON mode with schema constraints
        $result = Gemini::generativeModel(model: $modelName)
            ->withGenerationConfig(
                new GenerationConfig(
                    responseMimeType: ResponseMimeType::APPLICATION_JSON,
                    responseSchema: $schema
                )
            )
            ->generateContent($prompt);

        $responseText = trim($result->text());

        // Remove markdown wraps if Gemini returned them
        if (str_starts_with($responseText, '```json')) {
            $responseText = substr($responseText, 7);
        }
        if (str_starts_with($responseText, '```')) {
            $responseText = substr($responseText, 3);
        }
        if (str_ends_with($responseText, '```')) {
            $responseText = substr($responseText, 0, -3);
        }
        $responseText = trim($responseText);

        $data = json_decode($responseText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode Gemini JSON response: '.json_last_error_msg().'. Raw: '.$responseText);
        }

        // Extract clips list
        $clips = [];
        if (isset($data['clips'])) {
            $clips = $data['clips'];
        } elseif (is_array($data)) {
            $clips = $data;
        } else {
            throw new Exception('Unexpected response shape from Gemini: '.json_encode($data));
        }

        // Standardize output formats and filter out invalid durations
        $standardizedClips = [];
        foreach ($clips as $clip) {
            $start = (float) ($clip['start_seconds'] ?? $clip['start'] ?? 0.0);
            $end = (float) ($clip['end_seconds'] ?? $clip['end'] ?? 0.0);

            // Skip invalid clip intervals
            if ($start < 0 || $end <= $start) {
                continue;
            }

            $title = $clip['title'] ?? $clip['name'] ?? 'Suggested Clip';
            $description = $clip['description'] ?? $clip['summary'] ?? '';
            $tags = $clip['tags'] ?? $clip['keywords'] ?? [];

            $standardizedClips[] = [
                'title' => (string) $title,
                'description' => (string) $description,
                'start_seconds' => $start,
                'end_seconds' => $end,
                'tags' => (array) $tags,
            ];
        }

        return $standardizedClips;
    }

    /**
     * Compile SRT timeline transcripts into prompt instructions.
     */
    private function buildPrompt(string $srtTranscript): string
    {
        $prompt = "You are a professional video producer, social media editor, and virality expert.\n";
        $prompt .= "Analyze the video/podcast SRT subtitle timeline below and suggest 3-5 distinct highly engaging, standalone short-form clips (e.g. YouTube Shorts, Instagram Reels, TikToks).\n\n";

        $prompt .= "Rules for clipping:\n";
        $prompt .= "- Each clip duration must be between 15 and 90 seconds.\n";
        $prompt .= "- Each clip must cover a complete thought, hook, or cohesive topic. Avoid clipping in the middle of a speech turn.\n";
        $prompt .= "- Start and end seconds must align with the timestamps provided in the SRT segments.\n\n";

        $prompt .= "Here is the SRT transcription:\n";
        $prompt .= $srtTranscript."\n";

        $prompt .= "\nInstructions:\n";
        $prompt .= "Return the response strictly as a JSON array of objects. Do not wrap the JSON output in markdown backticks or include any text outside the brackets.\n";
        $prompt .= "Each object in the array must follow this schema:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"title\": \"Catchy title highlighting the clip hook\",\n";
        $prompt .= "    \"description\": \"Engaging caption summarizing the moment\",\n";
        $prompt .= "    \"start_seconds\": 10.50,\n";
        $prompt .= "    \"end_seconds\": 45.30,\n";
        $prompt .= "    \"tags\": [\"viral\", \"podcast\"]\n";
        $prompt .= "  }\n";
        $prompt .= "]\n";

        return $prompt;
    }
}
