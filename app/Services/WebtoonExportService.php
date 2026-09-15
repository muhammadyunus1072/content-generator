<?php

namespace App\Services;

use App\Models\Episode;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WebtoonExportService
{
    /**
     * Export complete Webtoon Canvas and Tapas publishing pack into a ZIP archive.
     *
     * @return string Relative public storage path to the exported ZIP
     */
    public function exportPublishingPack(Episode $episode): string
    {
        $slices = $episode->webtoon_slices;

        if (empty($slices) || ! is_array($slices)) {
            // Auto-generate if not yet generated
            $webtoonService = app(WebtoonStripService::class);
            $genResult = $webtoonService->generateAndSliceEpisode($episode);
            $slices = $genResult['slices'];
        }

        if (empty($slices)) {
            throw new Exception('No slices available to export.');
        }

        $zipRelative = "stories/webtoons/exports/episode_{$episode->id}_publishing_pack.zip";
        $zipAbsPath = Storage::disk('public')->path($zipRelative);
        File::ensureDirectoryExists(dirname($zipAbsPath));

        if (File::exists($zipAbsPath)) {
            File::delete($zipAbsPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipAbsPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Could not open ZIP archive at: {$zipAbsPath}");
        }

        // 1. Add Webtoon Canvas Slices
        foreach ($slices as $idx => $sliceRel) {
            $sliceAbs = Storage::disk('public')->path($sliceRel);
            if (File::exists($sliceAbs)) {
                $padded = str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
                $zip->addFile($sliceAbs, "webtoon_canvas/slice_{$padded}.jpg");
                $zip->addFile($sliceAbs, "tapas/slice_{$padded}.jpg");
            }
        }

        // 2. Generate and add square thumbnails (436x436 for Webtoon Canvas, 300x300 for Tapas)
        $firstSlice = $slices[0] ?? null;
        if ($firstSlice) {
            $firstSliceAbs = Storage::disk('public')->path($firstSlice);
            if (File::exists($firstSliceAbs)) {
                $thumb436 = $this->generateSquareThumb($firstSliceAbs, 436);
                if ($thumb436 && File::exists($thumb436)) {
                    $zip->addFile($thumb436, 'webtoon_canvas/thumbnail_436x436.jpg');
                }

                $thumb300 = $this->generateSquareThumb($firstSliceAbs, 300);
                if ($thumb300 && File::exists($thumb300)) {
                    $zip->addFile($thumb300, 'tapas/thumbnail_300x300.jpg');
                }
            }
        }

        // 3. Add Metadata JSON
        $metadata = [
            'series_title' => $episode->story?->title ?: 'Webtoon Series',
            'episode_number' => $episode->episode_number,
            'episode_title' => $episode->title,
            'synopsis' => $episode->synopsis,
            'total_slices' => count($slices),
            'generated_at' => now()->toIso8601String(),
            'platform_compliance' => [
                'webtoon_canvas' => 'Width 800px, max slice height 1280px, <2MB per image',
                'tapas' => 'Width up to 960px, max slice height 1440px',
            ],
        ];

        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
        $zip->close();

        $episode->update([
            'webtoon_export_pack_path' => $zipRelative,
        ]);

        return $zipRelative;
    }

    /**
     * Generate a square thumbnail cropped from the top of an image.
     */
    protected function generateSquareThumb(string $sourcePath, int $size): ?string
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            default => null,
        };

        if (! $src) {
            return null;
        }

        $w = $info[0];
        $h = $info[1];
        $cropSize = min($w, $h);

        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $size, $size, $cropSize, $cropSize);
        imagedestroy($src);

        $tempPath = storage_path("app/temp/thumb_{$size}_".uniqid().'.jpg');
        File::ensureDirectoryExists(dirname($tempPath));
        imagejpeg($thumb, $tempPath, 90);
        imagedestroy($thumb);

        return $tempPath;
    }
}
