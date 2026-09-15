<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplicateProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $modelId = $connection->model_id ?: 'black-forest-labs/flux-1.1-pro';
        $parts = explode('/', trim($modelId, '/'));
        $owner = $parts[0] ?? 'black-forest-labs';
        $name = $parts[1] ?? 'flux-1.1-pro';

        $url = "https://api.replicate.com/v1/models/{$owner}/{$name}/predictions";

        $input = [
            'prompt' => $prompt,
            'aspect_ratio' => $aspectRatio,
            'output_format' => 'png',
        ];

        // Attach first reference image if supported
        if (! empty($referenceImagePaths)) {
            $firstPath = $referenceImagePaths[0];
            if (File::exists($firstPath)) {
                $mime = mime_content_type($firstPath) ?: 'image/png';
                $input['image'] = 'data:'.$mime.';base64,'.base64_encode(File::get($firstPath));
            }
        }

        // Determine configured webhook URL
        $webhookUrl = config('services.replicate.webhook_url')
            ?: env('REPLICATE_WEBHOOK_URL')
            ?: env('AI_WEBHOOK_URL')
            ?: url('/api/webhooks/replicate');

        $payload = [
            'input' => $input,
            'webhook' => $webhookUrl,
            'webhook_events_filter' => ['completed'],
        ];

        // Call Replicate with 'Prefer: wait=60' so HTTP waits for completion, while webhook is also notified
        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$connection->api_key,
            'Content-Type' => 'application/json',
            'Prefer' => 'wait=60',
        ])
            ->timeout(75)
            ->post($url, $payload);

        if (! $res->successful()) {
            throw new Exception('Replicate prediction error ('.$res->status().'): '.$res->body());
        }

        $data = $res->json();
        $predictionId = $data['id'] ?? null;

        // Save mapping for Webhook callback resolution
        if ($predictionId) {
            Cache::put("replicate_prediction_{$predictionId}", [
                'output_path' => $outputAbsPath,
                'created_at' => now()->toIso8601String(),
            ], now()->addHours(2));
        }

        // If completed immediately via 'Prefer: wait'
        if (($data['status'] ?? '') === 'succeeded') {
            $outputUrl = is_array($data['output'] ?? null) ? ($data['output'][0] ?? null) : ($data['output'] ?? null);
            if ($outputUrl) {
                $img = Http::timeout(60)->get($outputUrl)->body();
                if (strlen($img) > 1000) {
                    File::put($outputAbsPath, $img);

                    return true;
                }
            }
        }

        // If status is still processing or queued, log notification
        Log::info('Replicate prediction initiated, awaiting webhook callback or completion', [
            'prediction_id' => $predictionId,
            'webhook' => $webhookUrl,
            'status' => $data['status'] ?? 'pending',
        ]);

        return true;
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $modelId = $connection->model_id ?: 'black-forest-labs/flux-1.1-pro';
        $parts = explode('/', trim($modelId, '/'));
        $owner = $parts[0] ?? 'black-forest-labs';
        $name = $parts[1] ?? 'flux-1.1-pro';

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$connection->api_key,
        ])
            ->timeout(15)
            ->get("https://api.replicate.com/v1/models/{$owner}/{$name}");

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if ($res->status() === 401 || $res->status() === 403) {
            throw new Exception('Replicate API token is invalid or unauthorized.');
        }

        if (! $res->successful()) {
            throw new Exception('Replicate API returned status '.$res->status().': '.$res->body());
        }

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => "Replicate authenticated. Model {$modelId} ready.",
        ];
    }

    public function supportsReferenceImages(): bool
    {
        return true;
    }
}
