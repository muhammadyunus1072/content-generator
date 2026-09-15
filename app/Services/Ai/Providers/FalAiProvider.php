<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class FalAiProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $baseUrl = rtrim($connection->base_url ?: 'https://fal.run', '/');
        $modelId = $connection->model_id ?: 'fal-ai/flux-pro/v1.1';
        $url = "{$baseUrl}/{$modelId}";

        $width = $aspectRatio === '9:16' ? 768 : 1344;
        $height = $aspectRatio === '9:16' ? 1344 : 768;
        if ($aspectRatio === '1:1') {
            $width = 1024;
            $height = 1024;
        }

        $payload = [
            'prompt' => $prompt,
            'image_size' => [
                'width' => $width,
                'height' => $height,
            ],
            'num_images' => 1,
            'enable_safety_checker' => true,
        ];

        // Attach reference images if present
        if (! empty($referenceImagePaths)) {
            $encodedRefs = [];
            foreach (array_slice($referenceImagePaths, 0, 4) as $path) {
                if (File::exists($path)) {
                    $mime = mime_content_type($path) ?: 'image/png';
                    $encodedRefs[] = 'data:'.$mime.';base64,'.base64_encode(File::get($path));
                }
            }

            if (! empty($encodedRefs)) {
                // If single image ref, set image_url; if multiple, set image_references
                $payload['image_url'] = $encodedRefs[0];
                $payload['image_references'] = $encodedRefs;
            }
        }

        $res = Http::withHeaders([
            'Authorization' => 'Key '.$connection->api_key,
            'Content-Type' => 'application/json',
        ])
            ->timeout(120)
            ->post($url, $payload);

        if (! $res->successful()) {
            throw new Exception('fal.ai error ('.$res->status().'): '.$res->body());
        }

        $imageUrl = $res->json('images.0.url');
        if (! $imageUrl) {
            throw new Exception('fal.ai did not return an image URL.');
        }

        $imageBinary = Http::timeout(60)->get($imageUrl)->body();
        if (strlen($imageBinary) < 1000) {
            throw new Exception('Failed to download image from fal.ai.');
        }

        File::put($outputAbsPath, $imageBinary);

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $baseUrl = rtrim($connection->base_url ?: 'https://fal.run', '/');
        $modelId = $connection->model_id ?: 'fal-ai/flux/dev';

        // Fal.ai uses 'Key <key>'
        $res = Http::withHeaders([
            'Authorization' => 'Key '.$connection->api_key,
        ])
            ->timeout(15)
            ->get("{$baseUrl}/{$modelId}");

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        // Even if 405 Method Not Allowed (since GET is used), 401/403 means auth failed
        if ($res->status() === 401 || $res->status() === 403) {
            throw new Exception('fal.ai authentication failed. Check your API key.');
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => 'fal.ai connection verified.',
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return true;
    }
}
