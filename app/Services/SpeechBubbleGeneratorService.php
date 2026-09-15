<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpeechBubbleGeneratorService
{
    public const STYLE_MANGA = 'manga';

    public const STYLE_WEBTOON = 'webtoon';

    public const STYLE_CAPTION = 'caption_box';

    /**
     * Render speech bubbles onto an image and return the relative path to the rendered image.
     *
     * @param  array<int, array{speaker?: string, text: string, style?: string, position?: string, emotion?: string, color?: string}>  $bubbles
     * @return string Relative storage path to the new image with bubbles
     */
    public function renderBubblesOnImage(string $imagePath, array $bubbles, ?string $outputRelativePath = null): string
    {
        if (empty($bubbles)) {
            return $imagePath;
        }

        $imageAbsPath = $this->resolveAbsolutePath($imagePath);
        if (! File::exists($imageAbsPath)) {
            Log::warning('SpeechBubbleGeneratorService: Source image does not exist', ['path' => $imageAbsPath]);

            return $imagePath;
        }

        $outputPath = $outputRelativePath
            ? Storage::disk('public')->path($outputRelativePath)
            : Storage::disk('public')->path('stories/comic_panels/panel_bubble_'.uniqid().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        try {
            $image = $this->loadImage($imageAbsPath);
            if (! $image) {
                return $imagePath;
            }

            $imgWidth = imagesx($image);
            $imgHeight = imagesy($image);

            $fontFile = $this->getFontFile();

            foreach ($bubbles as $index => $bubble) {
                $text = trim($bubble['text'] ?? '');
                if (empty($text)) {
                    continue;
                }

                $style = $bubble['style'] ?? self::STYLE_MANGA;
                $speaker = trim($bubble['speaker'] ?? '');
                $position = $bubble['position'] ?? $this->getAutoPosition($index, count($bubbles));
                $color = $bubble['color'] ?? null;
                $xPct = isset($bubble['x_pct']) && is_numeric($bubble['x_pct']) ? (float) $bubble['x_pct'] : null;
                $yPct = isset($bubble['y_pct']) && is_numeric($bubble['y_pct']) ? (float) $bubble['y_pct'] : null;

                switch ($style) {
                    case self::STYLE_CAPTION:
                        $this->renderCaptionBox($image, $imgWidth, $imgHeight, $speaker, $text, $position, $fontFile, $xPct, $yPct);
                        break;
                    case self::STYLE_WEBTOON:
                        $this->renderWebtoonBubble($image, $imgWidth, $imgHeight, $speaker, $text, $position, $color, $fontFile, $xPct, $yPct);
                        break;
                    case self::STYLE_MANGA:
                    default:
                        $this->renderMangaBubble($image, $imgWidth, $imgHeight, $speaker, $text, $position, $fontFile, $xPct, $yPct);
                        break;
                }
            }

            imagepng($image, $outputPath, 8);
            imagedestroy($image);

            return Storage::disk('public')->path($outputPath) === $outputPath
                ? str_replace('\\', '/', str_replace(Storage::disk('public')->path(''), '', $outputPath))
                : $outputRelativePath ?? 'stories/comic_panels/'.basename($outputPath);
        } catch (Exception $e) {
            Log::error('Failed to generate speech bubbles on image', [
                'error' => $e->getMessage(),
                'image' => $imagePath,
            ]);

            return $imagePath;
        }
    }

    /**
     * Render a classic high-impact manga speech bubble (white background, bold black outline).
     */
    protected function renderMangaBubble($image, int $w, int $h, string $speaker, string $text, string $pos, ?string $font, ?float $xPct = null, ?float $yPct = null): void
    {
        $bubbleW = (int) min(max($w * 0.42, 280), $w * 0.70);
        $coords = $this->calculateCoordinates($w, $h, $bubbleW, $pos, $xPct, $yPct);
        $cx = $coords['x'];
        $cy = $coords['y'];

        // Measure text lines
        $fontSize = (int) max(14, round($w / 45));
        $lines = $this->wrapText($text, $fontSize, (int) ($bubbleW * 0.78), $font);
        $lineHeight = (int) ($fontSize * 1.35);
        $totalTextH = count($lines) * $lineHeight;
        $bubbleH = (int) max($totalTextH + 50, 90);

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 15, 15, 20);

        // Draw shadow/outer outline
        for ($thickness = 4; $thickness >= 0; $thickness--) {
            imagefilledellipse($image, $cx, $cy, $bubbleW + ($thickness * 2), $bubbleH + ($thickness * 2), $black);
        }
        imagefilledellipse($image, $cx, $cy, $bubbleW, $bubbleH, $white);

        // Draw speech bubble tail pointing down/center
        $tailX = $pos === 'top-right' ? $cx - 30 : $cx + 30;
        $tailY = $cy + (int) ($bubbleH / 2);
        $trianglePoints = [
            $tailX - 15, $tailY - 5,
            $tailX + 15, $tailY - 5,
            $tailX, $tailY + 25,
        ];
        imagefilledpolygon($image, $trianglePoints, $black);
        $innerPoints = [
            $tailX - 11, $tailY - 7,
            $tailX + 11, $tailY - 7,
            $tailX, $tailY + 20,
        ];
        imagefilledpolygon($image, $innerPoints, $white);

        // Render text
        $startY = $cy - (int) ($totalTextH / 2) + $fontSize;
        foreach ($lines as $i => $line) {
            $textY = $startY + ($i * $lineHeight);
            $this->drawCenteredText($image, $line, $cx, $textY, $fontSize, $black, $font);
        }
    }

    /**
     * Render a modern webtoon rounded bubble with character tag badge.
     */
    protected function renderWebtoonBubble($image, int $w, int $h, string $speaker, string $text, string $pos, ?string $colorHex, ?string $font, ?float $xPct = null, ?float $yPct = null): void
    {
        $bubbleW = (int) min(max($w * 0.45, 300), $w * 0.75);
        $coords = $this->calculateCoordinates($w, $h, $bubbleW, $pos, $xPct, $yPct);
        $cx = $coords['x'];
        $cy = $coords['y'];

        $fontSize = (int) max(14, round($w / 46));
        $lines = $this->wrapText($text, $fontSize, (int) ($bubbleW * 0.8), $font);
        $lineHeight = (int) ($fontSize * 1.35);
        $totalTextH = count($lines) * $lineHeight;
        $bubbleH = (int) max($totalTextH + 60, 95);

        $bg = imagecolorallocatealpha($image, 255, 255, 255, 10);
        $border = imagecolorallocate($image, 79, 70, 229); // Modern Indigo
        $textColor = imagecolorallocate($image, 24, 24, 27);

        $x1 = (int) ($cx - ($bubbleW / 2));
        $y1 = (int) ($cy - ($bubbleH / 2));
        $x2 = (int) ($cx + ($bubbleW / 2));
        $y2 = (int) ($cy + ($bubbleH / 2));

        // Draw rounded rectangle
        $this->drawFilledRoundedRect($image, $x1, $y1, $x2, $y2, 18, $border);
        $this->drawFilledRoundedRect($image, $x1 + 3, $y1 + 3, $x2 - 3, $y2 - 3, 16, $bg);

        // Render speaker badge if present
        if (! empty($speaker)) {
            $badgeBg = imagecolorallocate($image, 79, 70, 229);
            $badgeText = imagecolorallocate($image, 255, 255, 255);
            $badgeW = (int) min(strlen($speaker) * 11 + 24, $bubbleW * 0.6);
            $this->drawFilledRoundedRect($image, $x1 + 14, $y1 - 14, $x1 + 14 + $badgeW, $y1 + 10, 8, $badgeBg);
            $this->drawCenteredText($image, strtoupper($speaker), $x1 + 14 + (int) ($badgeW / 2), $y1 + 2, 11, $badgeText, $font);
        }

        $startY = $cy - (int) ($totalTextH / 2) + $fontSize + 5;
        foreach ($lines as $i => $line) {
            $textY = $startY + ($i * $lineHeight);
            $this->drawCenteredText($image, $line, $cx, $textY, $fontSize, $textColor, $font);
        }
    }

    /**
     * Render a sleek semi-transparent narration caption box (novel / cinematic style).
     */
    protected function renderCaptionBox($image, int $w, int $h, string $speaker, string $text, string $pos, ?string $font, ?float $xPct = null, ?float $yPct = null): void
    {
        $boxW = (int) ($w * 0.88);
        $fontSize = (int) max(15, round($w / 42));
        $lines = $this->wrapText($text, $fontSize, (int) ($boxW * 0.9), $font);
        $lineHeight = (int) ($fontSize * 1.4);
        $boxH = (int) (count($lines) * $lineHeight + 36);

        if ($xPct !== null && $yPct !== null) {
            $cx = (int) round(($w * max(5, min(95, $xPct))) / 100);
            $cy = (int) round(($h * max(5, min(95, $yPct))) / 100);
        } else {
            $cx = (int) ($w / 2);
            $cy = $pos === 'top' || str_contains($pos, 'top') ? (int) ($boxH / 2 + 30) : (int) ($h - ($boxH / 2) - 40);
        }

        $x1 = (int) ($cx - ($boxW / 2));
        $y1 = (int) ($cy - ($boxH / 2));
        $x2 = (int) ($cx + ($boxW / 2));
        $y2 = (int) ($cy + ($boxH / 2));

        $blackAlpha = imagecolorallocatealpha($image, 10, 10, 15, 25);
        $goldBorder = imagecolorallocate($image, 234, 179, 8); // Gold accent
        $white = imagecolorallocate($image, 255, 255, 255);

        // Frame
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $blackAlpha);
        imagerectangle($image, $x1, $y1, $x2, $y2, $goldBorder);
        imagerectangle($image, $x1 + 2, $y1 + 2, $x2 - 2, $y2 - 2, $goldBorder);

        $startY = $y1 + $fontSize + 18;
        foreach ($lines as $i => $line) {
            $textY = $startY + ($i * $lineHeight);
            $this->drawCenteredText($image, $line, $cx, $textY, $fontSize, $white, $font);
        }
    }

    /**
     * Calculate bounding center coordinates for bubble positioning.
     *
     * @return array{x: int, y: int}
     */
    protected function calculateCoordinates(int $w, int $h, int $bw, string $pos, ?float $xPct = null, ?float $yPct = null): array
    {
        if ($xPct !== null && $yPct !== null) {
            $x = (int) round(($w * max(5, min(95, $xPct))) / 100);
            $y = (int) round(($h * max(5, min(95, $yPct))) / 100);

            return ['x' => $x, 'y' => $y];
        }

        return match ($pos) {
            'top-left' => ['x' => (int) ($bw / 2 + 30), 'y' => (int) ($h * 0.18)],
            'top-right' => ['x' => (int) ($w - ($bw / 2) - 30), 'y' => (int) ($h * 0.18)],
            'bottom-left' => ['x' => (int) ($bw / 2 + 30), 'y' => (int) ($h * 0.78)],
            'bottom-right' => ['x' => (int) ($w - ($bw / 2) - 30), 'y' => (int) ($h * 0.78)],
            'center' => ['x' => (int) ($w / 2), 'y' => (int) ($h / 2)],
            default => ['x' => (int) ($w - ($bw / 2) - 30), 'y' => (int) ($h * 0.20)],
        };
    }

    protected function getAutoPosition(int $index, int $total): string
    {
        $positions = ['top-right', 'top-left', 'bottom-right', 'bottom-left'];

        return $positions[$index % count($positions)];
    }

    /**
     * Draw text centered horizontally around cx.
     */
    protected function drawCenteredText($image, string $text, int $cx, int $y, int $fontSize, int $color, ?string $font): void
    {
        if ($font && function_exists('imagettfbbox')) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $textW = abs($bbox[4] - $bbox[0]);
            $x = (int) ($cx - ($textW / 2));
            imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);
        } else {
            // Built-in GD font fallback
            $gdFont = 5;
            $textW = imagefontwidth($gdFont) * strlen($text);
            $x = (int) ($cx - ($textW / 2));
            imagestring($image, $gdFont, $x, $y - 12, $text, $color);
        }
    }

    /**
     * Simple text wrapper for bubble widths.
     *
     * @return array<int, string>
     */
    protected function wrapText(string $text, int $fontSize, int $maxWidth, ?string $font): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine ? "{$currentLine} {$word}" : $word;

            if ($font && function_exists('imagettfbbox')) {
                $bbox = imagettfbbox($fontSize, 0, $font, $testLine);
                $w = abs($bbox[4] - $bbox[0]);
            } else {
                $w = strlen($testLine) * 9;
            }

            if ($w > $maxWidth && ! empty($currentLine)) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if (! empty($currentLine)) {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Draw filled rounded rectangle.
     */
    protected function drawFilledRoundedRect($image, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($image, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($image, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    /**
     * Load image from file (supporting JPEG, PNG, WEBP).
     */
    protected function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    /**
     * Resolve system true type font file if available.
     */
    protected function getFontFile(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/seguiemj.ttf',
            'C:/Windows/Fonts/calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
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
