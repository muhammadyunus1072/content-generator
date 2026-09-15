<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\MotionComicRendererService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RenderMotionComicJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;

    public function __construct(public Episode $episode) {}

    public function handle(MotionComicRendererService $renderer): void
    {
        $this->episode->update(['status' => 'rendering_motion_comic']);

        try {
            $renderer->renderEpisodeMotionComic($this->episode);
            $this->episode->update(['status' => 'completed']);
        } catch (Exception $e) {
            Log::error('RenderMotionComicJob failed', [
                'episode_id' => $this->episode->id,
                'error' => $e->getMessage(),
            ]);

            $this->episode->update(['status' => 'render_failed']);
            throw $e;
        }
    }
}
