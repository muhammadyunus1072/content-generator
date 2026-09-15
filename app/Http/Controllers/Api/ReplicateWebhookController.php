<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SceneBoard;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReplicateWebhookController extends Controller
{
    /**
     * Handle incoming prediction completion webhook from Replicate.
     */
    public function handle(Request $request): JsonResponse
    {
        $predictionId = $request->input('id');
        $status = $request->input('status');
        $output = $request->input('output');
        $error = $request->input('error');

        Log::info('Replicate webhook notification received', [
            'prediction_id' => $predictionId,
            'status' => $status,
            'error' => $error,
        ]);

        if ($status !== 'succeeded') {
            Log::warning('Replicate prediction did not succeed', [
                'prediction_id' => $predictionId,
                'status' => $status,
                'error' => $error,
            ]);

            return response()->json(['message' => 'Status logged as not succeeded', 'status' => $status]);
        }

        $outputUrl = is_array($output) ? ($output[0] ?? null) : $output;
        if (! $outputUrl) {
            return response()->json(['message' => 'No output URL in payload'], 400);
        }

        // Check if there is cached context for this prediction
        $cacheKey = "replicate_prediction_{$predictionId}";
        $meta = Cache::get($cacheKey);

        if ($meta && ! empty($meta['output_path'])) {
            try {
                $imageBinary = Http::timeout(60)->get($outputUrl)->body();
                if (strlen($imageBinary) > 1000) {
                    File::ensureDirectoryExists(dirname($meta['output_path']));
                    File::put($meta['output_path'], $imageBinary);
                    Log::info('Replicate webhook successfully saved image', [
                        'prediction_id' => $predictionId,
                        'path' => $meta['output_path'],
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to download image from Replicate webhook: '.$e->getMessage());
            }
        }

        // If board_id was passed in custom metadata
        $boardId = $request->input('board_id') ?? ($meta['board_id'] ?? null);
        if ($boardId) {
            $board = SceneBoard::find($boardId);
            if ($board) {
                $board->update([
                    'status' => $board->audio_path ? 'ready' : 'image_ready',
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'prediction_id' => $predictionId,
        ]);
    }
}
