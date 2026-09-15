<?php

namespace App\Jobs;

use App\Models\Clip;
use Exception;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class CutClipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public Clip $clip) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->clip->update([
            'status' => 'processing',
        ]);

        try {
            $video = $this->clip->video;

            if (empty($video->video_path) || ! Storage::disk('local')->exists($video->video_path)) {
                throw new Exception('Source video file not found at: '.($video->video_path ?? 'empty'));
            }

            // Ensure destination clips folder exists on public disk
            if (! Storage::disk('public')->exists('clips')) {
                Storage::disk('public')->makeDirectory('clips');
            }

            // Generate clean filename
            $rawTitle = $this->clip->title ?: 'clip';
            $safeTitle = Str::slug($rawTitle) ?: 'clip';
            $clipPath = 'clips/'.$safeTitle.'_'.time().'.mp4';

            // Calculate duration
            $duration = (float) $this->clip->end_seconds - (float) $this->clip->start_seconds;
            if ($duration <= 0) {
                throw new Exception("Invalid clip duration: {$duration} seconds. End seconds must be greater than start seconds.");
            }

            // Construct the 9:16 aspect ratio blurred background filter graph (no captions/drawtext)
            $filterGraph = '[0:v]split=2[bg][fg];';
            $filterGraph .= '[bg]scale=w=720:h=1280:force_original_aspect_ratio=increase,crop=720:1280,boxblur=luma_radius=20:luma_power=1[bg];';
            $filterGraph .= '[fg]scale=w=720:h=-2[fg];';
            $filterGraph .= '[bg][fg]overlay=(W-w)/2:(H-h)/2,setsar=1';

            // Optimize X264 format to single pass and veryfast preset for rapid social clip generation
            $format = (new X264('aac', 'libx264'))
                ->setPasses(1)
                ->setAdditionalParameters([
                    '-preset', 'veryfast',
                    '-movflags', '+faststart',
                ]);

            // Cut, scale, blur background, overlay foreground and save to public disk
            FFMpeg::fromDisk('local')
                ->open($video->video_path)
                ->export()
                ->toDisk('public')
                ->inFormat($format)
                ->addFilter(function ($filters) use ($duration) {
                    $filters->clip(
                        TimeCode::fromSeconds((float) $this->clip->start_seconds),
                        TimeCode::fromSeconds($duration)
                    );
                })
                ->addFilter(['-filter_complex', $filterGraph])
                ->save($clipPath);

            $this->clip->update([
                'clip_path' => $clipPath,
                'status' => 'completed',
            ]);
        } catch (Exception $e) {
            $this->clip->update([
                'status' => 'failed',
                'metadata' => array_merge($this->clip->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }
}
