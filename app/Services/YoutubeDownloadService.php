<?php

namespace App\Services;

use Exception;
use Symfony\Component\Process\Process;

class YoutubeDownloadService
{
    /**
     * Get the title of the video using yt-dlp.
     */
    public function getTitle(string $url): ?string
    {
        $process = new Process(['yt-dlp', '--get-title', $url]);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return null;
    }

    /**
     * Download the video using yt-dlp to the specified output path.
     */
    public function download(string $url, string $outputPath, int $timeout = 900): void
    {
        $command = [
            'yt-dlp',
            '-f', 'bestvideo[height<=720][ext=mp4]+bestaudio[ext=m4a]/best[height<=720][ext=mp4]/best[height<=720]/best',
        ];

        // Explicitly set FFmpeg binary location on Windows
        $ffmpegLocation = 'C:/ffmpeg/bin/ffmpeg.exe';
        if (file_exists($ffmpegLocation)) {
            $command[] = '--ffmpeg-location';
            $command[] = $ffmpegLocation;
        }

        $command[] = '-o';
        $command[] = $outputPath;
        $command[] = $url;

        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new Exception('yt-dlp failed to download video: '.$process->getErrorOutput());
        }
    }
}
