<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DockerReplicateImageProvider implements ImageProviderInterface
{
    /**
     * Generate an image for the given prompt and save it to the specified output path.
     */
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        File::ensureDirectoryExists(dirname($outputAbsPath));

        $predictionUrl = $this->getPredictionsUrl($connection);
        [$width, $height] = $this->calculateDimensions($aspectRatio, $connection->options ?? []);

        $options = $connection->options ?? [];
        $steps = (int) ($options['num_steps'] ?? $options['num_inference_steps'] ?? 20);
        $guidance = (float) ($options['guidance'] ?? $options['guidance_scale'] ?? 7.0);

        $input = [
            'prompt' => $prompt,
            'width' => $width,
            'height' => $height,
            'num_outputs' => 1,
            'guidance_scale' => $guidance,
            'num_inference_steps' => $steps,
        ];

        // Attach first reference image if supported
        if (! empty($referenceImagePaths)) {
            $firstPath = $referenceImagePaths[0];
            if (File::exists($firstPath)) {
                $mime = mime_content_type($firstPath) ?: 'image/png';
                $input['image'] = 'data:'.$mime.';base64,'.base64_encode(File::get($firstPath));
            }
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (! empty($connection->api_key)) {
            $headers['Authorization'] = 'Bearer '.$connection->api_key;
        }

        Log::info('Dispatching local Docker Replicate prediction', [
            'url' => $predictionUrl,
            'dimensions' => "{$width}x{$height}",
            'steps' => $steps,
        ]);

        $res = Http::withHeaders($headers)
            ->timeout(180)
            ->post($predictionUrl, [
                'input' => $input,
            ]);

        if (! $res->successful()) {
            throw new Exception('Local Docker container error ('.$res->status().'): '.$res->body());
        }

        $data = $res->json();
        $output = $data['output'] ?? null;

        if (is_array($output)) {
            $output = $output[0] ?? null;
        }

        if (empty($output)) {
            throw new Exception('Local Docker container returned empty output payload.');
        }

        // Output format can be base64 data URI, raw base64, or URL
        if (str_starts_with($output, 'data:image/') || preg_match('/^[a-zA-Z0-9+\/=\s]+$/', $output)) {
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $output);
            $binary = base64_decode($base64Data, true);

            if ($binary === false || strlen($binary) < 100) {
                throw new Exception('Failed to decode base64 image from local Docker container.');
            }

            File::put($outputAbsPath, $binary);

            return true;
        }

        if (filter_var($output, FILTER_VALIDATE_URL)) {
            $imageBytes = Http::timeout(60)->get($output)->body();
            if (strlen($imageBytes) < 100) {
                throw new Exception('Downloaded image from Docker container was corrupted or too small.');
            }

            File::put($outputAbsPath, $imageBytes);

            return true;
        }

        throw new Exception('Unrecognized output format from Docker container: '.substr($output, 0, 100));
    }

    /**
     * Test connection credentials, health, and latency.
     *
     * @return array{status: 'success'|'failed', latency_ms: int, message: string}
     */
    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $baseUrl = rtrim($connection->base_url ?: 'http://localhost:5000', '/');

        // Remove /predictions if user specified it in base_url
        if (str_ends_with($baseUrl, '/predictions')) {
            $baseUrl = substr($baseUrl, 0, -12);
        }

        try {
            // First check OpenAPI schema or root endpoint
            $res = Http::timeout(10)->get("{$baseUrl}/openapi.json");

            if (! $res->successful()) {
                $res = Http::timeout(10)->get($baseUrl);
            }

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if (! $res->successful()) {
                throw new Exception('Endpoint responded with HTTP '.$res->status().': '.$res->body());
            }

            return [
                'status' => 'success',
                'latency_ms' => $latency,
                'message' => "Local Docker Cog container online at {$baseUrl}.",
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failed',
                'latency_ms' => $latency,
                'message' => 'Unable to reach local Docker container: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Perform a lightweight warmup inference to load PyTorch / CUDA weights into GPU memory.
     *
     * @return array{status: 'success'|'failed', latency_ms: int, message: string}
     */
    public function warmupInference(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $predictionUrl = $this->getPredictionsUrl($connection);

        try {
            $res = Http::timeout(60)->post($predictionUrl, [
                'input' => [
                    'prompt' => 'ping warmup',
                    'width' => 256,
                    'height' => 256,
                    'num_inference_steps' => 1,
                    'num_outputs' => 1,
                    'guidance_scale' => 1.0,
                ],
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if (! $res->successful()) {
                throw new Exception('Warmup failed with status '.$res->status().': '.$res->body());
            }

            return [
                'status' => 'success',
                'latency_ms' => $latency,
                'message' => "Docker GPU warm-up complete! Weights loaded in {$latency}ms.",
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            return [
                'status' => 'failed',
                'latency_ms' => $latency,
                'message' => 'Warmup error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Whether this provider supports reference image uploads for visual consistency.
     */
    public function supportsReferenceImages(): bool
    {
        return true;
    }

    /**
     * Calculate resolution dimensions safe for 6GB VRAM (multiples of 8).
     *
     * @param  array<string, mixed>  $options
     * @return array{0: int, 1: int}
     */
    public function calculateDimensions(string $aspectRatio, array $options = []): array
    {
        $baseWidth = (int) ($options['width'] ?? 512);
        $baseHeight = (int) ($options['height'] ?? 512);

        return match ($aspectRatio) {
            '16:9' => [640, 360],
            '9:16' => [360, 640],
            '4:3' => [576, 432],
            '3:4' => [432, 576],
            '2:3' => [416, 624],
            '3:2' => [624, 416],
            '1:1' => [$baseWidth, $baseHeight],
            default => [$baseWidth, $baseHeight],
        };
    }

    /**
     * Resolve the predictions POST endpoint URL.
     */
    public function getPredictionsUrl(AiConnection $connection): string
    {
        $url = trim($connection->base_url ?: 'http://localhost:5000');
        $url = rtrim($url, '/');

        if (! str_ends_with($url, '/predictions')) {
            $url .= '/predictions';
        }

        return $url;
    }
}
