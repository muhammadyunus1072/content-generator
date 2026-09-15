<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\SceneBoard;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class MotionComicRendererService
{
    public function __construct(
        protected AudioMixerService $audioMixer,
        protected SubtitleGeneratorService $subtitleGenerator,
        protected SpeechBubbleGeneratorService $speechBubbleGenerator
    ) {}

    /**
     * Render an episode into a motion comic video.
     *
     * @return string Absolute file path to the completed video
     */
    public function renderEpisodeMotionComic(Episode $episode): string
    {
        $boards = $episode->boards()
            ->where(function ($query) {
                $query->whereNotNull('image_path')
                    ->orWhereNotNull('speech_bubble_image_path');
            })
            ->whereNotNull('audio_path')
            ->orderBy('sequence_order')
            ->get();

        if ($boards->isEmpty()) {
            throw new Exception('Episode has no ready comic boards with image and audio.');
        }

        $story = $episode->story;
        $aspectRatio = $story ? $story->aspect_ratio : '16:9';
        $width = $aspectRatio === '9:16' ? 720 : 1280;
        $height = $aspectRatio === '9:16' ? 1280 : 720;

        $tempDir = storage_path('app/temp/motion_comic_'.$episode->id.'_'.uniqid());
        File::ensureDirectoryExists($tempDir);

        $clipPaths = [];
        $ffmpegBin = env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe');

        try {
            // Render each board into an animated motion comic clip
            foreach ($boards as $index => $board) {
                $clipFile = $tempDir.'/clip_'.$index.'.mp4';
                $this->renderBoardMotionClip($board, $clipFile, $width, $height, $ffmpegBin);
                if (File::exists($clipFile) && filesize($clipFile) > 0) {
                    $clipPaths[] = $clipFile;
                }
            }

            if (empty($clipPaths)) {
                throw new Exception('Failed to generate any motion comic scene clips.');
            }

            // Concatenate all clips into final video
            $concatListFile = $tempDir.'/concat_list.txt';
            $concatContent = '';
            foreach ($clipPaths as $clipPath) {
                $escapedPath = str_replace('\\', '/', $clipPath);
                $concatContent .= "file '{$escapedPath}'\n";
            }
            File::put($concatListFile, $concatContent);

            $outputRelativePath = 'stories/episodes/motion_comic_episode_'.$episode->id.'_'.time().'.mp4';
            $outputAbsolutePath = Storage::disk('public')->path($outputRelativePath);
            File::ensureDirectoryExists(dirname($outputAbsolutePath));

            $concatCmd = [
                $ffmpegBin,
                '-y',
                '-f', 'concat',
                '-safe', '0',
                '-i', $concatListFile,
                '-c', 'copy',
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
                    '-f', 'concat',
                    '-safe', '0',
                    '-i', $concatListFile,
                    '-c:v', 'libx264',
                    '-pix_fmt', 'yuv420p',
                    '-c:a', 'aac',
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
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Render an individual motion comic board clip with animation effects and audio mix.
     */
    public function renderBoardMotionClip(
        SceneBoard $board,
        string $outputClipPath,
        int $width,
        int $height,
        string $ffmpegBin
    ): void {
        // 1. Choose image: prefer speech bubble burned image if available
        $imagePath = $board->speech_bubble_image_path ?: $board->image_path;
        $imageAbsolutePath = $this->resolveAbsolutePath($imagePath);

        // 2. Mix audio layers (voice + BGM + SFX)
        $mixedAudioRelative = $this->audioMixer->mixBoardAudio($board);
        $audioAbsolutePath = $this->resolveAbsolutePath($mixedAudioRelative);

        if (! File::exists($imageAbsolutePath) || ! File::exists($audioAbsolutePath)) {
            Log::warning('Board files missing for motion comic clip', [
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
        $tempPng = storage_path("app/temp/norm_mcomic_{$board->id}_".uniqid().'.png');
        File::ensureDirectoryExists(dirname($tempPng));

        $normProcess = new Process([
            $ffmpegBin,
            '-y',
            '-i', $imageAbsolutePath,
            '-vframes', '1',
            $tempPng,
        ]);
        $normProcess->setTimeout(30);
        $normProcess->run();

        $inputImageForRender = ($normProcess->isSuccessful() && File::exists($tempPng) && File::size($tempPng) > 0)
            ? $tempPng
            : $imageAbsolutePath;

        // 3. Build video filter according to chosen animation effect or custom keyframes
        $animEffect = $board->animation_effect ?? 'ken_burns';
        if (! empty($board->camera_keyframes) && is_array($board->camera_keyframes)) {
            $videoFilter = $this->buildDynamicKeyframeFilter($board->camera_keyframes, $width, $height, $totalFrames, $fps);
        } else {
            $videoFilter = $this->buildAnimationFilter($animEffect, $width, $height, $totalFrames, $fps, $board->board_number);
        }

        // 4. Add particle / atmosphere overlay if configured
        if ($board->has_particles && ! empty($board->particle_type)) {
            $videoFilter = $this->appendParticleFilter($videoFilter, $board->particle_type);
        }

        // 5. Subtitle burning (if enabled and narration exists)
        $tempSrtPath = null;
        $subtitlesFilter = null;
        $srtContent = '';

        if ($board->subtitle_path) {
            $vttAbsPath = $this->resolveAbsolutePath($board->subtitle_path);
            if (File::exists($vttAbsPath) && File::size($vttAbsPath) > 0) {
                $vttContent = File::get($vttAbsPath);
                $srtContent = $this->subtitleGenerator->convertVttToSrt($vttContent);
            }
        }

        if (empty($srtContent) && ! empty($board->narration_chunk)) {
            $srtContent = $this->subtitleGenerator->generate($board->narration_chunk, $duration);
        }

        if (! empty($srtContent)) {
            $tempSrtPath = storage_path("app/temp/sub_mcomic_{$board->id}_".uniqid().'.srt');
            File::ensureDirectoryExists(dirname($tempSrtPath));
            File::put($tempSrtPath, $srtContent);

            $escapedSrtPath = str_replace('\\', '/', $tempSrtPath);
            $escapedSrtPath = str_replace(':', '\\:', $escapedSrtPath);
            $fontSize = $height > 1000 ? 28 : 22;
            $forceStyle = "Fontname=Montserrat Black,FontSize={$fontSize},Alignment=2,PrimaryColour=&H00FFFFFF,OutlineColour=&H00000000,BorderStyle=1,Outline=3,Shadow=0,MarginV=50";
            $subtitlesFilter = "subtitles='{$escapedSrtPath}':force_style='{$forceStyle}'";
        }

        $filterComplex = $subtitlesFilter ? "{$videoFilter},{$subtitlesFilter}" : $videoFilter;

        $cmd = [
            $ffmpegBin,
            '-y',
            '-loop', '1',
            '-t', (string) ($duration + 0.2),
            '-i', $inputImageForRender,
            '-i', $audioAbsolutePath,
            '-filter_complex', $filterComplex,
            '-c:v', 'libx264',
            '-tune', 'stillimage',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-shortest',
            $outputClipPath,
        ];

        try {
            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($outputClipPath)) {
                Log::error('FFmpeg motion comic clip render failed', [
                    'board_id' => $board->id,
                    'error' => $process->getErrorOutput(),
                ]);
                throw new Exception('FFmpeg clip render failed: '.$process->getErrorOutput());
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
     * Build dynamic FFmpeg zoompan filter compiled from custom user keyframes.
     *
     * @param  array<int, array{time_pct: int|float, zoom?: float, x_pct?: int|float, y_pct?: int|float, shake?: int}>  $keyframes
     */
    public function buildDynamicKeyframeFilter(array $keyframes, int $w, int $h, int $totalFrames, int $fps): string
    {
        $baseScale = "scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h}";

        if (empty($keyframes)) {
            return $this->buildAnimationFilter('ken_burns', $w, $h, $totalFrames, $fps, 1);
        }

        // Sort keyframes by time_pct
        usort($keyframes, fn ($a, $b) => ($a['time_pct'] ?? 0) <=> ($b['time_pct'] ?? 0));

        $first = $keyframes[0];
        $last = end($keyframes);

        $zStart = (float) ($first['zoom'] ?? 1.0);
        $zEnd = (float) ($last['zoom'] ?? 1.25);
        $zDelta = ($totalFrames > 0) ? ($zEnd - $zStart) / $totalFrames : 0;
        $zDeltaFormatted = sprintf('%.6f', $zDelta);

        // Zoom expression
        if (abs($zDelta) > 0.000001) {
            $zoomExpr = $zDelta > 0
                ? "min({$zStart}+on*{$zDeltaFormatted},{$zEnd})"
                : "max({$zStart}+on*{$zDeltaFormatted},{$zEnd})";
        } else {
            $zoomExpr = sprintf('%.2f', $zStart);
        }

        // Pan positions
        $xStartPct = (float) ($first['x_pct'] ?? 50) / 100;
        $xEndPct = (float) ($last['x_pct'] ?? 50) / 100;
        $xDelta = ($totalFrames > 0) ? ($xEndPct - $xStartPct) / $totalFrames : 0;
        $xDeltaFormatted = sprintf('%.6f', $xDelta);

        $yStartPct = (float) ($first['y_pct'] ?? 50) / 100;
        $yEndPct = (float) ($last['y_pct'] ?? 50) / 100;
        $yDelta = ($totalFrames > 0) ? ($yEndPct - $yStartPct) / $totalFrames : 0;
        $yDeltaFormatted = sprintf('%.6f', $yDelta);

        $xExpr = "(iw-iw/zoom)*({$xStartPct}+on*{$xDeltaFormatted})";
        $yExpr = "(ih-ih/zoom)*({$yStartPct}+on*{$yDeltaFormatted})";

        // Check for max shake
        $maxShake = 0;
        foreach ($keyframes as $kf) {
            if (! empty($kf['shake']) && (int) $kf['shake'] > $maxShake) {
                $maxShake = (int) $kf['shake'];
            }
        }

        if ($maxShake > 0) {
            $xExpr .= "+sin(2*PI*on/4)*{$maxShake}";
            $yExpr .= "+cos(2*PI*on/3)*{$maxShake}";
        }

        return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";
    }

    /**
     * Build animation filter for Ken Burns, Parallax, Slide-in, Camera Shake, or Impact Frame.
     */
    protected function buildAnimationFilter(string $effect, int $w, int $h, int $totalFrames, int $fps, int $boardNumber): string
    {
        $baseScale = "scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h}";

        switch ($effect) {
            case 'camera_shake':
                // Rapid high-energy vibrating screen for action/combat
                $xExpr = 'iw/2-(iw/zoom/2)+sin(2*PI*on/4)*12';
                $yExpr = 'ih/2-(ih/zoom/2)+cos(2*PI*on/3)*9';
                $zoomExpr = '1.06+0.02*sin(2*PI*on/6)';

                return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";

            case 'impact_frame':
                // Initial sudden zoom explosion followed by subtle floating
                $zoomExpr = 'if(lt(on,10),1.25,max(1.08,1.25-(on-10)*0.003))';
                $xExpr = 'iw/2-(iw/zoom/2)';
                $yExpr = 'ih/2-(ih/zoom/2)';

                return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";

            case 'slide_in':
                // Easing slide in from side
                $isFromLeft = ($boardNumber % 2 === 1);
                if ($isFromLeft) {
                    $xExpr = 'if(lt(on,15),(15-on)*18,iw/2-(iw/zoom/2))';
                } else {
                    $xExpr = 'if(lt(on,15),-(15-on)*18,iw/2-(iw/zoom/2))';
                }
                $yExpr = 'ih/2-(ih/zoom/2)';
                $zoomExpr = '1.05';

                return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";

            case 'parallax':
                // Diagonal slow cinematic drift (giving depth sensation)
                $zoomExpr = '1.14';
                $xExpr = 'if(eq(on,1),0,min(x+0.8,iw-(iw/zoom)))';
                $yExpr = 'if(eq(on,1),0,min(y+0.4,ih-(ih/zoom)))';

                return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";

            case 'ken_burns':
            default:
                $isZoomIn = ($boardNumber % 2 === 1);
                if ($isZoomIn) {
                    $zoomExpr = 'min(zoom+0.0014,1.20)';
                } else {
                    $zoomExpr = 'if(eq(on,1),1.20,max(zoom-0.0014,1.0))';
                }
                $xExpr = 'iw/2-(iw/zoom/2)';
                $yExpr = 'ih/2-(ih/zoom/2)';

                return "{$baseScale},zoompan=z='{$zoomExpr}':d={$totalFrames}:x='{$xExpr}':y='{$yExpr}':s={$w}x{$h}:fps={$fps}";
        }
    }

    /**
     * Append atmosphere or particle filter (rain, snow, sparks, embers).
     */
    protected function appendParticleFilter(string $baseFilter, string $particleType): string
    {
        return match ($particleType) {
            'fire_embers', 'sparks' => "{$baseFilter},curves=vintage,noise=alls=15:allf=t+u",
            'rain' => "{$baseFilter},noise=alls=10:allf=t",
            'snow' => "{$baseFilter},colorbalance=bs=0.08:ms=0.05,noise=alls=8:allf=t",
            'dust' => "{$baseFilter},noise=alls=6:allf=t",
            default => $baseFilter,
        };
    }

    /**
     * Resolve path to absolute.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
