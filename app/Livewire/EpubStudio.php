<?php

namespace App\Livewire;

use App\Jobs\GenerateSceneAudioJob;
use App\Jobs\MergeEpisodeVideoJob;
use App\Jobs\ProcessEpubUploadJob;
use App\Jobs\RenderSceneVideoJob;
use App\Models\ContentFactoryTemplate;
use App\Models\EpubProject;
use App\Models\EpubScene;
use App\Services\TranslationService;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class EpubStudio extends Component
{
    use WithFileUploads;

    public $epubFile = null;

    public string $projectTitle = '';

    public string $aspectRatio = '16:9';

    public string $sourceLanguage = 'auto';

    public string $targetLanguage = 'en';

    public string $voiceName = 'en-US-ChristopherNeural';

    public ?string $ttsRate = null;

    public ?string $ttsPitch = null;

    public array $sceneImageFiles = [];

    public ?int $activeProjectId = null;

    public bool $showUploadModal = false;

    #[Url]
    public ?int $template_id = null;

    public function mount(?int $template_id = null): void
    {
        $templateId = $template_id ?? $this->template_id ?? request()->query('template_id');
        if ($templateId) {
            $template = ContentFactoryTemplate::find($templateId);
            if ($template) {
                $this->aspectRatio = $template->aspect_ratio;
                $this->voiceName = $template->voice_name;
                $this->ttsRate = $template->tts_rate;
                $this->ttsPitch = $template->tts_pitch;
                if (! empty($template->example_titles) && is_array($template->example_titles)) {
                    $this->projectTitle = $template->example_titles[0];
                } else {
                    $this->projectTitle = $template->name;
                }
                $this->showUploadModal = true;
            }
        }
    }

    public function openUploadModal(): void
    {
        $this->reset(['epubFile', 'projectTitle']);
        $this->aspectRatio = '16:9';
        $this->sourceLanguage = 'auto';
        $this->targetLanguage = 'en';
        $this->voiceName = app(TranslationService::class)->getDefaultVoiceForLanguage('en');
        $this->showUploadModal = true;
    }

    public function updatedTargetLanguage(string $value): void
    {
        $this->voiceName = app(TranslationService::class)->getDefaultVoiceForLanguage($value);
    }

    public function uploadEpub(): void
    {
        $this->validate([
            'projectTitle' => 'required|string|max:255',
            'epubFile' => 'required|file|max:102400', // 100MB
            'aspectRatio' => 'required|string|in:16:9,9:16',
            'sourceLanguage' => 'required|string|max:10',
            'targetLanguage' => 'required|string|max:10',
            'voiceName' => 'required|string|max:100',
        ]);

        try {
            $originalName = pathinfo($this->epubFile->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = strtolower($this->epubFile->getClientOriginalExtension() ?: 'epub');
            $filename = 'doc_'.uniqid().'_'.time().".{$ext}";
            $relPath = "epubs/{$filename}";
            $absPath = Storage::disk('public')->path($relPath);
            File::ensureDirectoryExists(dirname($absPath));

            $this->epubFile->storeAs('epubs', $filename, 'public');

            $project = EpubProject::create([
                'user_id' => Auth::id(),
                'title' => $this->projectTitle ?: $originalName,
                'epub_path' => $relPath,
                'status' => 'uploaded',
                'aspect_ratio' => $this->aspectRatio,
                'source_language' => $this->sourceLanguage,
                'target_language' => $this->targetLanguage,
                'voice_name' => $this->voiceName,
                'tts_rate' => $this->ttsRate,
                'tts_pitch' => $this->ttsPitch,
                'total_scenes' => 0,
                'rendered_scenes' => 0,
            ]);

            ProcessEpubUploadJob::dispatch($project);

            $this->activeProjectId = $project->id;
            $this->showUploadModal = false;
            $this->reset(['epubFile', 'projectTitle']);

            Flux::toast(variant: 'success', text: __('Document uploaded! Processing pipeline started in background.'));
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: 'Upload failed: '.$e->getMessage());
        }
    }

    public function selectProject(int $projectId): void
    {
        $this->activeProjectId = $projectId;
    }

    public function updatedSceneImageFiles($value, $key): void
    {
        $sceneId = (int) $key;
        $this->saveCustomSceneImage($sceneId);
    }

    public function saveCustomSceneImage(int $sceneId): void
    {
        if (! isset($this->sceneImageFiles[$sceneId])) {
            return;
        }

        $this->validate([
            "sceneImageFiles.{$sceneId}" => 'required|image|max:20480', // 20MB
        ]);

        $scene = EpubScene::find($sceneId);
        if (! $scene) {
            return;
        }

        $file = $this->sceneImageFiles[$sceneId];
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = "custom_scene_{$scene->id}_".time().".{$ext}";

        $file->storeAs("epub_projects/{$scene->epub_project_id}/images", $filename, 'public');
        $relPath = "epub_projects/{$scene->epub_project_id}/images/{$filename}";

        $originalImagePath = $scene->original_image_path ?: $scene->image_path;

        // Invalidate and delete existing video clip generated with old image
        if ($scene->video_path && ! str_starts_with($scene->video_path, 'http://') && ! str_starts_with($scene->video_path, 'https://')) {
            Storage::disk('public')->delete($scene->video_path);
        }

        $scene->update([
            'original_image_path' => $originalImagePath,
            'image_path' => $relPath,
            'video_path' => null,
            'status' => $scene->audio_path ? 'audio_ready' : 'pending',
            'error_message' => null,
        ]);

        $project = $scene->project;
        if ($project) {
            $project->update([
                'rendered_scenes' => $project->scenes()->where('status', 'completed')->count(),
            ]);
        }

        unset($this->sceneImageFiles[$sceneId]);

        Flux::toast(variant: 'success', text: __('Custom illustration applied to Scene #:order! Video cache invalidated. Click Regenerate when ready.', ['order' => $scene->sequence_order]));
    }

    public function resetSceneImage(int $sceneId): void
    {
        $scene = EpubScene::find($sceneId);
        if (! $scene) {
            return;
        }

        if ($scene->original_image_path) {
            // Invalidate and delete existing video clip
            if ($scene->video_path && ! str_starts_with($scene->video_path, 'http://') && ! str_starts_with($scene->video_path, 'https://')) {
                Storage::disk('public')->delete($scene->video_path);
            }

            $scene->update([
                'image_path' => $scene->original_image_path,
                'video_path' => null,
                'status' => $scene->audio_path ? 'audio_ready' : 'pending',
                'error_message' => null,
            ]);

            $project = $scene->project;
            if ($project) {
                $project->update([
                    'rendered_scenes' => $project->scenes()->where('status', 'completed')->count(),
                ]);
            }

            Flux::toast(variant: 'success', text: __('Restored original eBook illustration for Scene #:order! Click Regenerate to re-render video.', ['order' => $scene->sequence_order]));
        }
    }

    public function retryProject(int $projectId): void
    {
        $project = EpubProject::find($projectId);
        if ($project) {
            $project->update(['status' => 'uploaded', 'error_message' => null]);
            ProcessEpubUploadJob::dispatch($project);
            Flux::toast(variant: 'success', text: __('Pipeline re-triggered for project.'));
        }
    }

    public function retryScene(int $sceneId): void
    {
        $this->regenerateScene($sceneId, 'video_only');
    }

    public function regenerateScene(int $sceneId, string $mode = 'all_with_tts'): void
    {
        $scene = EpubScene::find($sceneId);
        if (! $scene) {
            return;
        }

        $scene->update(['status' => 'pending', 'error_message' => null]);

        if ($mode === 'video_only') {
            if ($scene->audio_path && Storage::disk('public')->exists($scene->audio_path)) {
                RenderSceneVideoJob::dispatch($scene);
                Flux::toast(variant: 'success', text: __('Re-rendering video for scene #:order (using existing audio)', ['order' => $scene->sequence_order]));
            } else {
                GenerateSceneAudioJob::dispatch($scene);
                Flux::toast(variant: 'warning', text: __('Audio missing: Re-synthesizing TTS and video for scene #:order', ['order' => $scene->sequence_order]));
            }
        } else {
            GenerateSceneAudioJob::dispatch($scene);
            Flux::toast(variant: 'success', text: __('Full regeneration started for scene #:order (TTS + Video)', ['order' => $scene->sequence_order]));
        }
    }

    public function deleteProject(int $projectId): void
    {
        $project = EpubProject::find($projectId);
        if ($project) {
            $project->delete();
            if ($this->activeProjectId === $projectId) {
                $this->activeProjectId = null;
            }
            Flux::toast(variant: 'success', text: __('EPUB Project deleted.'));
        }
    }

    public function deleteScene(int $sceneId): void
    {
        $scene = EpubScene::find($sceneId);
        if (! $scene) {
            return;
        }

        $project = $scene->project;
        $deletedOrder = $scene->sequence_order;

        // Clean up media files on disk
        if ($scene->image_path && ! str_starts_with($scene->image_path, 'http://') && ! str_starts_with($scene->image_path, 'https://')) {
            Storage::disk('public')->delete($scene->image_path);
        }
        if ($scene->audio_path && ! str_starts_with($scene->audio_path, 'http://') && ! str_starts_with($scene->audio_path, 'https://')) {
            Storage::disk('public')->delete($scene->audio_path);
        }
        if ($scene->video_path && ! str_starts_with($scene->video_path, 'http://') && ! str_starts_with($scene->video_path, 'https://')) {
            Storage::disk('public')->delete($scene->video_path);
        }

        $scene->delete();

        if ($project) {
            // Re-index sequence orders sequentially
            $remainingScenes = $project->scenes()->orderBy('sequence_order')->get();
            foreach ($remainingScenes as $index => $remainingScene) {
                $newOrder = $index + 1;
                if ($remainingScene->sequence_order !== $newOrder) {
                    $remainingScene->update(['sequence_order' => $newOrder]);
                }
            }

            $project->update([
                'total_scenes' => $remainingScenes->count(),
                'rendered_scenes' => $project->scenes()->where('status', 'completed')->count(),
            ]);
        }

        Flux::toast(variant: 'success', text: __('Scene #:order deleted and sequence renumbered.', ['order' => $deletedOrder]));
    }

    public function remergeEpisode(int $projectId): void
    {
        $project = EpubProject::find($projectId);
        if (! $project) {
            return;
        }

        $completedScenesCount = $project->scenes()->where('status', 'completed')->count();
        if ($completedScenesCount === 0) {
            Flux::toast(variant: 'danger', text: __('Cannot merge episode: No completed scene videos found.'));

            return;
        }

        MergeEpisodeVideoJob::dispatch($project);
        Flux::toast(variant: 'success', text: __('Episode video re-merge started in background.'));
    }

    public function render()
    {
        $projects = EpubProject::with('scenes')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeProject = $this->activeProjectId ? EpubProject::with('scenes')->find($this->activeProjectId) : $projects->first();

        $translationService = app(TranslationService::class);

        return view('livewire.epub-studio', [
            'projects' => $projects,
            'activeProject' => $activeProject,
            'supportedLanguages' => $translationService->getSupportedLanguages(),
            'groupedVoices' => $translationService->getGroupedVoices(),
            'translationService' => $translationService,
        ]);
    }
}
