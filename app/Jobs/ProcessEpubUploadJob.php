<?php

namespace App\Jobs;

use App\Models\EpubProject;
use App\Models\EpubScene;
use App\Services\EpubExtractorService;
use App\Services\HtmlExtractorService;
use App\Services\TranslationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ProcessEpubUploadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public EpubProject $project) {}

    public function handle(
        ?EpubExtractorService $epubExtractor = null,
        ?HtmlExtractorService $htmlExtractor = null,
        ?TranslationService $translationService = null
    ): void {
        $epubExtractor = $epubExtractor ?? app(EpubExtractorService::class);
        $htmlExtractor = $htmlExtractor ?? app(HtmlExtractorService::class);
        $translationService = $translationService ?? app(TranslationService::class);

        $this->project->update(['status' => 'parsing', 'error_message' => null]);

        try {
            $fileAbsPath = Storage::disk('public')->path($this->project->epub_path);
            $ext = strtolower(pathinfo($this->project->epub_path, PATHINFO_EXTENSION));

            if (in_array($ext, ['html', 'htm', 'xhtml'])) {
                $extractedScenes = $htmlExtractor->extractScenesFromHtml($fileAbsPath, $this->project->id);
            } elseif ($ext === 'zip') {
                $zip = new ZipArchive;
                $isEpub = false;
                if ($zip->open($fileAbsPath) === true) {
                    if ($zip->locateName('META-INF/container.xml') !== false || $zip->locateName('mimetype') !== false) {
                        $isEpub = true;
                    }
                    $zip->close();
                }

                if ($isEpub) {
                    $extractedScenes = $epubExtractor->extractScenesFromEpub($fileAbsPath, $this->project->id);
                } else {
                    $extractedScenes = $htmlExtractor->extractScenesFromHtml($fileAbsPath, $this->project->id);
                }
            } else {
                $extractedScenes = $epubExtractor->extractScenesFromEpub($fileAbsPath, $this->project->id);
            }

            if (empty($extractedScenes)) {
                throw new Exception('No readable scenes or paragraphs found in the uploaded document.');
            }

            // Remove existing scenes if reprocessing
            $this->project->scenes()->delete();

            $sourceLang = $this->project->source_language ?: 'auto';
            $targetLang = $this->project->target_language ?: 'en';
            $shouldTranslate = ($targetLang !== '') && ($sourceLang === 'auto' || $sourceLang !== $targetLang);

            $createdScenes = [];
            foreach ($extractedScenes as $sceneData) {
                $rawText = $sceneData['script_text'];
                $finalText = $rawText;

                if ($shouldTranslate) {
                    $finalText = $translationService->translate($rawText, $targetLang, $sourceLang);
                }

                $scene = EpubScene::create([
                    'epub_project_id' => $this->project->id,
                    'sequence_order' => $sceneData['sequence_order'],
                    'original_image_path' => $sceneData['image_path'],
                    'image_path' => $sceneData['image_path'],
                    'original_script_text' => $rawText,
                    'script_text' => $finalText,
                    'status' => 'pending',
                ]);
                $createdScenes[] = $scene;
            }

            $this->project->update([
                'status' => 'generating_audio',
                'total_scenes' => count($createdScenes),
                'rendered_scenes' => 0,
            ]);

            // Dispatch GenerateSceneAudioJob for each created scene
            foreach ($createdScenes as $scene) {
                GenerateSceneAudioJob::dispatch($scene);
            }
        } catch (Exception $e) {
            Log::error('ProcessEpubUploadJob failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            $this->project->update([
                'status' => 'failed',
                'error_message' => 'EPUB Parsing failed: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->project->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Job failed due to unexpected queue error',
        ]);
    }
}
