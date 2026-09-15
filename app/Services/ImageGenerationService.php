<?php

namespace App\Services;

use App\Models\AiConnection;
use App\Services\Ai\AiModelManagerService;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageGenerationService
{
    public function __construct(
        protected ?AiModelManagerService $aiManager = null
    ) {
        $this->aiManager = $aiManager ?? app(AiModelManagerService::class);
    }

    /**
     * Generate an image for a given prompt and save it to the specified storage path.
     *
     * @return string Absolute file path to the saved image
     */
    public function generateAndSave(
        string $prompt,
        string $outputAbsolutePath,
        string|int|null $providerOrConnectionId = 'cloudflare',
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): string {
        File::ensureDirectoryExists(dirname($outputAbsolutePath));

        // If numeric ID, resolve specific connection
        if (is_numeric($providerOrConnectionId)) {
            $connection = AiConnection::find($providerOrConnectionId);
            if ($connection) {
                $this->aiManager->generateImage($prompt, $outputAbsolutePath, $connection, $aspectRatio, $referenceImagePaths);

                return $outputAbsolutePath;
            }
        }

        // Check if a specific connection is configured in database matching provider name
        if (is_string($providerOrConnectionId)) {
            $connection = AiConnection::where('category', 'image')
                ->where('provider', $providerOrConnectionId)
                ->where('is_active', true)
                ->first();

            if ($connection) {
                try {
                    $this->aiManager->generateImage($prompt, $outputAbsolutePath, $connection, $aspectRatio, $referenceImagePaths);

                    return $outputAbsolutePath;
                } catch (Exception $e) {
                    Log::warning("Provider {$providerOrConnectionId} failed, falling back to manager default: ".$e->getMessage());
                }
            }
        }

        // Delegate to AiModelManagerService default
        try {
            $this->aiManager->generateImage($prompt, $outputAbsolutePath, null, $aspectRatio, $referenceImagePaths);

            return $outputAbsolutePath;
        } catch (Exception $e) {
            Log::warning('AiModelManager default image generation failed, trying direct pollinations: '.$e->getMessage());
        }

        // Direct Pollinations fallback
        $imageBinary = $this->generateViaPollinations($prompt, $aspectRatio);
        File::put($outputAbsolutePath, $imageBinary);

        return $outputAbsolutePath;
    }

    /**
     * Generate image via Pollinations Flux API.
     */
    protected function generateViaPollinations(string $prompt, string $aspectRatio = '16:9'): string
    {
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
        $endpoint = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width={$width}&height={$height}&nologo=true&model=flux";

        $response = Http::timeout(90)->get($endpoint);

        if ($response->failed() || strlen($response->body()) < 500) {
            throw new Exception('Pollinations API error or empty image returned.');
        }

        return $response->body();
    }
}
