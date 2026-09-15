<?php

namespace App\Services\Ai\Contracts;

use App\Models\AiConnection;

interface ImageProviderInterface
{
    /**
     * Generate an image for the given prompt and save it to the specified output path.
     *
     * @param  string  $prompt  Visual text prompt for generation
     * @param  string  $outputAbsPath  Destination absolute file path for saved image
     * @param  AiConnection  $connection  The active AI Connection model
     * @param  string  $aspectRatio  Aspect ratio (e.g. '16:9', '9:16', '1:1')
     * @param  array<int, string>  $referenceImagePaths  Optional list of absolute file paths to reference sheets
     * @return bool True if image was successfully generated and saved
     */
    public function generate(
        string $prompt,
        string $outputAbsPath,
        AiConnection $connection,
        string $aspectRatio = '16:9',
        array $referenceImagePaths = []
    ): bool;

    /**
     * Test connection credentials, health, and latency.
     *
     * @return array{status: 'success'|'failed', latency_ms: int, message: string}
     */
    public function testConnection(AiConnection $connection): array;

    /**
     * Whether this provider supports reference image uploads for visual consistency.
     */
    public function supportsReferenceImages(): bool;
}
