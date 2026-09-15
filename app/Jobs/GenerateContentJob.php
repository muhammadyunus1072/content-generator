<?php

namespace App\Jobs;

use App\Models\Clip;
use App\Models\Video;
use App\Services\ContentGenerationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public Video $video) {}

    /**
     * Execute the job.
     */
    public function handle(ContentGenerationService $contentService): void
    {
        $this->video->update([
            'status' => 'ai_processing',
            'error_message' => null,
        ]);

        try {
            // Generate clip data suggestions from Gemini
            $clipsData = $contentService->generateClips($this->video);

            foreach ($clipsData as $clipData) {
                $clip = Clip::create([
                    'video_id' => $this->video->id,
                    'title' => $clipData['title'],
                    'description' => $clipData['description'],
                    'start_seconds' => $clipData['start_seconds'],
                    'end_seconds' => $clipData['end_seconds'],
                    'metadata' => ['tags' => $clipData['tags']],
                    'status' => 'processing',
                ]);

                // Dispatch background cutting job
                CutClipJob::dispatch($clip);
            }

            // Set video status to completed indicating AI suggestions are generated
            $this->video->update([
                'status' => 'completed',
            ]);

            // Next step in pipeline: CreateClipsJob (video cutting) can be dispatched here
        } catch (Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
