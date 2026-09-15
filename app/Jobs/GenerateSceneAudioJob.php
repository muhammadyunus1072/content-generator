<?php

namespace App\Jobs;

use App\Models\EpubScene;
use App\Services\TextToSpeechService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class GenerateSceneAudioJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public EpubScene $scene) {}

    public function handle(TextToSpeechService $ttsService): void
    {
        $this->scene->update(['status' => 'generating_audio', 'error_message' => null]);

        try {
            $project = $this->scene->project;
            $voice = $project?->voice_name ?: 'en-US-ChristopherNeural';
            $rate = $project?->tts_rate;
            $pitch = $project?->tts_pitch;

            $timestamp = time();
            $audioRelPath = "epub_projects/{$this->scene->epub_project_id}/audio/scene_{$this->scene->id}_{$timestamp}.mp3";
            $subtitleRelPath = "epub_projects/{$this->scene->epub_project_id}/subtitles/scene_{$this->scene->id}_{$timestamp}.vtt";

            $audioAbsPath = Storage::disk('public')->path($audioRelPath);
            $subtitleAbsPath = Storage::disk('public')->path($subtitleRelPath);
            File::ensureDirectoryExists(dirname($audioAbsPath));
            File::ensureDirectoryExists(dirname($subtitleAbsPath));

            $text = $this->scene->script_text;
            $ttsResult = $ttsService->synthesize(
                text: $text,
                outputAudioAbsolutePath: $audioAbsPath,
                outputSubtitleAbsolutePath: $subtitleAbsPath,
                voice: $voice,
                provider: 'edge-tts',
                rate: $rate,
                pitch: $pitch
            );

            $duration = (float) ($ttsResult['duration_seconds'] ?? 0);

            // If duration was not accurately calculated, probe with ffprobe
            if ($duration <= 0.1 && File::exists($audioAbsPath)) {
                $duration = $this->probeAudioDuration($audioAbsPath);
            }

            if ($duration <= 0.1) {
                // Fallback estimate: ~150 words per minute -> 2.5 words per second
                $wordCount = str_word_count($text) ?: 10;
                $duration = max(2.5, round($wordCount / 2.5, 2));
            }

            $hasSubtitle = $ttsResult['subtitle_path'] && File::exists($subtitleAbsPath) && File::size($subtitleAbsPath) > 0;

            $this->scene->update([
                'audio_path' => $audioRelPath,
                'subtitle_path' => $hasSubtitle ? $subtitleRelPath : null,
                'audio_duration' => $duration,
                'status' => 'audio_ready',
            ]);

            // Dispatch RenderSceneVideoJob once audio is ready
            RenderSceneVideoJob::dispatch($this->scene);
        } catch (Exception $e) {
            Log::error('GenerateSceneAudioJob failed', [
                'scene_id' => $this->scene->id,
                'error' => $e->getMessage(),
            ]);

            $this->scene->update([
                'status' => 'failed',
                'error_message' => 'TTS generation failed: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Probe audio duration using ffprobe command.
     */
    protected function probeAudioDuration(string $audioAbsPath): float
    {
        try {
            $process = new Process([
                'ffprobe',
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $audioAbsPath,
            ]);
            $process->run();

            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());

                return (float) $output;
            }
        } catch (Exception $e) {
            Log::warning('ffprobe duration check failed: '.$e->getMessage());
        }

        return 0.0;
    }

    public function failed(?Throwable $exception): void
    {
        $this->scene->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Audio job failed',
        ]);
    }
}
