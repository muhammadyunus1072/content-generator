<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\MangaPageLayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateMangaPagesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public Episode $episode,
        public ?string $direction = null
    ) {}

    public function handle(MangaPageLayoutService $layoutService): void
    {
        Log::info('GenerateMangaPagesJob started', [
            'episode_id' => $this->episode->id,
            'direction' => $this->direction,
        ]);

        $layoutService->generateMangaPages($this->episode, $this->direction);

        Log::info('GenerateMangaPagesJob finished', [
            'episode_id' => $this->episode->id,
            'page_count' => count($this->episode->fresh()->manga_pages ?? []),
        ]);
    }
}
