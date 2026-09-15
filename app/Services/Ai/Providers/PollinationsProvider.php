<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PollinationsProvider implements ImageProviderInterface
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
        $encodedPrompt = rawurlencode($prompt);
        $model = $connection->model_id ?: 'flux';
        $baseUrl = rtrim($connection->base_url ?: 'https://image.pollinations.ai', '/');

        $url = "{$baseUrl}/prompt/{$encodedPrompt}?width={$width}&height={$height}&nologo=true&model={$model}";

        $request = Http::timeout(90);
        if (! empty($connection->api_key)) {
            $request = $request->withToken($connection->api_key);
        }

        $res = $request->get($url);

        if ($res->successful() && strlen($res->body()) > 1000) {
            File::put($outputAbsPath, $res->body());

            return true;
        }

        throw new Exception('Pollinations API error: '.$res->status());
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $baseUrl = rtrim($connection->base_url ?: 'https://image.pollinations.ai', '/');
        $url = "{$baseUrl}/prompt/ping%20test?width=64&height=64&nologo=true";

        $request = Http::timeout(15);
        if (! empty($connection->api_key)) {
            $request = $request->withToken($connection->api_key);
        }

        $res = $request->get($url);
        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if (! $res->successful()) {
            throw new Exception('Pollinations returned HTTP '.$res->status());
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => 'Pollinations public image gateway online.',
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return false;
    }
}
