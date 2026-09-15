<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\StoryDecompositionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateStoryScenesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public Story $story) {}

    public function handle(StoryDecompositionService $service): void
    {
        $this->story->update(['status' => 'decomposing']);

        try {
            // Splicing story into episodes
            $episodes = $service->createEpisodesForStory($this->story, 1);

            foreach ($episodes as $episode) {
                $service->generateScenesAndBoardsForEpisode($episode);
            }

            $this->story->update(['status' => 'scenes_ready']);
        } catch (Exception $e) {
            Log::error('GenerateStoryScenesJob failed', [
                'story_id' => $this->story->id,
                'error' => $e->getMessage(),
            ]);

            $this->story->update(['status' => 'failed']);
            throw $e;
        }
    }
}
