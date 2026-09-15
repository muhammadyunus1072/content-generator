<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class HtmlExtractorService
{
    /**
     * Extract structured scene pairs (text paragraphs + images) from an HTML file or HTML ZIP package.
     *
     * @return array<int, array{sequence_order: int, image_path: string|null, script_text: string}>
     */
    public function extractScenesFromHtml(string $filePath, int $projectId): array
    {
        if (! File::exists($filePath)) {
            throw new Exception("File does not exist at: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            return $this->extractFromHtmlZip($filePath, $projectId);
        }

        $htmlContent = File::get($filePath);

        return $this->parseHtmlContent($htmlContent, $projectId, dirname($filePath));
    }

    /**
     * Extract and parse an HTML file packaged in a ZIP archive with image assets.
     *
     * @return array<int, array{sequence_order: int, image_path: string|null, script_text: string}>
     */
    protected function extractFromHtmlZip(string $zipPath, int $projectId): array
    {
        $zip = new ZipArchive;
        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            throw new Exception("Failed to open ZIP archive (Error code: {$openResult})");
        }

        $tempExtractDir = storage_path("app/temp/html_zip_{$projectId}_".uniqid());
        File::ensureDirectoryExists($tempExtractDir);

        try {
            $zip->extractTo($tempExtractDir);
            $zip->close();

            // Find primary HTML file (e.g. index.html, main.html, or first .html/.htm/.xhtml file found)
            $allHtml = File::glob($tempExtractDir.'/**/*.{html,htm,xhtml}', GLOB_BRACE);

            if (empty($allHtml)) {
                $allHtml = File::glob($tempExtractDir.'/*.{html,htm,xhtml}', GLOB_BRACE);
            }

            if (empty($allHtml)) {
                throw new Exception('No HTML file (.html, .htm, .xhtml) found in the ZIP archive.');
            }

            // Prefer index.html or book.html if present
            $primaryHtmlPath = $allHtml[0];
            foreach ($allHtml as $hPath) {
                $basename = strtolower(basename($hPath));
                if (in_array($basename, ['index.html', 'index.htm', 'book.html', 'main.html', 'story.html'])) {
                    $primaryHtmlPath = $hPath;
                    break;
                }
            }

            $htmlContent = File::get($primaryHtmlPath);

            return $this->parseHtmlContent($htmlContent, $projectId, dirname($primaryHtmlPath), $tempExtractDir);
        } finally {
            if (File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
        }
    }

    /**
     * Parse HTML string, extract embedded or referenced images, and build scene pairs.
     *
     * @return array<int, array{sequence_order: int, image_path: string|null, script_text: string}>
     */
    public function parseHtmlContent(string $htmlContent, int $projectId, ?string $baseDir = null, ?string $zipExtractDir = null): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Ensure public storage directory for project images exists
        $publicImageDir = "epub_projects/{$projectId}/images";
        $publicImageAbsDir = Storage::disk('public')->path($publicImageDir);
        File::ensureDirectoryExists($publicImageAbsDir);

        $scenes = [];
        $sequenceOrder = 1;
        $currentContextImage = null;

        // Query all relevant content elements in document order
        $nodes = $xpath->query('//body//*[self::p or self::h1 or self::h2 or self::h3 or self::h4 or self::img or self::image or self::figure or self::blockquote or self::li]');

        if ($nodes && $nodes->length > 0) {
            foreach ($nodes as $node) {
                if (! ($node instanceof DOMElement)) {
                    continue;
                }

                $tagName = strtolower($node->tagName);

                if ($tagName === 'img' || $tagName === 'image') {
                    $src = $node->getAttribute('src') ?: $node->getAttribute('xlink:href');
                    $savedImage = $this->processAndStoreImage($src, $publicImageDir, $baseDir, $zipExtractDir);
                    if ($savedImage) {
                        $currentContextImage = $savedImage;
                    }
                } elseif ($tagName === 'figure') {
                    // Check if figure contains an image
                    $figureImgs = $node->getElementsByTagName('img');
                    if ($figureImgs->length > 0) {
                        $src = $figureImgs->item(0)?->getAttribute('src');
                        $savedImage = $this->processAndStoreImage($src, $publicImageDir, $baseDir, $zipExtractDir);
                        if ($savedImage) {
                            $currentContextImage = $savedImage;
                        }
                    }
                } elseif (in_array($tagName, ['p', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'li'])) {
                    // Also check if paragraph has an inline img tag
                    $inlineImgs = $node->getElementsByTagName('img');
                    if ($inlineImgs->length > 0) {
                        $src = $inlineImgs->item(0)?->getAttribute('src');
                        $savedImage = $this->processAndStoreImage($src, $publicImageDir, $baseDir, $zipExtractDir);
                        if ($savedImage) {
                            $currentContextImage = $savedImage;
                        }
                    }

                    $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                    if (strlen($text) >= 15) {
                        $scenes[] = [
                            'sequence_order' => $sequenceOrder++,
                            'image_path' => $currentContextImage,
                            'script_text' => $text,
                        ];
                    }
                }
            }
        }

        // Fallback: If xpath matched nothing, extract via regex
        if (empty($scenes)) {
            preg_match_all('/<(?:p|h1|h2|h3)[^>]*>(.*?)<\/(?:p|h1|h2|h3)>/is', $htmlContent, $matches);
            if (! empty($matches[1])) {
                foreach ($matches[1] as $rawText) {
                    $text = trim(strip_tags($rawText));
                    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
                    if (strlen($text) >= 15) {
                        $scenes[] = [
                            'sequence_order' => $sequenceOrder++,
                            'image_path' => $currentContextImage,
                            'script_text' => $text,
                        ];
                    }
                }
            }
        }

        return $scenes;
    }

    /**
     * Process image from src attribute (Base64 data URI, Remote HTTP URL, or local relative path).
     */
    protected function processAndStoreImage(
        ?string $src,
        string $publicImageDir,
        ?string $baseDir = null,
        ?string $zipExtractDir = null
    ): ?string {
        if (empty($src)) {
            return null;
        }

        $src = trim($src);

        // Case 1: Base64 Data URI (e.g. data:image/png;base64,iVBORw0KGgo...)
        if (str_starts_with($src, 'data:image/')) {
            if (preg_match('/^data:image\/([a-zA-Z0-9\+\-]+);base64,(.+)$/', $src, $b64Matches)) {
                $ext = strtolower($b64Matches[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
                $decoded = base64_decode($b64Matches[2]);
                if ($decoded !== false && strlen($decoded) > 10) {
                    $filename = 'img_b64_'.uniqid().'.'.$ext;
                    $relPath = "{$publicImageDir}/{$filename}";
                    $absPath = Storage::disk('public')->path($relPath);
                    File::ensureDirectoryExists(dirname($absPath));
                    File::put($absPath, $decoded);

                    return $relPath;
                }
            }

            return null;
        }

        // Case 2: Remote HTTP/HTTPS URL
        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            try {
                $response = Http::timeout(10)->get($src);
                if ($response->successful() && strlen($response->body()) > 10) {
                    $pathInfo = pathinfo(parse_url($src, PHP_URL_PATH) ?? 'image.jpg');
                    $ext = ! empty($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'jpg';
                    $filename = 'img_remote_'.uniqid().'.'.$ext;
                    $relPath = "{$publicImageDir}/{$filename}";
                    $absPath = Storage::disk('public')->path($relPath);
                    File::ensureDirectoryExists(dirname($absPath));
                    File::put($absPath, $response->body());

                    return $relPath;
                }
            } catch (Exception $e) {
                Log::warning('Failed to download remote HTML image', ['url' => $src, 'error' => $e->getMessage()]);
            }

            return null;
        }

        // Case 3: Local relative file path (from unzipped archive or relative baseDir)
        $searchPaths = [];
        if ($baseDir) {
            $searchPaths[] = $baseDir.'/'.ltrim($src, '/\\');
            $searchPaths[] = $baseDir.'/'.basename($src);
        }
        if ($zipExtractDir) {
            $searchPaths[] = $zipExtractDir.'/'.ltrim($src, '/\\');
            $searchPaths[] = $zipExtractDir.'/images/'.basename($src);
            $searchPaths[] = $zipExtractDir.'/assets/'.basename($src);
        }

        foreach ($searchPaths as $potentialPath) {
            if (File::exists($potentialPath) && ! File::isDirectory($potentialPath)) {
                $ext = pathinfo($potentialPath, PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'img_'.uniqid().'_'.basename($potentialPath);
                $relPath = "{$publicImageDir}/{$filename}";
                $absPath = Storage::disk('public')->path($relPath);
                File::ensureDirectoryExists(dirname($absPath));
                File::copy($potentialPath, $absPath);

                return $relPath;
            }
        }

        return null;
    }
}
