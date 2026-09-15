<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GeminiImageProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $apiKey = $connection->api_key ?: env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new Exception('Google Gemini / AI Studio API key is not set.');
        }

        $model = $connection->model_id ?: 'imagen-3.0-generate-002';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predict?key={$apiKey}";

        $res = Http::timeout(90)->post($url, [
            'instances' => [
                ['prompt' => $prompt],
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => $aspectRatio === '9:16' ? '9:16' : ($aspectRatio === '1:1' ? '1:1' : '16:9'),
                'outputMimeType' => 'image/png',
            ],
        ]);

        if (! $res->successful()) {
            throw new Exception('Google AI Studio image generation failed ('.$res->status().'): '.$res->body());
        }

        $b64 = $res->json('predictions.0.bytesBase64Encoded');
        if (! $b64) {
            throw new Exception('Google AI Studio returned no image data.');
        }

        File::put($outputAbsPath, base64_decode($b64));

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $apiKey = $connection->api_key ?: env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            throw new Exception('Google Gemini API key is missing.');
        }

        $res = Http::timeout(15)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if (! $res->successful()) {
            throw new Exception('Google AI Studio authentication failed: '.$res->status());
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => 'Google AI Studio API key verified.',
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return true;
    }
}
