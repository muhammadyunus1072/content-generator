<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoBuilderProject;
use App\Services\YoutubeDownloadService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MergeVideoBuilderJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public VideoBuilderProject $project) {}

    public function handle(YoutubeDownloadService $downloadService): void
    {
        $this->project->update([
            'status' => 'rendering',
            'error_message' => null,
        ]);

        try {
            $mainVideoAbsPath = $this->resolveMainVideo($downloadService);

            if (! File::exists($mainVideoAbsPath)) {
                throw new Exception("Source main video not found at: {$mainVideoAbsPath}");
            }

            // Determine canvas dimensions
            $aspectRatio = $this->project->aspect_ratio ?: '16:9';
            $isVertical = ($aspectRatio === '9:16');
            $canvasWidth = $isVertical ? 1080 : 1920;
            $canvasHeight = $isVertical ? 1920 : 1080;

            // Prepare destination output file
            $outputDir = storage_path('app/public/video_builder');
            File::ensureDirectoryExists($outputDir);
            $filename = 'merged_'.$this->project->id.'_'.time().'_'.Str::random(6).'.mp4';
            $outputAbsPath = $outputDir.'/'.$filename;
            $outputRelPath = 'video_builder/'.$filename;

            $assets = $this->project->assets_config ?? [];

            if (empty($assets)) {
                // Direct Passthrough mode: copy source video directly without heavy FFmpeg re-encoding
                File::copy($mainVideoAbsPath, $outputAbsPath);
            } else {
                // Build FFmpeg command inputs and filter graph
                $ffmpegBinary = config('laravel-ffmpeg.ffmpeg.binaries', 'ffmpeg');
                $command = [$ffmpegBinary, '-y'];

                // Input 0: Main video
                $command[] = '-i';
                $command[] = $mainVideoAbsPath;

                $inputCount = 1;
                $audioInputs = ['0:a'];
                $filterComplex = [];

                // Base video filter: scale and pad to target canvas resolution with black bars if needed
                $filterComplex[] = "[0:v]scale={$canvasWidth}:{$canvasHeight}:force_original_aspect_ratio=decrease,pad={$canvasWidth}:{$canvasHeight}:(ow-iw)/2:(oh-ih)/2:black,setsar=1[vbase]";

                $lastVideoStream = 'vbase';

                foreach ($assets as $idx => $asset) {
                    $assetPath = $this->resolveAssetPath($asset);
                    if (! $assetPath || ! File::exists($assetPath)) {
                        Log::warning("VideoBuilder asset file missing at: {$assetPath}", ['asset' => $asset]);

                        continue;
                    }

                    $inputIdx = $inputCount++;
                    $isImage = ($asset['type'] ?? 'image') === 'image';

                    $hasStart = isset($asset['start_seconds']) && is_numeric($asset['start_seconds']) && (float) $asset['start_seconds'] > 0;
                    $hasEnd = isset($asset['end_seconds']) && is_numeric($asset['end_seconds']) && (float) $asset['end_seconds'] > 0;

                    // Append asset input
                    if ($isImage) {
                        $command[] = '-loop';
                        $command[] = '1';
                    } elseif (! $hasStart && ! $hasEnd) {
                        // Loop video continuously if no start and end timestamps are specified
                        $command[] = '-stream_loop';
                        $command[] = '-1';
                    }
                    $command[] = '-i';
                    $command[] = $assetPath;

                    // Calculate overlay dimensions
                    $wPercent = max(1.0, min(100.0, (float) ($asset['width_percent'] ?? 25.0)));
                    $hPercent = max(1.0, min(100.0, (float) ($asset['height_percent'] ?? 25.0)));
                    $ow = (int) round(($wPercent / 100.0) * $canvasWidth);
                    $oh = (int) round(($hPercent / 100.0) * $canvasHeight);
                    $ow = ($ow % 2 === 0) ? $ow : $ow + 1;
                    $oh = ($oh % 2 === 0) ? $oh : $oh + 1;
                    $ow = max(2, $ow);
                    $oh = max(2, $oh);

                    // Calculate position offsets
                    $xPercent = max(0.0, min(100.0, (float) ($asset['x_percent'] ?? 0.0)));
                    $yPercent = max(0.0, min(100.0, (float) ($asset['y_percent'] ?? 0.0)));
                    $ox = max(0, min($canvasWidth - 2, (int) round(($xPercent / 100.0) * $canvasWidth)));
                    $oy = max(0, min($canvasHeight - 2, (int) round(($yPercent / 100.0) * $canvasHeight)));

                    $streamOvl = "ovl_{$idx}";
                    $removeGreen = ! $isImage && ! empty($asset['remove_green_screen']);

                    if ($removeGreen) {
                        // Apply chromakey for green screen #00FF00 (with 0.15 similarity, 0.08 blend)
                        $filterComplex[] = "[{$inputIdx}:v]chromakey=0x00FF00:0.15:0.08,scale={$ow}:{$oh}[{$streamOvl}]";
                    } else {
                        $filterComplex[] = "[{$inputIdx}:v]scale={$ow}:{$oh}[{$streamOvl}]";
                    }

                    // Timing enable clause
                    $enableClause = '';

                    if ($hasStart && $hasEnd) {
                        $s = (float) $asset['start_seconds'];
                        $e = (float) $asset['end_seconds'];
                        $enableClause = ":enable='between(t,{$s},{$e})'";
                    } elseif ($hasStart) {
                        $s = (float) $asset['start_seconds'];
                        $enableClause = ":enable='gte(t,{$s})'";
                    } elseif ($hasEnd) {
                        $e = (float) $asset['end_seconds'];
                        $enableClause = ":enable='lte(t,{$e})'";
                    }

                    $nextVideoStream = "v_step_{$idx}";
                    $shortestClause = $isImage ? ':shortest=1' : '';
                    $filterComplex[] = "[{$lastVideoStream}][{$streamOvl}]overlay={$ox}:{$oy}{$enableClause}{$shortestClause}[{$nextVideoStream}]";
                    $lastVideoStream = $nextVideoStream;

                    // Handle Audio mixing if video overlay has unmuted audio
                    if (! $isImage && empty($asset['mute_audio'])) {
                        $audioInputs[] = "{$inputIdx}:a";
                    }
                }

                // Audio mixing filter if multiple audio inputs exist
                $audioMap = '0:a?';
                if (count($audioInputs) > 1) {
                    $amixInputs = implode('', array_map(fn ($id) => "[{$id}]", $audioInputs));
                    $amixCount = count($audioInputs);
                    $filterComplex[] = "{$amixInputs}amix=inputs={$amixCount}:duration=first:dropout_transition=2[aout]";
                    $audioMap = '[aout]';
                }

                // Assemble command
                $command[] = '-filter_complex';
                $command[] = implode(';', $filterComplex);
                $command[] = '-map';
                $command[] = "[{$lastVideoStream}]";
                $command[] = '-map';
                $command[] = $audioMap;

                // Output codecs and rapid encoding parameters
                $command[] = '-c:v';
                $command[] = 'libx264';
                $command[] = '-preset';
                $command[] = 'veryfast';
                $command[] = '-pix_fmt';
                $command[] = 'yuv420p';
                $command[] = '-movflags';
                $command[] = '+faststart';
                $command[] = '-c:a';
                $command[] = 'aac';
                $command[] = '-b:a';
                $command[] = '192k';
                $command[] = '-shortest';
                $command[] = $outputAbsPath;

                $process = new Process($command);
                $process->setTimeout($this->timeout);
                $process->run();

                if (! $process->isSuccessful() || ! File::exists($outputAbsPath) || File::size($outputAbsPath) < 1000) {
                    throw new Exception('FFmpeg video merge failed: '.$process->getErrorOutput());
                }
            }

            $savedVideoId = null;

            if ($this->project->save_to_library) {
                $videoTitle = $this->project->title ?: ($this->project->main_source_type === 'url' ? 'Downloaded Video' : 'Merged Video');
                $origFilename = $this->project->main_source_type === 'url'
                    ? (basename(parse_url($this->project->main_video_url ?: '', PHP_URL_PATH) ?: '') ?: 'download.mp4')
                    : basename($outputRelPath);

                $libraryVideo = Video::create([
                    'user_id' => $this->project->user_id,
                    'title' => $videoTitle,
                    'original_filename' => $origFilename,
                    'video_path' => $outputRelPath,
                    'status' => 'completed',
                    'processing_mode' => 'video_builder',
                ]);

                $savedVideoId = $libraryVideo->id;
            }

            $this->project->update([
                'status' => 'completed',
                'output_video_path' => $outputRelPath,
                'saved_video_id' => $savedVideoId,
            ]);
        } catch (Exception $e) {
            Log::error('VideoBuilder merge failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->project->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve or download the main source video.
     */
    protected function resolveMainVideo(YoutubeDownloadService $downloadService): string
    {
        if ($this->project->main_source_type === 'url') {
            if ($this->project->main_video_path && Storage::disk('local')->exists($this->project->main_video_path)) {
                return Storage::disk('local')->path($this->project->main_video_path);
            }

            $this->project->update(['status' => 'downloading_source']);

            $videoDir = storage_path('app/private/videos');
            File::ensureDirectoryExists($videoDir);
            $filename = 'download_builder_'.$this->project->id.'_'.time().'.mp4';
            $outputPath = $videoDir.'/'.$filename;
            $relPath = 'videos/'.$filename;

            $downloadService->download($this->project->main_video_url, $outputPath, $this->timeout);

            $this->project->update([
                'main_video_path' => $relPath,
                'status' => 'rendering',
            ]);

            return $outputPath;
        }

        if ($this->project->main_video_path) {
            if (Storage::disk('local')->exists($this->project->main_video_path)) {
                return Storage::disk('local')->path($this->project->main_video_path);
            }
            if (Storage::disk('public')->exists($this->project->main_video_path)) {
                return Storage::disk('public')->path($this->project->main_video_path);
            }
            if (File::exists($this->project->main_video_path)) {
                return $this->project->main_video_path;
            }
        }

        throw new Exception("Main video path not found: {$this->project->main_video_path}");
    }

    /**
     * Resolve the absolute path for an overlay asset.
     */
    protected function resolveAssetPath(array $asset): ?string
    {
        $relPath = $asset['file_path'] ?? null;
        if (! $relPath) {
            return null;
        }

        if (Storage::disk('public')->exists($relPath)) {
            return Storage::disk('public')->path($relPath);
        }

        if (Storage::disk('local')->exists($relPath)) {
            return Storage::disk('local')->path($relPath);
        }

        if (File::exists($relPath)) {
            return $relPath;
        }

        return null;
    }
}
