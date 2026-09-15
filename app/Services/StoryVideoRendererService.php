<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\SceneBoard;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class StoryVideoRendererService
{
    /**
     * Render an episode into a final MP4 video by composing and stitching all scene boards.
     *
     * @return string Absolute file path to the completed video
     */
    public function renderEpisodeVideo(Episode $episode): string
    {
        $boards = $episode->boards()->whereNotNull('image_path')->whereNotNull('audio_path')->orderBy('sequence_order')->get();

        if ($boards->isEmpty()) {
            throw new Exception('Episode has no ready boards with both image and audio.');
        }

        $story = $episode->story;
        $aspectRatio = $story ? $story->aspect_ratio : '16:9';
        $width = $aspectRatio === '9:16' ? 720 : 1280;
        $height = $aspectRatio === '9:16' ? 1280 : 720;

        $tempDir = storage_path('app/temp/render_episode_'.$episode->id.'_'.uniqid());
        File::ensureDirectoryExists($tempDir);

        $clipPaths = [];
        $ffmpegBin = env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe');

        try {
            // Render each board into an individual scene clip
            foreach ($boards as $index => $board) {
                $clipFile = $tempDir.'/clip_'.$index.'.mp4';
                $this->renderBoardClip($board, $clipFile, $width, $height, $ffmpegBin);
                if (File::exists($clipFile) && filesize($clipFile) > 0) {
                    $clipPaths[] = $clipFile;
                }
            }

            if (empty($clipPaths)) {
                throw new Exception('Failed to generate any scene clips.');
            }

            // Concatenate all clips into final video
            $concatListFile = $tempDir.'/concat_list.txt';
            $concatContent = '';
            foreach ($clipPaths as $clipPath) {
                $escapedPath = str_replace('\\', '/', $clipPath);
                $concatContent .= "file '{$escapedPath}'\n";
            }
            File::put($concatListFile, $concatContent);

            $outputRelativePath = 'stories/episodes/episode_'.$episode->id.'_'.time().'.mp4';
            $outputAbsolutePath = Storage::disk('public')->path($outputRelativePath);
            File::ensureDirectoryExists(dirname($outputAbsolutePath));

            $concatCmd = [
                $ffmpegBin,
                '-y',
                '-f',
                'concat',
                '-safe',
                '0',
                '-i',
                $concatListFile,
                '-c',
                'copy',
                $outputAbsolutePath,
            ];

            $concatProcess = new Process($concatCmd);
            $concatProcess->setTimeout(600);
            $concatProcess->run();

            if (! $concatProcess->isSuccessful() || ! File::exists($outputAbsolutePath) || filesize($outputAbsolutePath) === 0) {
                // If stream copy fails, re-encode concatenation
                $fallbackConcatCmd = [
                    $ffmpegBin,
                    '-y',
                    '-f',
                    'concat',
                    '-safe',
                    '0',
                    '-i',
                    $concatListFile,
                    '-c:v',
                    'libx264',
                    '-pix_fmt',
                    'yuv420p',
                    '-c:a',
                    'aac',
                    $outputAbsolutePath,
                ];

                $fallbackProcess = new Process($fallbackConcatCmd);
                $fallbackProcess->setTimeout(600);
                $fallbackProcess->run();

                if (! $fallbackProcess->isSuccessful() || ! File::exists($outputAbsolutePath)) {
                    throw new Exception('FFmpeg final concatenation failed: '.$fallbackProcess->getErrorOutput());
                }
            }

            // Calculate total duration
            $ttsService = new TextToSpeechService;
            $totalDuration = $ttsService->getAudioDuration($outputAbsolutePath);

            $episode->update([
                'video_path' => $outputRelativePath,
                'duration_seconds' => $totalDuration,
                'status' => 'completed',
            ]);

            return $outputAbsolutePath;
        } finally {
            // Clean up temporary clip directory
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Render an individual board clip (Image + Audio + Ken Burns motion).
     */
    protected function renderBoardClip(
        SceneBoard $board,
        string $outputClipPath,
        int $width,
        int $height,
        string $ffmpegBin
    ): void {
        $imageAbsolutePath = $this->resolveAbsolutePath($board->image_path);
        $audioAbsolutePath = $this->resolveAbsolutePath($board->audio_path);

        if (! File::exists($imageAbsolutePath) || ! File::exists($audioAbsolutePath)) {
            Log::warning('Board files missing for rendering clip', [
                'board_id' => $board->id,
                'image' => $imageAbsolutePath,
                'audio' => $audioAbsolutePath,
            ]);

            return;
        }

        $ttsService = new TextToSpeechService;
        $duration = $board->duration_seconds > 0 ? $board->duration_seconds : $ttsService->getAudioDuration($audioAbsolutePath);
        $fps = 25;
        $totalFrames = (int) ceil(($duration + 0.5) * $fps);

        // Pre-normalize input image into clean temporary single-frame PNG
        $tempPng = storage_path("app/temp/norm_board_{$board->id}_".uniqid().'.png');
        File::ensureDirectoryExists(dirname($tempPng));

        $normProcess = new Process([
            $ffmpegBin,
            '-y',
            '-i',
            $imageAbsolutePath,
            '-vframes',
            '1',
            $tempPng,
        ]);
        $normProcess->setTimeout(30);
        $normProcess->run();

        $inputImageForRender = ($normProcess->isSuccessful() && File::exists($tempPng) && File::size($tempPng) > 0)
            ? $tempPng
            : $imageAbsolutePath;

        // Ken Burns subtle zoom effect
        // Alternate zoom-in and zoom-out based on board number for dynamic cinematic flow
        $isZoomIn = ($board->board_number % 2 === 1);
        if ($isZoomIn) {
            $zoomExpr = 'min(zoom+0.0012,1.18)';
            $xExpr = 'iw/2-(iw/zoom/2)';
            $yExpr = 'ih/2-(ih/zoom/2)';
        } else {
            $zoomExpr = 'if(eq(on,1),1.18,max(zoom-0.0012,1.0))';
            $xExpr = 'iw/2-(iw/zoom/2)';
            $yExpr = 'ih/2-(ih/zoom/2)';
        }

        // Subtitle burning: Extract exact Edge-TTS VTT or use pause-weighted fallback
        $tempSrtPath = null;
        $subtitlesFilter = null;
        $subtitleGenerator = app(SubtitleGeneratorService::class);
        $srtContent = '';

        if ($board->subtitle_path) {
            $vttAbsPath = $this->resolveAbsolutePath($board->subtitle_path);
            if (File::exists($vttAbsPath) && File::size($vttAbsPath) > 0) {
                $vttContent = File::get($vttAbsPath);
                $srtContent = $subtitleGenerator->convertVttToSrt($vttContent);
            }
        }

        if (empty($srtContent) && ! empty($board->narration_chunk)) {
            $srtContent = $subtitleGenerator->generate($board->narration_chunk, $duration);
        }

        if (! empty($srtContent)) {
            $tempSrtPath = storage_path("app/temp/sub_board_{$board->id}_".uniqid().'.srt');
            File::ensureDirectoryExists(dirname($tempSrtPath));
            File::put($tempSrtPath, $srtContent);

            $escapedSrtPath = str_replace('\\', '/', $tempSrtPath);
            $escapedSrtPath = str_replace(':', '\\:', $escapedSrtPath);
            $fontSize = $height > 1000 ? 28 : 22;
            $forceStyle = "Fontname=Montserrat Black,FontSize={$fontSize},Alignment=2,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=3,Shadow=0,MarginV=50";
            $subtitlesFilter = "subtitles='{$escapedSrtPath}':force_style='{$forceStyle}'";
        }

        $baseFilter = "scale={$width}:{$height}:force_original_aspect_ratio=increase,crop={$width}:{$height},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$width}x{$height}:fps={$fps}";
        $filterComplex = $subtitlesFilter ? "{$baseFilter},{$subtitlesFilter}" : $baseFilter;

        $cmd = [
            $ffmpegBin,
            '-y',
            '-loop',
            '1',
            '-t',
            (string) ($duration + 0.2),
            '-i',
            $inputImageForRender,
            '-i',
            $audioAbsolutePath,
            '-filter_complex',
            $filterComplex,
            '-c:v',
            'libx264',
            '-tune',
            'stillimage',
            '-pix_fmt',
            'yuv420p',
            '-c:a',
            'aac',
            '-b:a',
            '192k',
            '-shortest',
            $outputClipPath,
        ];

        try {
            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($outputClipPath)) {
                Log::error('FFmpeg board clip render failed', [
                    'board_id' => $board->id,
                    'error' => $process->getErrorOutput(),
                ]);
                throw new Exception('FFmpeg clip render failed for board '.$board->id.': '.$process->getErrorOutput());
            }
        } finally {
            if (File::exists($tempPng)) {
                File::delete($tempPng);
            }
            if ($tempSrtPath && File::exists($tempSrtPath)) {
                File::delete($tempSrtPath);
            }
        }
    }

    /**
     * Resolve a storage or public path to an absolute path.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        return Storage::disk('public')->path($path);
    }
}
