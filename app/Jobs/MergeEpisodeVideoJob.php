<?php

namespace App\Jobs;

use App\Models\EpubProject;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class MergeEpisodeVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public EpubProject $project) {}

    public function handle(): void
    {
        $this->project->update(['status' => 'merging_episode', 'error_message' => null]);

        $concatManifestPath = null;

        try {
            $scenes = $this->project->scenes()->orderBy('sequence_order')->get();

            if ($scenes->isEmpty()) {
                throw new Exception('No scenes available to merge for project ID '.$this->project->id);
            }

            $validVideoPaths = [];
            foreach ($scenes as $scene) {
                if (! $scene->video_path) {
                    throw new Exception("Scene #{$scene->sequence_order} has no rendered video path.");
                }

                $absPath = Storage::disk('public')->path($scene->video_path);
                if (! File::exists($absPath)) {
                    throw new Exception("Rendered video file missing for scene #{$scene->sequence_order} at {$absPath}");
                }

                $validVideoPaths[] = $absPath;
            }

            $outputRelPath = "epub_projects/{$this->project->id}/final_episode_".time().'.mp4';
            $outputAbsPath = Storage::disk('public')->path($outputRelPath);
            File::ensureDirectoryExists(dirname($outputAbsPath));

            // Generate Concat Demuxer Text File
            $concatManifestPath = storage_path("app/temp/concat_project_{$this->project->id}_".uniqid().'.txt');
            File::ensureDirectoryExists(dirname($concatManifestPath));

            $concatContent = '';
            foreach ($validVideoPaths as $videoPath) {
                // Escape single quotes for ffmpeg concat demuxer
                $escapedPath = str_replace("'", "'\\''", str_replace('\\', '/', $videoPath));
                $concatContent .= "file '{$escapedPath}'\n";
            }
            File::put($concatManifestPath, $concatContent);

            // Attempt fast stream copy first
            $copyCommand = [
                'ffmpeg',
                '-y',
                '-f', 'concat',
                '-safe', '0',
                '-i', $concatManifestPath,
                '-c', 'copy',
                $outputAbsPath,
            ];

            $process = new Process($copyCommand);
            $process->setTimeout($this->timeout);
            $process->run();

            // If copy failed or output invalid, fall back to safe re-encode
            if (! $process->isSuccessful() || ! File::exists($outputAbsPath) || File::size($outputAbsPath) < 1024) {
                Log::warning('FFmpeg stream copy concat failed, attempting re-encode concat', [
                    'project_id' => $this->project->id,
                    'error' => $process->getErrorOutput(),
                ]);

                $reencodeCommand = [
                    'ffmpeg',
                    '-y',
                    '-f', 'concat',
                    '-safe', '0',
                    '-i', $concatManifestPath,
                    '-c:v', 'libx264',
                    '-preset', 'fast',
                    '-c:a', 'aac',
                    '-b:a', '192k',
                    '-pix_fmt', 'yuv420p',
                    $outputAbsPath,
                ];

                $reencodeProcess = new Process($reencodeCommand);
                $reencodeProcess->setTimeout($this->timeout);
                $reencodeProcess->run();

                if (! $reencodeProcess->isSuccessful() || ! File::exists($outputAbsPath)) {
                    throw new Exception('FFmpeg final episode merge failed: '.$reencodeProcess->getErrorOutput());
                }
            }

            $this->project->update([
                'output_video_path' => $outputRelPath,
                'status' => 'completed',
            ]);
        } catch (Exception $e) {
            Log::error('MergeEpisodeVideoJob failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Episode merge failed: '.$e->getMessage(),
            ]);

            throw $e;
        } finally {
            if ($concatManifestPath && File::exists($concatManifestPath)) {
                File::delete($concatManifestPath);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->project->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Episode merge job failed',
        ]);
    }
}
