<?php

namespace App\Services\Ai\Providers;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CloudflareProvider implements ImageProviderInterface
{
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        $url = $connection->base_url ?: env('IMAGE_WORKER_URL');
        $apiKey = $connection->api_key ?: env('IMAGE_WORKER_KEY', 'default-key');

        if (empty($url)) {
            throw new Exception('Cloudflare Worker URL is not configured.');
        }

        File::ensureDirectoryExists(dirname($outputAbsPath));

        $hasRefImage = ! empty($referenceImagePaths) && File::exists($referenceImagePaths[0]);
        $model = $this->normalizeModelId($connection->model_id ?: '@cf/bytedance/stable-diffusion-xl-lightning');
        $schemaType = $this->resolveSchemaType($model, $connection);
        $numSteps = (int) ($connection->options['num_steps'] ?? $connection->options['steps'] ?? 20);

        // Standard model dimensions matching SDXL/Flux aspect ratios
        $width = 1024;
        $height = 1024;

        if ($aspectRatio === '9:16') {
            $width = 768;
            $height = 1024;
        } elseif ($aspectRatio === '16:9') {
            $width = 1280;
            $height = 720;
        } elseif ($aspectRatio === '4:3') {
            $width = 1024;
            $height = 768;
        } elseif ($aspectRatio === '3:4') {
            $width = 768;
            $height = 1024;
        } elseif ($aspectRatio === '1:1') {
            $width = 1024;
            $height = 1024;
        }

        if (isset($connection->options['width'])) {
            $width = (int) $connection->options['width'];
        }
        if (isset($connection->options['height'])) {
            $height = (int) $connection->options['height'];
        }

        $urlWithQuery = $url.(str_contains($url, '?') ? '&' : '?').http_build_query([
            'prompt' => $prompt,
            'model' => $model,
        ]);

        if ($schemaType === 'multipart') {
            $multipartData = [
                ['name' => 'prompt', 'contents' => $prompt],
                ['name' => 'model', 'contents' => $model],
                ['name' => 'schema_type', 'contents' => 'multipart'],
                ['name' => 'width', 'contents' => (string) $width],
                ['name' => 'height', 'contents' => (string) $height],
                ['name' => 'num_steps', 'contents' => (string) $numSteps],
            ];

            if (! empty($connection->options['negative_prompt'])) {
                $multipartData[] = ['name' => 'negative_prompt', 'contents' => $connection->options['negative_prompt']];
            }

            if (isset($connection->options['guidance'])) {
                $multipartData[] = ['name' => 'guidance', 'contents' => (string) $connection->options['guidance']];
            }

            if (isset($connection->options['seed'])) {
                $multipartData[] = ['name' => 'seed', 'contents' => (string) $connection->options['seed']];
            }

            if ($hasRefImage) {
                $refPath = $referenceImagePaths[0];
                $multipartData[] = [
                    'name' => 'input_image_0',
                    'contents' => fopen($refPath, 'r'),
                    'filename' => basename($refPath),
                ];
                $multipartData[] = ['name' => 'strength', 'contents' => (string) ($connection->options['strength'] ?? 0.75)];
            }

            // dd([$urlWithQuery, $multipartData]);
            $res = Http::withToken($apiKey)
                ->timeout(90)
                ->withHeaders(['X-AI-Schema' => 'multipart'])
                ->asMultipart()
                ->post($urlWithQuery, $multipartData);
        } else {
            $params = [
                'prompt' => $prompt,
                'model' => $model,
                'schema_type' => 'standard_json',
                'width' => $width,
                'height' => $height,
                'num_steps' => $numSteps,
            ];

            if (! empty($connection->options['negative_prompt'])) {
                $params['negative_prompt'] = $connection->options['negative_prompt'];
            }

            if (isset($connection->options['seed'])) {
                $params['seed'] = (int) $connection->options['seed'];
            }

            if (isset($connection->options['guidance'])) {
                $params['guidance'] = (float) $connection->options['guidance'];
            }

            if ($hasRefImage) {
                $refPath = $referenceImagePaths[0];
                $params['image_b64'] = base64_encode(File::get($refPath));
                $params['strength'] = (float) ($connection->options['strength'] ?? 0.75);
                $params['guidance'] = (float) ($connection->options['guidance'] ?? 7.5);
            }

            $res = Http::withToken($apiKey)
                ->timeout(90)
                ->withHeaders(['X-AI-Schema' => 'standard_json'])
                ->post($urlWithQuery, $params);
        }

        if ($res->successful() && strlen($res->body()) > 100 && ! str_starts_with(trim($res->body()), '{"error"')) {
            File::put($outputAbsPath, $res->body());

            return true;
        }

        $body = $res->body();
        if (str_contains($body, '4006') || str_contains($body, 'daily free allocation')) {
            throw new Exception('Cloudflare quota exceeded (4006): You have used up your daily free allocation of 10,000 neurons. Please upgrade to Cloudflare Workers Paid plan or wait for your daily quota to reset.');
        }

        if (str_contains($body, "are 'multipart'") || str_contains($body, '5006')) {
            throw new Exception("Cloudflare Worker returned error (5006): Model '{$model}' requires multipart/form-data. Please deploy the updated hello-ai worker script to Cloudflare.");
        }

        if ($res->status() === 400 && str_contains($body, 'prompt is required')) {
            throw new Exception("Cloudflare Worker returned HTTP 400 ('prompt is required'): The worker script running in Cloudflare is missing the prompt parameter. Please copy the updated hello-ai/src/index.js code into your Cloudflare Worker Dashboard.");
        }

