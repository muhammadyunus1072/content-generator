<?php

namespace App\Services\Ai;

use App\Models\AiConnection;
use App\Services\Ai\Contracts\ImageProviderInterface;
use App\Services\Ai\Providers\CloudflareProvider;
use App\Services\Ai\Providers\DockerReplicateImageProvider;
use App\Services\Ai\Providers\FalAiProvider;
use App\Services\Ai\Providers\GeminiImageProvider;
use App\Services\Ai\Providers\HuggingFaceProvider;
use App\Services\Ai\Providers\PollinationsProvider;
use App\Services\Ai\Providers\ReplicateProvider;
use App\Services\Ai\Providers\StabilityAiProvider;
use App\Services\Ai\Providers\TogetherAiProvider;
use Exception;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class AiModelManagerService
{
    /**
     * Get active connections for a category, ensuring defaults are provisioned.
     *
     * @return Collection<int, AiConnection>
     */
    public function getAvailableModels(string $category, ?int $userId = null): Collection
    {
        $this->ensureDefaultConnectionsExist($userId);

        return AiConnection::query()
            ->active()
            ->category($category)
            ->when($userId, fn ($q) => $q->where(fn ($sub) => $sub->where('user_id', $userId)->orWhereNull('user_id')))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the default connection for a category.
     */
    public function getDefaultModel(string $category, ?int $userId = null): ?AiConnection
    {
        $this->ensureDefaultConnectionsExist($userId);

        $default = AiConnection::query()
            ->active()
            ->category($category)
            ->where('is_default', true)
            ->when($userId, fn ($q) => $q->where(fn ($sub) => $sub->where('user_id', $userId)->orWhereNull('user_id')))
            ->first();

        if ($default) {
            return $default;
        }

        return AiConnection::query()
            ->active()
            ->category($category)
            ->when($userId, fn ($q) => $q->where(fn ($sub) => $sub->where('user_id', $userId)->orWhereNull('user_id')))
            ->first();
    }

    /**
     * Resolve a connection instance by ID or return default.
     */
    public function resolveConnection(string $category, ?int $connectionId = null, ?int $userId = null): ?AiConnection
    {
        if ($connectionId) {
            $connection = AiConnection::find($connectionId);
            if ($connection && $connection->is_active) {
                return $connection;
            }
        }

        return $this->getDefaultModel($category, $userId);
    }

    /**
     * Set a connection as the default for its category.
     */
    public function setDefaultConnection(AiConnection $connection): void
    {
        AiConnection::where('category', $connection->category)
            ->where('id', '!=', $connection->id)
            ->update(['is_default' => false]);

        $connection->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * Test live connectivity for any configured AI model.
     *
     * @return array{status: string, latency_ms: int, message: string}
     */
    public function testConnection(AiConnection $connection): array
    {
        $startTime = microtime(true);

        try {
            if ($connection->category === 'image') {
                $result = $this->getImageProvider($connection->provider)->testConnection($connection);

                $connection->update([
                    'last_tested_at' => now(),
                    'last_test_status' => $result['status'],
                    'last_test_message' => $result['message'].' ('.$result['latency_ms'].'ms)',
                ]);

                return $result;
            }

            switch ($connection->provider) {
                case 'gemini':
                    $modelName = $connection->model_id ?: env('GEMINI_MODEL', 'gemini-2.0-flash');
                    $response = Gemini::generativeModel(model: $modelName)->generateContent('Respond with "OK".');
                    $message = 'Gemini online. Sample: '.substr(trim($response->text()), 0, 50);
                    break;

                case 'together_ai':
                    if ($connection->category === 'image') {
                        $res = Http::withToken($connection->api_key)
                            ->timeout(20)
                            ->post('https://api.together.xyz/v1/images/generations', [
                                'model' => $connection->model_id ?: 'black-forest-labs/FLUX.1-schnell',
                                'prompt' => 'A small blue circle',
                                'width' => 512,
                                'height' => 512,
                                'steps' => 1,
                            ]);
                        if (! $res->successful()) {
                            throw new Exception($res->json('error.message') ?? $res->body());
                        }
                        $message = 'Together AI Flux active.';
                    } else {
                        $res = Http::withToken($connection->api_key)
                            ->timeout(15)
                            ->post('https://api.together.xyz/v1/chat/completions', [
                                'model' => $connection->model_id ?: 'meta-llama/Llama-3.3-70B-Instruct-Turbo',
                                'messages' => [['role' => 'user', 'content' => 'ping']],
                                'max_tokens' => 5,
                            ]);
                        if (! $res->successful()) {
                            throw new Exception($res->json('error.message') ?? $res->body());
                        }
                        $message = 'Together AI LLM active.';
                    }
                    break;

                case 'openai_compatible':
                case 'groq':
                    $baseUrl = rtrim($connection->base_url ?: ($connection->provider === 'groq' ? 'https://api.groq.com/openai/v1' : 'https://api.openai.com/v1'), '/');
                    $res = Http::withToken($connection->api_key)
                        ->timeout(15)
                        ->post($baseUrl.'/chat/completions', [
                            'model' => $connection->model_id ?: 'gpt-4o-mini',
                            'messages' => [['role' => 'user', 'content' => 'ping']],
                            'max_tokens' => 5,
                        ]);
                    if (! $res->successful()) {
                        throw new Exception($res->json('error.message') ?? $res->body());
                    }
                    $message = 'API endpoint online.';
                    break;

                case 'cloudflare':
                    $url = $connection->base_url ?: env('IMAGE_WORKER_URL');
                    if (empty($url)) {
                        throw new Exception('Cloudflare Worker URL is not configured.');
                    }
                    $apiKey = $connection->api_key ?: env('IMAGE_WORKER_KEY', 'default-key');
                    $res = Http::withToken($apiKey)
                        ->timeout(15)
                        ->post($url, ['prompt' => 'ping test']);
                    if (! $res->successful()) {
                        throw new Exception('Worker returned HTTP '.$res->status().': '.$res->body());
                    }
                    $message = 'Cloudflare Worker AI online.';
                    break;

                case 'pollinations':
                    $res = Http::timeout(10)->get('https://image.pollinations.ai/prompt/ping%20test?width=64&height=64&nologo=true');
                    if (! $res->successful()) {
                        throw new Exception('Pollinations returned HTTP '.$res->status());
                    }
                    $message = 'Pollinations public image gateway online.';
                    break;

                case 'elevenlabs':
                    $res = Http::withHeaders(['xi-api-key' => $connection->api_key])
                        ->timeout(15)
                        ->get('https://api.elevenlabs.io/v1/user');
                    if (! $res->successful()) {
                        throw new Exception($res->json('detail.message') ?? $res->body());
                    }
                    $message = 'ElevenLabs authenticated. Subscription active.';
                    break;

                case 'edge_tts':
                    $testProcess = Process::run(['python', '-m', 'edge_tts', '--list-voices']);
                    if (! $testProcess->successful()) {
                        throw new Exception('Edge-TTS python package error: '.$testProcess->errorOutput());
                    }
                    $message = 'Edge-TTS engine verified locally.';
                    break;

                case 'docker_cog':
                case 'docker_replicate':
                    $baseUrl = rtrim($connection->base_url ?: 'http://localhost:5000', '/');
                    if (str_ends_with($baseUrl, '/predictions')) {
                        $baseUrl = substr($baseUrl, 0, -12);
                    }
                    $res = Http::timeout(10)->get("{$baseUrl}/openapi.json");
                    if (! $res->successful()) {
                        $res = Http::timeout(10)->get($baseUrl);
                    }
                    if (! $res->successful()) {
                        throw new Exception('Local Docker container returned HTTP '.$res->status());
                    }
                    $message = "Local Docker container online at {$baseUrl}.";
                    break;

                default:
                    $message = 'Connection parameters saved.';
                    break;
            }

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $connection->update([
                'last_tested_at' => now(),
                'last_test_status' => 'success',
                'last_test_message' => $message.' ('.$latency.'ms)',
            ]);

            return [
                'status' => 'success',
                'latency_ms' => $latency,
                'message' => $message,
            ];
        } catch (Exception $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);

            $connection->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'latency_ms' => $latency,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate text using the chosen or default LLM connection.
     */
    public function generateText(string $prompt, ?AiConnection $connection = null, array $options = []): string
    {
        $conn = $connection ?: $this->getDefaultModel('text');

        if (! $conn || $conn->provider === 'gemini') {
            $model = $conn?->model_id ?: env('GEMINI_MODEL', 'gemini-2.0-flash');
            $response = Gemini::generativeModel(model: $model)->generateContent($prompt);

            return trim($response->text());
        }

        if (in_array($conn->provider, ['docker_cog', 'docker_replicate'])) {
            $predictionUrl = trim($conn->base_url ?: 'http://localhost:5000');
            $predictionUrl = rtrim($predictionUrl, '/');
            if (! str_ends_with($predictionUrl, '/predictions')) {
                $predictionUrl .= '/predictions';
            }

            $res = Http::timeout(120)->post($predictionUrl, [
                'input' => [
                    'prompt' => $prompt,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 2048,
                ],
            ]);

            if ($res->successful()) {
                $data = $res->json();
                $output = $data['output'] ?? '';
                if (is_array($output)) {
                    return trim(implode('', $output));
                }

                return trim((string) $output);
            }

            throw new Exception('Docker Cog LLM inference error: '.$res->body());
        }

        if (in_array($conn->provider, ['openai_compatible', 'groq', 'together_ai'])) {
            $baseUrl = rtrim($conn->base_url ?: ($conn->provider === 'groq' ? 'https://api.groq.com/openai/v1' : ($conn->provider === 'together_ai' ? 'https://api.together.xyz/v1' : 'https://api.openai.com/v1')), '/');

            $res = Http::withToken($conn->api_key)
                ->timeout(60)
                ->post($baseUrl.'/chat/completions', [
                    'model' => $conn->model_id,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 4096,
                ]);

            if ($res->successful()) {
                return trim($res->json('choices.0.message.content') ?? '');
            }

            throw new Exception('LLM API call failed: '.$res->body());
        }

        // Fallback to Gemini
        $response = Gemini::generativeModel(model: env('GEMINI_MODEL', 'gemini-2.0-flash'))->generateContent($prompt);

        return trim($response->text());
    }

    /**
     * Resolve the appropriate strategy instance for an image provider.
     */
    public function getImageProvider(string $providerKey): ImageProviderInterface
    {
        return match ($providerKey) {
            'docker_cog', 'docker_replicate' => app(DockerReplicateImageProvider::class),
            'fal_ai' => app(FalAiProvider::class),
            'replicate' => app(ReplicateProvider::class),
            'stability_ai' => app(StabilityAiProvider::class),
            'together_ai' => app(TogetherAiProvider::class),
            'gemini_image' => app(GeminiImageProvider::class),
            'huggingface' => app(HuggingFaceProvider::class),
            'cloudflare' => app(CloudflareProvider::class),
            'pollinations' => app(PollinationsProvider::class),
            default => app(CloudflareProvider::class),
        };
    }

    /**
     * Check if a given connection (or default image connection) supports reference images.
     */
    public function connectionSupportsReferenceImages(?AiConnection $connection = null): bool
    {
        $conn = $connection ?: $this->getDefaultModel('image');
        $provider = $conn ? $conn->provider : 'cloudflare';

        return $this->getImageProvider($provider)->supportsReferenceImages();
    }

    /**
     * Generate and save image using the chosen or default Image connection.
     *
     * @param  array<int, string>  $referenceImagePaths  Optional list of absolute file paths to reference sheets
     */
    public function generateImage(
        string $prompt,
        string $outputAbsPath,
        ?AiConnection $connection = null,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool {
        $conn = $connection ?: $this->getDefaultModel('image');
        $provider = $conn ? $conn->provider : 'cloudflare';

        File::ensureDirectoryExists(dirname($outputAbsPath));

        try {
            $strategy = $this->getImageProvider($provider);
            $effectiveConn = $conn ?: new AiConnection([
                'provider' => 'cloudflare',
                'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
                'base_url' => env('IMAGE_WORKER_URL'),
                'api_key' => env('IMAGE_WORKER_KEY', 'default-key'),
            ]);

            return $strategy->generate($prompt, $outputAbsPath, $effectiveConn, $aspectRatio, $referenceImagePaths);
        } catch (Exception $e) {
            Log::warning("Provider {$provider} image generation failed: ".$e->getMessage().', trying Pollinations fallback.');
        }

        // Fallback to Pollinations
        if ($provider !== 'pollinations') {
            try {
                $fallback = $this->getImageProvider('pollinations');
                $dummyConn = new AiConnection([
                    'provider' => 'pollinations',
                    'model_id' => 'flux',
                    'base_url' => 'https://image.pollinations.ai',
                ]);

                return $fallback->generate($prompt, $outputAbsPath, $dummyConn, $aspectRatio, $referenceImagePaths);
            } catch (Exception $e) {
                Log::error('Pollinations fallback failed: '.$e->getMessage());
            }
        }

        throw new Exception('All image generation providers failed.');
    }

    /**
     * Get list of curated preset templates for quick connection setup.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getPresetTemplates(): array
    {
        return [
            'text' => [
                [
                    'name' => 'Google Gemini 2.0 Flash (Default)',
                    'provider' => 'gemini',
                    'model_id' => 'gemini-2.0-flash',
                    'base_url' => '',
                    'is_default' => true,
                    'description' => 'Fast, intelligent multimodal reasoning by Google DeepMind.',
                ],
                [
                    'name' => 'Groq - Llama 3.3 70B Versatile',
                    'provider' => 'groq',
                    'model_id' => 'llama-3.3-70b-versatile',
                    'base_url' => 'https://api.groq.com/openai/v1',
                    'is_default' => false,
                    'description' => 'Ultra-fast inference (500+ tokens/sec) on Groq LPU chips.',
                ],
                [
                    'name' => 'Together AI - DeepSeek V3',
                    'provider' => 'together_ai',
                    'model_id' => 'deepseek-ai/DeepSeek-V3',
                    'base_url' => 'https://api.together.xyz/v1',
                    'is_default' => false,
                    'description' => 'Advanced 671B MoE model with exceptional reasoning at low cost.',
                ],
                [
                    'name' => 'OpenAI GPT-4o Mini',
                    'provider' => 'openai_compatible',
                    'model_id' => 'gpt-4o-mini',
                    'base_url' => 'https://api.openai.com/v1',
                    'is_default' => false,
                    'description' => 'Cost-efficient, fast flagship model by OpenAI.',
                ],
                [
                    'name' => 'Ollama Local LLM',
                    'provider' => 'openai_compatible',
                    'model_id' => 'llama3.2',
                    'base_url' => 'http://localhost:11434/v1',
                    'is_default' => false,
                    'description' => '100% offline local LLM running via Ollama.',
                ],
                [
                    'name' => 'Local Docker Cog LLM (Offline)',
                    'provider' => 'docker_cog',
                    'model_id' => 'local-docker-llm',
                    'base_url' => 'http://localhost:5000/predictions',
                    'is_default' => false,
                    'description' => 'Local LLM container running via Cog / Docker on port 5000.',
                ],
            ],
            'image' => [
                [
                    'name' => 'Cloudflare SDXL Lightning (Default Free)',
                    'provider' => 'cloudflare',
                    'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
                    'base_url' => env('IMAGE_WORKER_URL', ''),
                    'is_default' => true,
                    'description' => 'Fast 4-step SDXL Lightning on Cloudflare edge (10k Neurons free daily).',
                    'api_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'prompt' => ['type' => 'string'],
                            'width' => ['type' => 'integer', 'default' => 1024],
                            'height' => ['type' => 'integer', 'default' => 1024],
                            'num_steps' => ['type' => 'integer', 'default' => 20],
                            'guidance' => ['type' => 'number', 'default' => 7.5],
                        ],
                        'required' => ['prompt'],
                    ],
                ],
                [
                    'name' => 'Cloudflare - FLUX-2 Klein 4B (Text & Image-to-Image)',
                    'provider' => 'cloudflare',
                    'model_id' => '@cf/black-forest-labs/flux-2-klein-4b',
                    'base_url' => env('IMAGE_WORKER_URL', ''),
                    'is_default' => false,
                    'description' => 'Fast FLUX-2 Klein 4B model running on Cloudflare Workers AI with text-to-image and multipart reference image support.',
                    'api_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'multipart' => [
                                'type' => 'object',
                                'properties' => [
                                    'body' => ['type' => 'object'],
                                    'contentType' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'required' => ['multipart'],
                    ],
                ],
                [
                    'name' => 'Cloudflare - FLUX-2 Klein 9B (Ultra-Fast 9B)',
                    'provider' => 'cloudflare',
                    'model_id' => '@cf/black-forest-labs/flux-2-klein-9b',
                    'base_url' => env('IMAGE_WORKER_URL', ''),
                    'is_default' => false,
                    'description' => 'Flagship 9B FLUX-2 Klein model on Cloudflare Workers AI with sub-second inference and superior prompt realism.',
                    'api_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'multipart' => [
                                'type' => 'object',
                                'properties' => [
                                    'body' => ['type' => 'object'],
                                    'contentType' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'required' => ['multipart'],
                    ],
                ],
                [
                    'name' => 'Cloudflare - Leonardo Phoenix 1.0',
                    'provider' => 'cloudflare',
                    'model_id' => '@cf/leonardo/phoenix-1.0',
                    'base_url' => env('IMAGE_WORKER_URL', ''),
                    'is_default' => false,
                    'description' => 'High quality creative generation with prompt, guidance, seed, and negative prompt schema.',
                    'api_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'prompt' => ['type' => 'string', 'minLength' => 1, 'description' => 'A text description of the image you want to generate.'],
                            'guidance' => ['type' => 'number', 'default' => 2, 'minimum' => 2, 'maximum' => 10, 'description' => 'Controls how closely the generated image should adhere to the prompt.'],
                            'seed' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Random seed for reproducibility.'],
                            'height' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2048, 'default' => 1024, 'description' => 'The height of the generated image in pixels.'],
                            'width' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2048, 'default' => 1024, 'description' => 'The width of the generated image in pixels.'],
                            'num_steps' => ['type' => 'integer', 'default' => 25, 'minimum' => 1, 'maximum' => 50, 'description' => 'The number of diffusion steps.'],
                            'negative_prompt' => ['type' => 'string', 'minLength' => 1, 'description' => 'Specify what to exclude from the generated images.'],
                        ],
                        'required' => ['prompt'],
                    ],
                ],
                [
                    'name' => 'Fal.ai - FLUX 1.1 Pro ($0.04/img) ⭐ Top Pick',
                    'provider' => 'fal_ai',
                    'model_id' => 'fal-ai/flux-pro/v1.1',
                    'base_url' => 'https://fal.run',
                    'is_default' => false,
                    'description' => 'Flagship production FLUX 1.1 Pro. Superior detail & reference image support. $10 free credit.',
                ],
                [
                    'name' => 'Fal.ai - FLUX Dev ($0.025/img)',
                    'provider' => 'fal_ai',
                    'model_id' => 'fal-ai/flux/dev',
                    'base_url' => 'https://fal.run',
                    'is_default' => false,
                    'description' => 'Balanced FLUX Dev model with reference image support. $10 free credit.',
                ],
                [
                    'name' => 'Together AI - FLUX.1 Schnell ($0.003/img)',
                    'provider' => 'together_ai',
                    'model_id' => 'black-forest-labs/FLUX.1-schnell',
                    'base_url' => 'https://api.together.xyz/v1',
                    'is_default' => false,
                    'description' => 'State-of-the-art FLUX.1 model with top-tier anatomy, lighting, and manhwa detail. $1 free credit.',
                ],
                [
                    'name' => 'Together AI - FLUX.1 Dev ($0.025/img)',
                    'provider' => 'together_ai',
                    'model_id' => 'black-forest-labs/FLUX.1-dev',
                    'base_url' => 'https://api.together.xyz/v1',
                    'is_default' => false,
                    'description' => 'High quality FLUX.1 Dev via OpenAI-compatible endpoint. $1 free credit.',
                ],
                [
                    'name' => 'Replicate - FLUX 1.1 Pro ($0.04/img)',
                    'provider' => 'replicate',
                    'model_id' => 'black-forest-labs/flux-1.1-pro',
                    'base_url' => 'https://api.replicate.com/v1',
                    'is_default' => false,
                    'description' => 'Official FLUX 1.1 Pro on Replicate with image input support and webhook callback.',
                ],
                [
                    'name' => 'Replicate - FLUX Dev ($0.025/img)',
                    'provider' => 'replicate',
                    'model_id' => 'black-forest-labs/flux-dev',
                    'base_url' => 'https://api.replicate.com/v1',
                    'is_default' => false,
                    'description' => 'High quality FLUX Dev model on Replicate with webhook support.',
                ],
                [
                    'name' => 'Replicate - FLUX Schnell ($0.003/img)',
                    'provider' => 'replicate',
                    'model_id' => 'black-forest-labs/flux-schnell',
                    'base_url' => 'https://api.replicate.com/v1',
                    'is_default' => false,
                    'description' => 'Ultra-fast 4-step generation ($3 per 1,000 images).',
                ],
                [
                    'name' => 'Stability AI - SD 3.5 Large ($0.065/img)',
                    'provider' => 'stability_ai',
                    'model_id' => 'sd3.5-large',
                    'base_url' => 'https://api.stability.ai/v2beta',
                    'is_default' => false,
                    'description' => 'Stable Diffusion 3.5 Large with image-to-image support. 6.5 credits per image.',
                ],
                [
                    'name' => 'Stability AI - SD 3.5 Flash ($0.025/img)',
                    'provider' => 'stability_ai',
                    'model_id' => 'sd3.5-flash',
                    'base_url' => 'https://api.stability.ai/v2beta',
                    'is_default' => false,
                    'description' => 'Fast SD 3.5 Flash with image-to-image support. 2.5 credits per image.',
                ],
                [
                    'name' => 'Google AI Studio - Gemini Flash Image',
                    'provider' => 'gemini_image',
                    'model_id' => 'gemini-3.1-flash-image',
                    'base_url' => 'https://generativelanguage.googleapis.com',
                    'is_default' => false,
                    'description' => 'Multimodal image generation via generate_content. Free in Studio, paid in API.',
                ],
                [
                    'name' => 'HuggingFace - FLUX.1 Schnell (FREE)',
                    'provider' => 'huggingface',
                    'model_id' => 'black-forest-labs/FLUX.1-schnell',
                    'base_url' => 'https://api-inference.huggingface.co/models',
                    'is_default' => false,
                    'description' => 'Free serverless inference API via HuggingFace. Rate-limited. Text-only.',
                ],
                [
                    'name' => 'Pollinations.ai Flux (Free Public)',
                    'provider' => 'pollinations',
                    'model_id' => 'flux',
                    'base_url' => 'https://image.pollinations.ai',
                    'is_default' => false,
                    'description' => 'Free community image generation endpoint powered by Flux.',
                ],
                [
                    'name' => 'Local Docker (Replicate / Cog) - RTX 3050 Safe',
                    'provider' => 'docker_cog',
                    'model_id' => 'replicate-docker-sd',
                    'base_url' => 'http://localhost:5000/predictions',
                    'is_default' => false,
                    'description' => 'Local GPU text-to-image container via Cog/Replicate Docker on RTX 3050 (512x512 safe).',
                    'options' => [
                        'width' => 512,
                        'height' => 512,
                        'num_steps' => 20,
                        'guidance' => 7.0,
                    ],
                ],
            ],
            'audio_tts' => [
                [
                    'name' => 'Edge-TTS Multi-Language Neural (Default Free)',
                    'provider' => 'edge_tts',
                    'model_id' => 'id-ID-ArdiNeural',
                    'base_url' => '',
                    'is_default' => true,
                    'description' => '100% free high-quality neural narration in Indonesian, English, and Japanese.',
                ],
                [
                    'name' => 'ElevenLabs Multilingual v2',
                    'provider' => 'elevenlabs',
                    'model_id' => 'eleven_multilingual_v2',
                    'base_url' => 'https://api.elevenlabs.io/v1',
                    'is_default' => false,
                    'description' => 'Industry standard emotional voice acting and custom cloned voices.',
                ],
                [
                    'name' => 'Local Docker Cog TTS',
                    'provider' => 'docker_cog',
                    'model_id' => 'local-docker-tts',
                    'base_url' => 'http://localhost:5000/predictions',
                    'is_default' => false,
                    'description' => 'Local voice synthesis container running via Cog / Docker.',
                ],
            ],
            'audio_stt' => [
                [
                    'name' => 'Faster-Whisper Local (Default)',
                    'provider' => 'whisper_local',
                    'model_id' => 'large-v3',
                    'base_url' => '',
                    'is_default' => true,
                    'description' => 'Local GPU/CPU Whisper model for word-level subtitle alignment.',
                ],
                [
                    'name' => 'Groq Whisper Large v3 (Cloud Ultra-Fast)',
                    'provider' => 'groq',
                    'model_id' => 'whisper-large-v3',
                    'base_url' => 'https://api.groq.com/openai/v1',
                    'is_default' => false,
                    'description' => 'Transcribes audio in under 1 second using Groq LPU cloud inference.',
                ],
            ],
        ];
    }

    /**
     * Ensure base default connections exist in DB.
     */
    protected function ensureDefaultConnectionsExist(?int $userId = null): void
    {
        if (AiConnection::count() > 0) {
            return;
        }

        // Seed default starter connections
        AiConnection::create([
            'user_id' => $userId,
            'name' => 'Google Gemini 2.0 Flash',
            'category' => 'text',
            'provider' => 'gemini',
            'model_id' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'api_key' => env('GEMINI_API_KEY'),
            'is_default' => true,
            'is_active' => true,
        ]);

        AiConnection::create([
            'user_id' => $userId,
            'name' => 'Cloudflare SDXL Lightning',
            'category' => 'image',
            'provider' => 'cloudflare',
            'model_id' => '@cf/bytedance/stable-diffusion-xl-lightning',
            'base_url' => env('IMAGE_WORKER_URL'),
            'api_key' => env('IMAGE_WORKER_KEY', 'default-key'),
            'is_default' => true,
            'is_active' => true,
        ]);

        AiConnection::create([
            'user_id' => $userId,
            'name' => 'Pollinations.ai Flux (Free)',
            'category' => 'image',
            'provider' => 'pollinations',
            'model_id' => 'flux',
            'base_url' => 'https://image.pollinations.ai',
            'is_default' => false,
            'is_active' => true,
        ]);

        AiConnection::create([
            'user_id' => $userId,
            'name' => 'Edge-TTS Multi-Language Neural',
            'category' => 'audio_tts',
            'provider' => 'edge_tts',
            'model_id' => 'id-ID-ArdiNeural',
            'is_default' => true,
            'is_active' => true,
        ]);

        AiConnection::create([
            'user_id' => $userId,
            'name' => 'Faster-Whisper Local',
            'category' => 'audio_stt',
            'provider' => 'whisper_local',
            'model_id' => 'large-v3',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Get the default JSON Schema definition for a given model identifier.
     */
    public function getDefaultSchemaForModel(string $modelId): array
    {
        $normalized = strtolower($modelId);

        if (str_contains($normalized, 'flux-2-klein') || str_contains($normalized, 'klein-9b') || str_contains($normalized, 'klein-4b')) {
            return [
                'type' => 'object',
                'properties' => [
                    'multipart' => [
                        'type' => 'object',
                        'properties' => [
                            'body' => ['type' => 'object'],
                            'contentType' => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['multipart'],
            ];
        }

        if (str_contains($normalized, 'phoenix')) {
            return [
                'type' => 'object',
                'properties' => [
                    'prompt' => ['type' => 'string', 'minLength' => 1, 'description' => 'A text description of the image you want to generate.'],
                    'guidance' => ['type' => 'number', 'default' => 2, 'minimum' => 2, 'maximum' => 10, 'description' => 'Controls how closely the generated image should adhere to the prompt.'],
                    'seed' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Random seed for reproducibility.'],
                    'height' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2048, 'default' => 1024, 'description' => 'The height of the generated image in pixels.'],
                    'width' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2048, 'default' => 1024, 'description' => 'The width of the generated image in pixels.'],
                    'num_steps' => ['type' => 'integer', 'default' => 25, 'minimum' => 1, 'maximum' => 50, 'description' => 'The number of diffusion steps.'],
                    'negative_prompt' => ['type' => 'string', 'minLength' => 1, 'description' => 'Specify what to exclude from the generated images.'],
                ],
                'required' => ['prompt'],
            ];
        }

        return [
            'type' => 'object',
            'properties' => [
                'prompt' => ['type' => 'string'],
                'width' => ['type' => 'integer', 'default' => 1024],
                'height' => ['type' => 'integer', 'default' => 1024],
                'num_steps' => ['type' => 'integer', 'default' => 20],
                'guidance' => ['type' => 'number', 'default' => 7.5],
            ],
            'required' => ['prompt'],
        ];
    }
}
