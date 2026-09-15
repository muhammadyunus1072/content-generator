<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\WebtoonExportService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExportWebtoonPackJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public Episode $episode) {}

    public function handle(WebtoonExportService $service): void
    {
        try {
            $zipPath = $service->exportPublishingPack($this->episode);
            Log::info('Webtoon publishing pack exported', ['episode_id' => $this->episode->id, 'zip' => $zipPath]);
        } catch (Exception $e) {
            Log::error('ExportWebtoonPackJob failed', [
                'episode_id' => $this->episode->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
