<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class StabilityAiProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $baseUrl = rtrim($connection->base_url ?: 'https://api.stability.ai/v2beta', '/');
        $model = $connection->model_id ?: 'sd3.5-flash';
        $endpoint = "{$baseUrl}/stable-image/generate/sd3";

        $hasRefImage = ! empty($referenceImagePaths) && File::exists($referenceImagePaths[0]);

        $request = Http::withHeaders([
            'Authorization' => 'Bearer '.$connection->api_key,
            'Accept' => 'image/*',
        ])->timeout(90);

        $params = [
            'prompt' => $prompt,
            'model' => $model,
            'aspect_ratio' => $aspectRatio,
            'output_format' => 'png',
            'mode' => $hasRefImage ? 'image-to-image' : 'text-to-image',
        ];

        if ($hasRefImage) {
            $refPath = $referenceImagePaths[0];
            $params['strength'] = 0.65;
            $res = $request->attach('image', File::get($refPath), basename($refPath))
                ->post($endpoint, $params);
        } else {
            $res = $request->asMultipart()->post($endpoint, $params);
        }

        if (! $res->successful()) {
            throw new Exception('Stability AI error ('.$res->status().'): '.$res->body());
        }

        if (strlen($res->body()) < 1000) {
            throw new Exception('Stability AI returned an empty or invalid image.');
        }

        File::put($outputAbsPath, $res->body());

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$connection->api_key,
        ])
            ->timeout(15)
            ->get('https://api.stability.ai/v1/user/balance');

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if ($res->status() === 401 || $res->status() === 403) {
            throw new Exception('Stability AI API key is invalid.');
        }

        if (! $res->successful()) {
            throw new Exception('Stability AI returned status '.$res->status().': '.$res->body());
        }

        $credits = $res->json('credits');

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => "Stability AI active. Available credits: {$credits}",
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return true;
    }
}
