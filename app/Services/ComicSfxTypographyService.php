<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComicSfxTypographyService
{
    public const STYLE_ACTION_SPEED = 'action_speed';

    public const STYLE_BLADE_SLASH = 'blade_slash';

    public const STYLE_IMPACT_QUAKE = 'impact_quake';

    public const STYLE_EERIE_WHISPER = 'eerie_whisper';

    /**
     * Map common SFX tags to dynamic comic sound effect words.
     */
    public const SFX_WORD_SUGGESTIONS = [
        'slash' => 'SLASH!!',
        'sword_draw' => 'SHHHK!',
        'explosion' => 'BOOOOM!!',
        'punch' => 'BAM!!',
        'heartbeat' => 'DOKI... DOKI...',
        'thunder' => 'KRA-KABOOM!!',
        'magic_cast' => 'VOOOM!',
        'footsteps' => 'THUD... THUD...',
        'wind' => 'FSHHH...',
        'rain' => 'SSSHHH...',
    ];

    /**
     * Suggest comic SFX word, style, color, and angle based on text prompt or action.
     *
     * @return array{text: string, style: string, color: string, rotation: int}
     */
    public function suggestSfxForAction(string $description): array
    {
        $desc = strtolower($description);

        if (str_contains($desc, 'explosion') || str_contains($desc, 'blast') || str_contains($desc, 'boom')) {
            return [
                'text' => 'BOOOOOM!!',
                'style' => self::STYLE_ACTION_SPEED,
                'color' => '#EF4444',
                'rotation' => -15,
            ];
        }

        if (str_contains($desc, 'sword') || str_contains($desc, 'slash') || str_contains($desc, 'cut') || str_contains($desc, 'blade')) {
            return [
                'text' => 'SHHIIING!',
                'style' => self::STYLE_BLADE_SLASH,
                'color' => '#38BDF8',
                'rotation' => 20,
            ];
        }

        if (str_contains($desc, 'earthquake') || str_contains($desc, 'quake') || str_contains($desc, 'stomp') || str_contains($desc, 'crash')) {
            return [
                'text' => 'DOOOOM!!',
                'style' => self::STYLE_IMPACT_QUAKE,
                'color' => '#F59E0B',
                'rotation' => -5,
            ];
        }

        if (str_contains($desc, 'whisper') || str_contains($desc, 'shadow') || str_contains($desc, 'creepy') || str_contains($desc, 'dread')) {
            return [
                'text' => 'ZZZZZZT...',
                'style' => self::STYLE_EERIE_WHISPER,
                'color' => '#A855F7',
                'rotation' => 5,
            ];
        }

        return [
            'text' => 'WHOOSH!!',
            'style' => self::STYLE_ACTION_SPEED,
            'color' => '#E11D48',
            'rotation' => -10,
        ];
    }

    /**
     * Render SFX typography elements onto an image.
     *
     * @param  array<int, array{text: string, style?: string, color?: string, rotation?: int, position?: string, size?: int}>  $sfxList
     * @return string Relative public storage path
     */
    public function renderSfxOnImage(string $imagePath, array $sfxList, ?string $outputRelativePath = null): string
    {
        if (empty($sfxList)) {
            return $imagePath;
        }

        $imageAbsPath = $this->resolveAbsolutePath($imagePath);
        if (! File::exists($imageAbsPath)) {
            Log::warning('ComicSfxTypographyService: Source image missing', ['path' => $imageAbsPath]);

            return $imagePath;
        }

        $outputPath = $outputRelativePath
            ? Storage::disk('public')->path($outputRelativePath)
            : Storage::disk('public')->path('stories/comic_panels/panel_sfx_'.uniqid().'.png');

        File::ensureDirectoryExists(dirname($outputPath));

        try {
            $image = $this->loadImage($imageAbsPath);
            if (! $image) {
                return $imagePath;
            }

            $imgW = imagesx($image);
            $imgH = imagesy($image);
            $fontFile = $this->getFontFile();

            foreach ($sfxList as $item) {
                $text = strtoupper(trim($item['text'] ?? ''));
                if ($text === '') {
                    continue;
                }

                $style = $item['style'] ?? self::STYLE_ACTION_SPEED;
                $colorHex = $item['color'] ?? $this->getDefaultColorForStyle($style);
                $rotation = (int) ($item['rotation'] ?? -12);
                $fontSize = (int) ($item['size'] ?? 42);
                $coords = $this->resolveCoordinates($imgW, $imgH, $item);

                $this->drawStylizedSfx(
                    $image,
                    $text,
                    $coords['x'],
                    $coords['y'],
                    $fontSize,
                    $rotation,
                    $colorHex,
                    $style,
                    $fontFile
                );
            }

            imagepng($image, $outputPath, 8);
            imagedestroy($image);

            return Storage::disk('public')->path($outputPath) === $outputPath
                ? str_replace('\\', '/', str_replace(Storage::disk('public')->path(''), '', $outputPath))
                : $outputRelativePath ?? 'stories/comic_panels/'.basename($outputPath);
        } catch (Exception $e) {
            Log::error('Failed to render comic SFX typography', [
                'error' => $e->getMessage(),
                'image' => $imagePath,
            ]);

            return $imagePath;
        }
    }

    /**
     * Draw stylized comic SFX with multi-layered high-contrast outlines and dynamic colors.
     */
    protected function drawStylizedSfx(
        $image,
        string $text,
        int $x,
        int $y,
        int $fontSize,
        int $angle,
        string $hexColor,
        string $style,
        ?string $font
    ): void {
        $rgb = $this->hexToRgb($hexColor);
        $fillColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
        $black = imagecolorallocate($image, 10, 10, 15);
        $white = imagecolorallocate($image, 255, 255, 255);

        if ($font && function_exists('imagettftext')) {
            // Draw thick outer outline by rendering surrounding offsets
            $outlineThickness = max(3, round($fontSize / 8));

            for ($ox = -$outlineThickness; $ox <= $outlineThickness; $ox++) {
                for ($oy = -$outlineThickness; $oy <= $outlineThickness; $oy++) {
                    if ($ox === 0 && $oy === 0) {
                        continue;
                    }
                    imagettftext($image, $fontSize, $angle, $x + $ox, $y + $oy, $black, $font, $text);
                }
            }

            // Inner contrasting outline for action styles
            if ($style === self::STYLE_ACTION_SPEED || $style === self::STYLE_BLADE_SLASH) {
                imagettftext($image, $fontSize, $angle, $x - 1, $y - 1, $white, $font, $text);
                imagettftext($image, $fontSize, $angle, $x + 1, $y + 1, $white, $font, $text);
            }

            // Draw primary text fill
            imagettftext($image, $fontSize, $angle, $x, $y, $fillColor, $font, $text);
        } else {
            // Fallback: GD built-in font
            $gdFont = 5;
            imagestring($image, $gdFont, $x - 2, $y - 2, $text, $black);
            imagestring($image, $gdFont, $x + 2, $y + 2, $text, $black);
            imagestring($image, $gdFont, $x, $y, $text, $fillColor);
        }
    }

    /**
     * Auto-suggest SFX typography elements based on an array of SFX tags.
     *
     * @param  array<int, string>  $sfxTags
     * @return array<int, array{text: string, style: string, color: string, rotation: int, position: string}>
     */
    public function suggestSfxTypography(array $sfxTags): array
    {
        $suggestions = [];
        $positions = ['top-right', 'center', 'bottom-left'];

        foreach ($sfxTags as $idx => $tag) {
            $tagLower = strtolower(trim($tag));
            $word = self::SFX_WORD_SUGGESTIONS[$tagLower] ?? strtoupper($tagLower).'!!';
            $style = str_contains($tagLower, 'slash') || str_contains($tagLower, 'sword')
                ? self::STYLE_BLADE_SLASH
                : (str_contains($tagLower, 'heart') || str_contains($tagLower, 'wind') ? self::STYLE_EERIE_WHISPER : self::STYLE_ACTION_SPEED);

            $suggestions[] = [
                'text' => $word,
                'style' => $style,
                'color' => $this->getDefaultColorForStyle($style),
                'rotation' => ($idx % 2 === 0) ? -14 : 12,
                'position' => $positions[$idx % count($positions)],
            ];
        }

        return $suggestions;
    }

    /**
     * Resolve pixel coordinates from percentage coords or preset positions.
     *
     * @param  array{x_pct?: int|float|string, y_pct?: int|float|string, x?: int|float|string, y?: int|float|string, position?: string}  $item
     * @return array{x: int, y: int}
     */
    public function resolveCoordinates(int $imgW, int $imgH, array $item): array
    {
        if (isset($item['x_pct']) || isset($item['x'])) {
            $rawX = $item['x_pct'] ?? $item['x'];
            $rawY = $item['y_pct'] ?? $item['y'];
            $xVal = is_string($rawX) && str_ends_with($rawX, '%') ? (float) rtrim($rawX, '%') : (float) $rawX;
            $yVal = is_string($rawY) && str_ends_with($rawY, '%') ? (float) rtrim($rawY, '%') : (float) $rawY;

            return [
                'x' => (int) round(($imgW * max(5, min(95, $xVal))) / 100),
                'y' => (int) round(($imgH * max(5, min(95, $yVal))) / 100),
            ];
        }

        return $this->calculateCoordinates($imgW, $imgH, $item['position'] ?? 'center');
    }

    protected function calculateCoordinates(int $w, int $h, string $pos): array
    {
        return match ($pos) {
            'top-left' => ['x' => (int) ($w * 0.15), 'y' => (int) ($h * 0.22)],
            'top-right' => ['x' => (int) ($w * 0.55), 'y' => (int) ($h * 0.22)],
            'bottom-left' => ['x' => (int) ($w * 0.15), 'y' => (int) ($h * 0.82)],
            'bottom-right' => ['x' => (int) ($w * 0.55), 'y' => (int) ($h * 0.82)],
            'center' => ['x' => (int) ($w * 0.35), 'y' => (int) ($h * 0.52)],
            default => ['x' => (int) ($w * 0.40), 'y' => (int) ($h * 0.48)],
        };
    }

    protected function getDefaultColorForStyle(string $style): string
    {
        return match ($style) {
            self::STYLE_BLADE_SLASH => '#F59E0B', // Vibrant Amber/Gold
            self::STYLE_IMPACT_QUAKE => '#EF4444', // Fiery Crimson
            self::STYLE_EERIE_WHISPER => '#A855F7', // Ghostly Purple
            self::STYLE_ACTION_SPEED => '#E11D48', // High-Impact Rose
            default => '#EF4444',
        };
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)) ?: 239,
            'g' => hexdec(substr($hex, 2, 2)) ?: 68,
            'b' => hexdec(substr($hex, 4, 2)) ?: 68,
        ];
    }

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

    protected function getFontFile(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/impact.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
