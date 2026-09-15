<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\ComicArchiveExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExportComicArchiveJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public Episode $episode,
        public string $format = 'both' // 'cbz', 'pdf', or 'both'
    ) {}

    public function handle(ComicArchiveExportService $exporter): void
    {
        Log::info('ExportComicArchiveJob started', [
            'episode_id' => $this->episode->id,
            'format' => $this->format,
        ]);

        if (in_array($this->format, ['cbz', 'both'])) {
            $exporter->exportCbzArchive($this->episode);
        }

        if (in_array($this->format, ['pdf', 'both'])) {
            $exporter->exportPdfDocument($this->episode);
        }

        Log::info('ExportComicArchiveJob finished', [
            'episode_id' => $this->episode->id,
            'cbz' => $this->episode->fresh()->cbz_package_path,
            'pdf' => $this->episode->fresh()->pdf_package_path,
        ]);
    }
}
