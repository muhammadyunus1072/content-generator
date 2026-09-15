<?php

namespace App\Jobs;

use App\Models\EpubScene;
use App\Services\SubtitleGeneratorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class RenderSceneVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public EpubScene $scene) {}

    public function handle(): void
    {
        $this->scene->update(['status' => 'rendering_video', 'error_message' => null]);

        $normalizedImageAbsPath = null;
        $srtAbsPath = null;

        try {
            $project = $this->scene->project;
            if (! $project) {
                throw new Exception("Parent EpubProject not found for scene ID {$this->scene->id}");
            }

            $duration = (float) max(1.0, (float) ($this->scene->audio_duration ?: 4.0));
            $aspectRatio = $project->aspect_ratio ?: '16:9';
            $isVertical = ($aspectRatio === '9:16');
            $targetWidth = $isVertical ? 1080 : 1920;
            $targetHeight = $isVertical ? 1920 : 1080;

            $videoRelPath = "epub_projects/{$project->id}/videos/scene_{$this->scene->id}_".time().'.mp4';
            $videoAbsPath = Storage::disk('public')->path($videoRelPath);
            File::ensureDirectoryExists(dirname($videoAbsPath));

            // Resolve Audio Absolute Path
            $audioAbsPath = $this->scene->audio_path ? Storage::disk('public')->path($this->scene->audio_path) : null;
            if (! $audioAbsPath || ! File::exists($audioAbsPath) || File::size($audioAbsPath) === 0) {
                throw new Exception("Scene audio file missing or empty at: {$audioAbsPath}");
            }

            // 1. Generate Timed ALL-CAPS SRT Subtitle File (From Edge-TTS VTT or Pause-Weighted Fallback)
            $scriptText = $this->scene->script_text ?: '';
            $subtitleGenerator = app(SubtitleGeneratorService::class);
            $srtContent = '';

            if ($this->scene->subtitle_path) {
                $vttAbsPath = Storage::disk('public')->path($this->scene->subtitle_path);
                if (File::exists($vttAbsPath) && File::size($vttAbsPath) > 0) {
                    $vttContent = File::get($vttAbsPath);
                    $srtContent = $subtitleGenerator->convertVttToSrt($vttContent);
                }
            }

            if (empty($srtContent)) {
                $srtContent = $subtitleGenerator->generate($scriptText, $duration);
            }

            $srtAbsPath = storage_path("app/temp/sub_scene_{$this->scene->id}_".uniqid().'.srt');
            File::ensureDirectoryExists(dirname($srtAbsPath));
            File::put($srtAbsPath, $srtContent);

            $escapedSrtPath = str_replace('\\', '/', $srtAbsPath);
            $escapedSrtPath = str_replace(':', '\\:', $escapedSrtPath);
            // MarginV=50 raises subtitles by ~1 character height from bottom edge for optimal readability
            $forceStyle = 'Fontname=Montserrat Black,FontSize=24,Alignment=2,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=3,Shadow=0,MarginV=50';
            $subtitlesFilter = ! empty($srtContent) ? "subtitles='{$escapedSrtPath}':force_style='{$forceStyle}'" : null;

            // 2. Resolve Image Absolute Path & Pre-normalize to single frame PNG
            $imageAbsPath = $this->scene->image_path ? Storage::disk('public')->path($this->scene->image_path) : null;
            $hasValidNormalizedImage = false;
            $isTallerThan16x9 = false;

            if ($imageAbsPath && File::exists($imageAbsPath) && File::size($imageAbsPath) > 0) {
                $tempPng = storage_path("app/temp/norm_img_{$this->scene->id}_".uniqid().'.png');
                File::ensureDirectoryExists(dirname($tempPng));

                // Extract single frame into clean PNG using FFmpeg
                $normProcess = new Process([
                    'ffmpeg',
                    '-y',
                    '-i', $imageAbsPath,
                    '-vframes', '1',
                    $tempPng,
                ]);
                $normProcess->setTimeout(30);
                $normProcess->run();

                if ($normProcess->isSuccessful() && File::exists($tempPng) && File::size($tempPng) > 0) {
                    $normalizedImageAbsPath = $tempPng;
                    $hasValidNormalizedImage = true;

                    // Detect image aspect ratio to determine motion directive
                    $imgSize = @getimagesize($normalizedImageAbsPath);
                    $imgWidth = $imgSize[0] ?? 1920;
                    $imgHeight = $imgSize[1] ?? 1080;
                    $imageAspect = $imgHeight / max(1, $imgWidth);

                    // 16:9 ratio in height/width is 9/16 = 0.5625
                    $isTallerThan16x9 = ($imageAspect > (9 / 16));
                } else {
                    Log::warning('Image normalization failed, falling back to solid background', [
                        'scene_id' => $this->scene->id,
                        'image_path' => $imageAbsPath,
                        'error' => $normProcess->getErrorOutput(),
                    ]);
                }
            }

            $fps = 25;
            $totalFrames = (int) max(25, ceil($fps * $duration));

            // 3. Construct FFmpeg video filters (Motion Directive + Subtitle Burning)
            if ($hasValidNormalizedImage && $normalizedImageAbsPath) {
                if ($isTallerThan16x9) {
                    // Vertical Webtoon Scroll: Scale width to target and scroll smoothly from top to bottom with 0.5s top pause
                    $scrollDuration = max(0.5, $duration - 0.5);
                    $motionFilter = "scale={$targetWidth}:-2,crop={$targetWidth}:{$targetHeight}:0:'if(lt(t,0.5),0,min(in_h-out_h,(in_h-out_h)*((t-0.5)/{$scrollDuration})))',format=yuv420p";
                } else {
                    // Standard Ken Burns slow zoompan
                    $motionFilter = "scale={$targetWidth}:{$targetHeight}:force_original_aspect_ratio=increase,crop={$targetWidth}:{$targetHeight},zoompan=z='min(zoom+0.0012,1.2)':d={$totalFrames}:s={$targetWidth}x{$targetHeight}:fps={$fps},format=yuv420p";
                }

                $vfFilter = $subtitlesFilter ? "{$motionFilter},{$subtitlesFilter}" : $motionFilter;

                $command = [
                    'ffmpeg',
                    '-y',
                    '-loop', '1',
                    '-i', $normalizedImageAbsPath,
                    '-i', $audioAbsPath,
                    '-vf', $vfFilter,
                    '-c:v', 'libx264',
                    '-tune', 'stillimage',
                    '-c:a', 'aac',
                    '-b:a', '192k',
                    '-pix_fmt', 'yuv420p',
                    '-t', (string) $duration,
                    '-shortest',
                    $videoAbsPath,
                ];
            } else {
                // Generate solid stylized background if no image or normalization failed
                $vfFilter = $subtitlesFilter ?: 'null';

                $command = [
                    'ffmpeg',
                    '-y',
                    '-f', 'lavfi',
                    '-i', "color=c=0x111827:s={$targetWidth}x{$targetHeight}:d={$duration}:r={$fps}",
                    '-i', $audioAbsPath,
                    '-vf', $vfFilter,
                    '-c:v', 'libx264',
                    '-c:a', 'aac',
                    '-b:a', '192k',
                    '-pix_fmt', 'yuv420p',
                    '-t', (string) $duration,
                    '-shortest',
                    $videoAbsPath,
                ];
            }

            $process = new Process($command);
            $process->setTimeout($this->timeout);
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($videoAbsPath)) {
                throw new Exception('FFmpeg scene video render failed: '.$process->getErrorOutput());
            }

            $this->scene->update([
                'video_path' => $videoRelPath,
                'status' => 'completed',
            ]);

            // Increment rendered scenes count on project
            $project->increment('rendered_scenes');
            $project->refresh();

            // Check if all scenes for the project are completed and dispatch merge with atomic lock
            $remainingPending = $project->scenes()->where('status', '!=', 'completed')->count();
            if ($remainingPending === 0) {
                Cache::lock("merge_epub_project_{$project->id}", 60)->get(function () use ($project) {
                    $project->refresh();
                    if ($project->status !== 'merging_episode' && $project->status !== 'completed') {
                        MergeEpisodeVideoJob::dispatch($project);
                    }
                });
            }
        } catch (Exception $e) {
            Log::error('RenderSceneVideoJob failed', [
                'scene_id' => $this->scene->id,
                'error' => $e->getMessage(),
            ]);

            $this->scene->update([
                'status' => 'failed',
                'error_message' => 'Video render failed: '.$e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($normalizedImageAbsPath && File::exists($normalizedImageAbsPath)) {
                File::delete($normalizedImageAbsPath);
            }
            if ($srtAbsPath && File::exists($srtAbsPath)) {
                File::delete($srtAbsPath);
            }
        }
    }

    /**
     * Generate formatted, timed SRT subtitle content with UPPERCASE text and proportional chunking.
     */
    public function generateSrtContent(string $text, float $duration): string
    {
        return app(SubtitleGeneratorService::class)->generate($text, $duration);
    }

    /**
     * Format float seconds into standard SRT timecode: HH:MM:SS,mmm
     */
    protected function formatSrtTimecode(float $seconds): string
    {
        return app(SubtitleGeneratorService::class)->formatTimecode($seconds);
    }

    public function failed(?Throwable $exception): void
    {
        $this->scene->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Video render job failed',
        ]);
    }
}