        throw new Exception('Cloudflare Worker returned error: '.$res->status().' '.$body);
    }

    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);
        $url = $connection->base_url ?: env('IMAGE_WORKER_URL');

        if (empty($url)) {
            throw new Exception('Cloudflare Worker URL is not configured.');
        }

        $model = $this->normalizeModelId($connection->model_id ?: '@cf/bytedance/stable-diffusion-xl-lightning');
        $schemaType = $this->resolveSchemaType($model, $connection);
        $apiKey = $connection->api_key ?: env('IMAGE_WORKER_KEY', 'default-key');

        $urlWithQuery = $url.(str_contains($url, '?') ? '&' : '?').http_build_query([
            'prompt' => 'ping test',
            'model' => $model,
            'ping' => 'true',
        ]);

        if ($schemaType === 'multipart') {
            $res = Http::withToken($apiKey)
                ->timeout(20)
                ->withHeaders([
                    'X-AI-Schema' => 'multipart',
                    'X-AI-Model' => $model,
                    'X-Test-Connection' => 'true',
                ])
                ->asMultipart()
                ->post($urlWithQuery, [
                    ['name' => 'prompt', 'contents' => 'ping test'],
                    ['name' => 'model', 'contents' => $model],
                    ['name' => 'schema_type', 'contents' => 'multipart'],
                ]);

            // If remote worker only parses JSON and rejected multipart with 400, retry as JSON
            if (! $res->successful() && $res->status() === 400) {
                $jsonRes = Http::withToken($apiKey)
                    ->timeout(20)
                    ->withHeaders([
                        'X-AI-Schema' => 'multipart',
                        'X-AI-Model' => $model,
                        'X-Test-Connection' => 'true',
                    ])
                    ->post($urlWithQuery, [
                        'prompt' => 'ping test',
                        'model' => $model,
                        'schema_type' => 'multipart',
                    ]);

                if ($jsonRes->successful() || str_contains($jsonRes->body(), '5006') || str_contains($jsonRes->body(), "are 'multipart'")) {
                    $res = $jsonRes;
                }
            }
        } else {
            $res = Http::withToken($apiKey)
                ->timeout(20)
                ->withHeaders([
                    'X-AI-Schema' => 'standard_json',
                    'X-AI-Model' => $model,
                    'X-Test-Connection' => 'true',
                ])
                ->post($urlWithQuery, [
                    'prompt' => 'ping test',
                    'model' => $model,
                    'schema_type' => 'standard_json',
                ]);
        }

        $latency = (int) round((microtime(true) - $startTime) * 1000);

        if (! $res->successful()) {
            $body = $res->body();
            if (str_contains($body, '4006') || str_contains($body, 'daily free allocation')) {
                throw new Exception('Cloudflare quota exceeded (4006): You have used up your daily free allocation of 10,000 neurons. Please upgrade to Cloudflare Workers Paid plan or wait for your daily quota to reset (resets at 00:00 UTC). Updating to the latest hello-ai worker script enables zero-neuron connection testing.');
            }

            if (str_contains($body, "are 'multipart'") || str_contains($body, '5006')) {
                throw new Exception("Cloudflare Worker error (5006): Model '{$model}' requires multipart/form-data. Please deploy the updated hello-ai worker script to Cloudflare.");
            }

            if ($res->status() === 400 && str_contains($body, 'prompt is required')) {
                throw new Exception("Cloudflare Worker returned HTTP 400 ('prompt is required'): The worker script running in Cloudflare is missing the prompt parameter. Please copy the updated hello-ai/src/index.js code into your Cloudflare Worker Dashboard.");
            }

            throw new Exception('Worker returned HTTP '.$res->status().': '.$body);
        }

        $data = $res->json();
        $message = is_array($data) && ! empty($data['message'])
            ? $data['message']
            : "Cloudflare Worker AI online ({$model} [{$schemaType}]).";

        return [
            'status' => 'success',
            'latency_ms' => $latency,
            'message' => $message,
        ];
    }

    /**
     * Dynamically resolves the schema type for a model, respecting connection options override.
     */
    public function resolveSchemaType(string $modelId, ?AiConnection $connection = null): string
    {
        if ($connection && ! empty($connection->options['schema_type'])) {
            return $connection->options['schema_type'];
        }

        if ($connection && ! empty($connection->options['api_schema'])) {
            $schema = $connection->options['api_schema'];
            if ((isset($schema['required']) && is_array($schema['required']) && in_array('multipart', $schema['required'])) || isset($schema['properties']['multipart'])) {
                return 'multipart';
            }

            return 'standard_json';
        }

        $normalized = strtolower($modelId);
        if (str_contains($normalized, 'flux-2-klein') || str_contains($normalized, 'klein-9b') || str_contains($normalized, 'klein-4b')) {
            return 'multipart';
        }

        return 'standard_json';
    }

    /**
     * Normalize Cloudflare model strings to ensure leading '@' prefix.
     */
    public function normalizeModelId(?string $modelId): string
    {
        $trimmed = trim($modelId ?? '');
        if (empty($trimmed)) {
            return '@cf/bytedance/stable-diffusion-xl-lightning';
        }

        if (! str_starts_with($trimmed, '@') && str_starts_with($trimmed, 'cf/')) {
            return '@'.$trimmed;
        }

        return $trimmed;
    }

    public function supportsReferenceImages(): bool
    {
        return true;
    }
}
