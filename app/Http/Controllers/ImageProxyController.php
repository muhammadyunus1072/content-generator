<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ImageProxyController extends Controller
{
    /**
     * Proxy the request to the Cloudflare Worker.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
            'model' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240',
        ]);

        $workerUrl = env('IMAGE_WORKER_URL');
        $workerKey = env('IMAGE_WORKER_KEY');

        if (empty($workerUrl) || str_contains($workerUrl, 'your-worker-name')) {
            return response()->json(['error' => __('Worker URL is not configured. Please set a valid IMAGE_WORKER_URL in your .env file.')], 500);
        }

        try {
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer '.$workerKey,
            ])->timeout(90);

            $rawModel = $request->model ?: '@cf/bytedance/stable-diffusion-xl-lightning';
            $model = str_starts_with($rawModel, '@') ? $rawModel : (str_starts_with($rawModel, 'cf/') ? '@'.$rawModel : $rawModel);

            $params = [
                'prompt' => $request->prompt,
                'model' => $model,
            ];

            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $response = $httpClient->attach('image', File::get($imageFile->getRealPath()), $imageFile->getClientOriginalName())
                    ->post($workerUrl, $params);
            } else {
                $response = $httpClient->post($workerUrl, $params);
            }

            if ($response->failed()) {
                $errorMsg = $response->json('error') ?? $response->json('details') ?? $response->body() ?: 'Unknown Worker Error';

                return response()->json(['error' => $errorMsg], $response->status());
            }

            $contentType = $response->header('Content-Type') ?: 'image/png';
            $imageBytes = $response->body();

            if (empty($imageBytes)) {
                return response()->json(['error' => __('Worker API returned empty body.')], 500);
            }

            return response($imageBytes, 200)
                ->header('Content-Type', $contentType);
        } catch (Exception $e) {
            return response()->json(['error' => __('Proxy exception: ').$e->getMessage()], 500);
        }
    }
}
