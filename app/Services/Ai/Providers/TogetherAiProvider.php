<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TogetherAiProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $width = 1024;
        $height = 1024;
        if ($aspectRatio === '9:16') {
            $width = 768;
            $height = 1024;
        } elseif ($aspectRatio === '16:9') {
            $width = 1280;
            $height = 720;
        }

        $baseUrl = rtrim($connection->base_url ?: 'https://api.together.xyz/v1', '/');
        $modelId = $connection->model_id ?: 'black-forest-labs/FLUX.1-schnell';
        $steps = (int) ($connection->options['num_steps'] ?? $connection->options['steps'] ?? (str_contains(strtolower($modelId), 'dev') ? 28 : 20));

        $res = Http::withToken($connection->api_key)
            ->timeout(90)
            ->post("{$baseUrl}/images/generations", [
                'model' => $modelId,
                'prompt' => $prompt,
                'width' => $width,
                'height' => $height,
                'steps' => $steps,
                'response_format' => 'b64_json',
            ]);

        if (! $res->successful()) {
            throw new Exception('Together AI image generation failed: '.$res->body());
        }

        $b64 = $res->json('data.0.b64_json');
        if (! $b64) {
            throw new Exception('Together AI returned empty image data.');
        }

        File::put($outputAbsPath, base64_decode($b64));

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $baseUrl = rtrim($connection->base_url ?: 'https://api.together.xyz/v1', '/');

        $res = Http::withToken($connection->api_key)
            ->timeout(20)
            ->post("{$baseUrl}/images/generations", [
                'model' => $connection->model_id ?: 'black-forest-labs/FLUX.1-schnell',
                'prompt' => 'A small blue circle',
                'width' => 512,
                'height' => 512,
                'steps' => 1,
            ]);

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if (! $res->successful()) {
            throw new Exception($res->json('error.message') ?? $res->body());
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => 'Together AI FLUX active.',
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return false;
    }
}
