<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ThumbnailGeneratorService
{
    /**
     * Generate an eye-catching YouTube or TikTok thumbnail from a source image.
     *
     * @param  string  $sourceImagePath  Absolute or public storage relative path
     * @param  string  $overlayText  Punchy 2-5 word thumbnail text (e.g. "HE AWAKENED?!")
     * @param  string  $niche  Niche key ('manhwa_recap', 'kids_story', 'science_explainer', 'horror_narration', 'book_summary')
     * @param  string  $aspectRatio  '16:9' (1280x720) or '9:16' (720x1280)
     * @return string Relative public storage path to the generated thumbnail
     */
    public function generate(
        string $sourceImagePath,
        string $overlayText = '',
        string $niche = 'manhwa_recap',
        string $aspectRatio = '16:9'
    ): string {
        $sourcePath = $this->resolvePath($sourceImagePath);

        if (! File::exists($sourcePath)) {
            throw new Exception("Source image does not exist at: {$sourcePath}");
        }

        $width = $aspectRatio === '9:16' ? 720 : 1280;
        $height = $aspectRatio === '9:16' ? 1280 : 720;

        $filename = 'thumb_'.uniqid().'_'.time().'.jpg';
        $relativeOutputPath = 'thumbnails/'.$filename;
        $absoluteOutputPath = Storage::disk('public')->path($relativeOutputPath);

        File::ensureDirectoryExists(dirname($absoluteOutputPath));

        // Attempt FFmpeg generation first
        $ffmpegSuccess = $this->renderWithFfmpeg(
            $sourcePath,
            $absoluteOutputPath,
            $overlayText,
            $niche,
            $width,
            $height
        );

        if ($ffmpegSuccess && File::exists($absoluteOutputPath) && filesize($absoluteOutputPath) > 0) {
            return $relativeOutputPath;
        }

        // Fallback to PHP GD if FFmpeg failed
        $this->renderWithGd(
            $sourcePath,
            $absoluteOutputPath,
            $overlayText,
            $niche,
            $width,
            $height
        );

        return $relativeOutputPath;
    }

    /**
     * Render thumbnail using FFmpeg with color-grading filters and text overlays.
     */
    protected function renderWithFfmpeg(
        string $sourcePath,
        string $outputPath,
        string $overlayText,
        string $niche,
        int $width,
        int $height
    ): bool {
        $ffmpegBin = env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe');
        if (! file_exists($ffmpegBin)) {
            $ffmpegBin = 'ffmpeg';
        }

        // Color grading filters based on niche
        $colorFilter = match ($niche) {
            'manhwa_recap' => 'eq=contrast=1.3:brightness=0.03:saturation=1.4',
            'kids_story' => 'eq=contrast=1.1:brightness=0.06:saturation=1.3',
            'science_explainer' => 'eq=contrast=1.25:brightness=-0.02:saturation=1.2,colorbalance=rs=-0.05:bs=0.1',
            'horror_narration' => 'eq=contrast=1.4:brightness=-0.08:saturation=0.5,vignette=PI/4',
            'book_summary' => 'eq=contrast=1.2:brightness=0.0:saturation=0.9',
            default => 'eq=contrast=1.2:saturation=1.2',
        };

        // Base scale & crop filter
        $filterParts = [
            "scale={$width}:{$height}:force_original_aspect_ratio=increase",
            "crop={$width}:{$height}",
            $colorFilter,
        ];

        // Add subtle bottom gradient banner for text readability if text is provided
        if (! empty($overlayText)) {
            $safeText = addslashes(strtoupper($overlayText));
            $safeText = str_replace([':', "'", '%'], ['\\:', "\\'", '\\%'], $safeText);

            $fontSize = $height > 1000 ? 56 : 48;
            $drawText = "drawtext=text='{$safeText}':fontcolor=white:fontsize={$fontSize}:borderw=4:bordercolor=black@0.9:box=1:boxcolor=black@0.65:boxborderw=18:x=(w-text_w)/2:y=h-th-80";
            $filterParts[] = $drawText;
        }

        $filterComplex = implode(',', $filterParts);

        $command = [
            $ffmpegBin,
            '-y',
            '-i',
            $sourcePath,
            '-vf',
            $filterComplex,
            '-vframes',
            '1',
            '-q:v',
            '2',
            $outputPath,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            return $process->isSuccessful();
        } catch (Exception $e) {
            Log::warning('ThumbnailGeneratorService FFmpeg run failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Fallback rendering with GD in case FFmpeg is unavailable.
     */
    protected function renderWithGd(
        string $sourcePath,
        string $outputPath,
        string $overlayText,
        string $niche,
        int $width,
        int $height
    ): void {
        if (! extension_loaded('gd')) {
            // If GD is also unavailable, simply copy the file
            File::copy($sourcePath, $outputPath);

            return;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (! $imageInfo) {
            File::copy($sourcePath, $outputPath);

            return;
        }

        $srcImg = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (! $srcImg) {
            File::copy($sourcePath, $outputPath);

            return;
        }

        $dstImg = imagecreatetruecolor($width, $height);
        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        // Resize with crop to fill
        $srcRatio = $srcW / $srcH;
        $dstRatio = $width / $height;

        if ($srcRatio > $dstRatio) {
            $newH = $height;
            $newW = (int) ($height * $srcRatio);
            $cropX = (int) (($newW - $width) / 2);
            $cropY = 0;
        } else {
            $newW = $width;
            $newH = (int) ($width / $srcRatio);
            $cropX = 0;
            $cropY = (int) (($newH - $height) / 2);
        }

        $tempResized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($tempResized, $srcImg, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        imagecopy($dstImg, $tempResized, 0, 0, $cropX, $cropY, $width, $height);

        imagedestroy($tempResized);
        imagedestroy($srcImg);

        // Apply contrast
        imagefilter($dstImg, IMG_FILTER_CONTRAST, -15);

        // Output JPEG
        imagejpeg($dstImg, $outputPath, 90);
        imagedestroy($dstImg);
    }

    /**
     * Resolve image path from relative storage disk or absolute path.
     */
    protected function resolvePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        $diskPath = Storage::disk('public')->path($path);
        if (File::exists($diskPath)) {
            return $diskPath;
        }

        return $path;
    }
}
