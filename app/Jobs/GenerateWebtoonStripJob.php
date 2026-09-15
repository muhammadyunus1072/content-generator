<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\WebtoonStripService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateWebtoonStripJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public Episode $episode) {}

    public function handle(WebtoonStripService $service): void
    {
        try {
            $service->generateAndSliceEpisode($this->episode);
            Log::info('Webtoon strip generated successfully', ['episode_id' => $this->episode->id]);
        } catch (Exception $e) {
            Log::error('GenerateWebtoonStripJob failed', [
                'episode_id' => $this->episode->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
