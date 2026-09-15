<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\StoryVideoRendererService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RenderEpisodeVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public Episode $episode) {}

    public function handle(StoryVideoRendererService $renderer): void
    {
        $this->episode->update(['status' => 'rendering']);

        try {
            $renderer->renderEpisodeVideo($this->episode);
            $this->episode->update(['status' => 'completed']);
        } catch (Exception $e) {
            Log::error('RenderEpisodeVideoJob failed', [
                'episode_id' => $this->episode->id,
                'error' => $e->getMessage(),
            ]);

            $this->episode->update(['status' => 'render_failed']);
            throw $e;
        }
    }
}
