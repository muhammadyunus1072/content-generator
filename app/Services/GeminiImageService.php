<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiImageService
{
    public function generateImage(string $prompt)
    {
        $encodedPrompt = urlencode($prompt);

        $queryParams = http_build_query([
            'model' => 'flux',
            'nologo' => 'true',
        ]);

        $endpoint = "https://gen.pollinations.ai/image/{$encodedPrompt}?{$queryParams}";

        $response = Http::timeout(60)->get($endpoint);

        if ($response->successful()) {
            return $response->body();
        }

        $statusCode = $response->status();
        $errorBody = $response->body();

        Log::error('Pollinations API Image Generation Failed', [
            'status' => $statusCode,
            'endpoint' => $endpoint,
            'prompt' => $prompt,
            'response' => $errorBody,
        ]);

        // Ambil detail error
        $errorDetail = $response->json('error') ?? $response->json('message');

        // --- PERBAIKAN DI SINI ---
        // Jika API membalas dengan Array/Object, ubah ke String format JSON
        if (is_array($errorDetail)) {
            $errorDetail = json_encode($errorDetail);
        }
        // Jika kosong atau bukan JSON sama sekali, ambil teks mentahnya
        elseif (! $errorDetail) {
            $errorDetail = ! empty($errorBody)
                ? substr(strip_tags($errorBody), 0, 150).'...'
                : 'No response body provided by the API.';
        }

        throw new Exception("Pollinations API Error [{$statusCode}]: {$errorDetail}");
    }
}
