<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\YoutubeDownloadService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DownloadVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job timeout in seconds.
     * Video downloads can take time, so we set a generous timeout of 15 minutes.
     */
    public int $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video,
        public string $url
    ) {}

    /**
     * Execute the job.
     */
    public function handle(YoutubeDownloadService $downloadService): void
    {
        $this->video->update([
            'status' => 'downloading',
        ]);

        try {
            // 1. Fetch the video title automatically using the service
            $downloadedTitle = $downloadService->getTitle($this->url);

            // 2. Generate local destination path
            $videoDir = storage_path('app/private/videos');
            if (! file_exists($videoDir)) {
                mkdir($videoDir, 0755, true);
            }

            $filename = 'download_'.time().'_'.Str::random(8).'.mp4';
            $outputPath = $videoDir.'/'.$filename;
            $relativeVideoPath = 'videos/'.$filename;

            // 3. Download the video using the service
            $downloadService->download($this->url, $outputPath, $this->timeout);

            $isManual = ($this->video->processing_mode ?? 'automatic') === 'manual';

            // 4. Update the Video model on completion
            $this->video->update([
                'title' => $this->video->title ?: ($downloadedTitle ?? 'YouTube Video'),
                'original_filename' => $downloadedTitle ? ($downloadedTitle.'.mp4') : 'youtube_download.mp4',
                'video_path' => $relativeVideoPath,
                'status' => $isManual ? 'completed' : 'pending',
            ]);

            // 5. Dispatch next steps
            if ($isManual) {
                $clips = $this->video->clips()->get();
                foreach ($clips as $clip) {
                    $clip->update(['status' => 'processing']);
                    CutClipJob::dispatch($clip);
                }
            } else {
                ExtractAudioJob::dispatch($this->video);
            }

        } catch (Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
