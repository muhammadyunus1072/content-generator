<?php

namespace App\Jobs;

use App\Models\Character;
use App\Models\SceneBoard;
use App\Services\ImageGenerationService;
use App\Services\PromptCompilerService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateBoardImageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public SceneBoard $board) {}

    public function handle(ImageGenerationService $imageService): void
    {
        $this->board->update(['status' => 'generating_image']);

        try {
            $story = $this->board->episode?->story;
            $contentTitle = $story?->contentTitle;
            $provider = $contentTitle?->image_connection_id ?: ($contentTitle?->image_provider ?? ($story ? $story->image_provider : 'cloudflare'));
            $aspectRatio = $story ? $story->aspect_ratio : '16:9';

            $relativePath = 'stories/boards/board_'.$this->board->id.'_'.time().'.png';
            $outputAbsolutePath = Storage::disk('public')->path($relativePath);
            File::ensureDirectoryExists(dirname($outputAbsolutePath));

            // Resolve all bound Reference Sheet images
            $referenceImagePaths = [];

            // 1. Environment Reference Sheet
            if ($this->board->environment_id && $this->board->environment?->sheet_image_path) {
                $envSheet = Storage::disk('public')->path($this->board->environment->sheet_image_path);
                if (File::exists($envSheet)) {
                    $referenceImagePaths[] = $envSheet;
                }
            }

            // 2. Character Reference Sheets
            if ($story) {
                $targetCharNames = $this->board->characters_present ?? [];

                foreach ($targetCharNames as $charName) {
                    $char = $story->resolveCharacterByName($charName);

                    if ($char && $char->sheet_image_path) {
                        $charSheet = Storage::disk('public')->path($char->sheet_image_path);
                        if (File::exists($charSheet)) {
                            $referenceImagePaths[] = $charSheet;
                        }
                    }
                }
            }

            // 3. Prop Reference Sheets
            if (! empty($this->board->props_present) && $contentTitle) {
                foreach ($this->board->props_present as $propName) {
                    $prop = $contentTitle->props->firstWhere('name', $propName);
                    if ($prop && $prop->sheet_image_path) {
                        $propSheet = Storage::disk('public')->path($prop->sheet_image_path);
                        if (File::exists($propSheet)) {
                            $referenceImagePaths[] = $propSheet;
                        }
                    }
                }
            }

            $compiler = app(PromptCompilerService::class);
            $compiledPrompt = $this->board->scene_data ? $compiler->compilePrompt($contentTitle, $this->board->scene_data) : 'default prompt';

            $imageService->generateAndSave(
                prompt: $compiledPrompt,
                outputAbsolutePath: $outputAbsolutePath,
                providerOrConnectionId: $provider,
                aspectRatio: $aspectRatio,
                referenceImagePaths: $referenceImagePaths
            );

            $this->board->update([
                'image_path' => $relativePath,
                'status' => $this->board->audio_path ? 'ready' : 'image_ready',
            ]);
        } catch (Exception $e) {
            Log::error('GenerateBoardImageJob failed', [
                'board_id' => $this->board->id,
                'error' => $e->getMessage(),
            ]);

            $this->board->update(['status' => 'failed']);
            throw $e;
        }
    }
}
