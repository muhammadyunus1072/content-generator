<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class HuggingFaceProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $baseUrl = rtrim($connection->base_url ?: 'https://api-inference.huggingface.co/models', '/');
        $model = $connection->model_id ?: 'black-forest-labs/FLUX.1-schnell';
        $url = "{$baseUrl}/{$model}";

        $request = Http::timeout(120);
        if (! empty($connection->api_key)) {
            $request = $request->withToken($connection->api_key);
        }

        $res = $request->post($url, [
            'inputs' => $prompt,
            'parameters' => [
                'wait_for_model' => true,
            ],
        ]);

        if (! $res->successful()) {
            throw new Exception('HuggingFace error ('.$res->status().'): '.$res->body());
        }

        if (strlen($res->body()) < 1000) {
            throw new Exception('HuggingFace returned an invalid or empty image response.');
        }

        File::put($outputAbsPath, $res->body());

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);

        $request = Http::timeout(15);
        if (! empty($connection->api_key)) {
            $request = $request->withToken($connection->api_key);
        }

        $model = $connection->model_id ?: 'black-forest-labs/FLUX.1-schnell';
        $res = $request->get("https://huggingface.co/api/models/{$model}");

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if (! $res->successful()) {
            throw new Exception('HuggingFace model check failed: '.$res->status());
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => "HuggingFace model {$model} reachable.",
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return false;
    }
}
