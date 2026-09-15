<?php

namespace App\Livewire;

use App\Jobs\MergeVideoBuilderJob;
use App\Models\Video;
use App\Models\VideoBuilderProject;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\Process\Process;

class VideoBuilder extends Component
{
    use WithFileUploads, WithPagination;

    /**
     * Active navigation tab.
     */
    public string $activeTab = 'merger';

    /**
     * Main video source mode: 'file', 'library', 'url'.
     */
    public string $mainSourceType = 'file';

    /**
     * Uploaded main video file.
     */
    public $mainVideoFile;

    /**
     * Selected video ID from library.
     */
    public ?int $selectedLibraryVideoId = null;

    /**
     * Video URL (YouTube, TikTok, etc.).
     */
    public string $mainVideoUrl = '';

    /**
     * Custom project title.
     */
    public string $title = '';

    /**
     * Whether to save the rendered output into the user's permanent Video Library.
     */
    public bool $saveToLibrary = true;

    /**
     * Canvas aspect ratio: '16:9' or '9:16'.
     */
    public string $aspectRatio = '16:9';

    /**
     * Overlay assets list.
     *
     * @var array<int, array{
     *     id: string,
     *     type: string,
     *     name: string,
     *     file_path: string,
     *     preview_url: string,
     *     remove_green_screen: bool,
     *     mute_audio: bool,
     *     x_percent: float,
     *     y_percent: float,
     *     width_percent: float,
     *     height_percent: float,
     *     start_seconds: float|null,
     *     end_seconds: float|null
     * }>
     */
    public array $assets = [];

    /**
     * Pending asset file for upload.
     */
    public $newAssetFile;

    /**
     * Selected library video ID to add as asset.
     */
    public ?int $libraryAssetVideoId = null;

    /**
     * Active selected asset index for canvas editing.
     */
    public ?int $activeAssetIndex = null;

    /**
     * Recompute locked asset dimensions when canvas aspect ratio changes so the original asset aspect ratio is maintained.
     */
    public function updatedAspectRatio(string $newRatio): void
    {
        $canvasRatio = ($newRatio === '9:16') ? (9.0 / 16.0) : (16.0 / 9.0);

        foreach ($this->assets as $idx => $asset) {
            if (! empty($asset['lock_aspect_ratio']) && ! empty($asset['original_aspect_ratio'])) {
                $origAspect = (float) $asset['original_aspect_ratio'];
                if ($origAspect > 0) {
                    $w = (float) ($asset['width_percent'] ?? 25.0);
                    $newH = round($w * ($canvasRatio / $origAspect), 1);
                    if ($newH > 85.0) {
                        $newH = 85.0;
                        $w = round($newH * ($origAspect / $canvasRatio), 1);
                        $this->assets[$idx]['width_percent'] = max(5.0, min(100.0, $w));
                    }
                    $this->assets[$idx]['height_percent'] = max(5.0, min(100.0, $newH));
                }
            }
        }
    }

    /**
     * Detect original dimensions (width, height) and aspect ratio of an asset.
     *
     * @return array{width: int, height: int, aspect_ratio: float}
     */
    protected function getMediaDimensions(string $filePath, string $type): array
    {
        $absPath = Storage::disk('public')->exists($filePath)
            ? Storage::disk('public')->path($filePath)
            : (Storage::disk('local')->exists($filePath) ? Storage::disk('local')->path($filePath) : $filePath);

        $width = 1920;
        $height = 1080;

        if ($type === 'image' && file_exists($absPath)) {
            $imgSize = @getimagesize($absPath);
            if ($imgSize && $imgSize[0] > 0 && $imgSize[1] > 0) {
                $width = (int) $imgSize[0];
                $height = (int) $imgSize[1];
            }
        } elseif ($type === 'video' && file_exists($absPath)) {
            $ffprobeBin = config('laravel-ffmpeg.ffprobe.binaries', env('FFPROBE_BINARIES', 'ffprobe'));
            if (! file_exists($ffprobeBin) && file_exists('C:/ffmpeg/bin/ffprobe.exe')) {
                $ffprobeBin = 'C:/ffmpeg/bin/ffprobe.exe';
            }

            $cmd = [
                $ffprobeBin,
                '-v',
                'error',
                '-select_streams',
                'v:0',
                '-show_entries',
                'stream=width,height',
                '-of',
                'csv=s=x:p=0',
                $absPath,
            ];

            try {
                $process = new Process($cmd);
                $process->setTimeout(15);
                $process->run();
                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    $parts = explode('x', $output);
                    if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $w = (int) $parts[0];
                        $h = (int) $parts[1];
                        if ($w > 0 && $h > 0) {
                            $width = $w;
                            $height = $h;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fall back to default
            }
        }

        $aspectRatio = ($height > 0) ? ($width / $height) : 1.0;

        return [
            'width' => $width,
            'height' => $height,
            'aspect_ratio' => round($aspectRatio, 4),
        ];
    }

    /**
     * Handle asset file upload immediately upon selection.
     */
    public function updatedNewAssetFile(): void
    {
        $this->validate([
            'newAssetFile' => 'required|file|mimes:mp4,mov,avi,webm,png,jpg,jpeg,webp,gif|max:204800',
        ]);

        $mimeType = $this->newAssetFile->getMimeType() ?: '';
        $isVideo = str_starts_with($mimeType, 'video/');
        $type = $isVideo ? 'video' : 'image';

        $storedPath = $this->newAssetFile->store('video_builder/assets', 'public');
        $previewUrl = Storage::disk('public')->url($storedPath);
        $originalName = $this->newAssetFile->getClientOriginalName();

        $dim = $this->getMediaDimensions($storedPath, $type);
        $origAspect = $dim['aspect_ratio']; // Width / Height

        $canvasRatio = ($this->aspectRatio === '9:16') ? (9.0 / 16.0) : (16.0 / 9.0);
        $defaultWidth = ($this->aspectRatio === '9:16') ? 40.0 : 25.0;
        $defaultHeight = round($defaultWidth * ($canvasRatio / $origAspect), 1);

        if ($defaultHeight > 75.0) {
            $defaultHeight = 75.0;
            $defaultWidth = round($defaultHeight * ($origAspect / $canvasRatio), 1);
        } elseif ($defaultHeight < 10.0) {
            $defaultHeight = 10.0;
            $defaultWidth = round($defaultHeight * ($origAspect / $canvasRatio), 1);
        }

        $newAsset = [
            'id' => 'asset_'.Str::random(8),
            'type' => $type,
            'name' => $originalName,
            'file_path' => $storedPath,
            'preview_url' => $previewUrl,
            'remove_green_screen' => false,
            'mute_audio' => true,
            'lock_aspect_ratio' => true,
            'original_width' => $dim['width'],
            'original_height' => $dim['height'],
            'original_aspect_ratio' => $origAspect,
            'x_percent' => 15.0 + (count($this->assets) * 5.0) % 50.0,
            'y_percent' => 15.0 + (count($this->assets) * 5.0) % 50.0,
            'width_percent' => $defaultWidth,
            'height_percent' => $defaultHeight,
            'start_seconds' => null,
            'end_seconds' => null,
        ];

        $this->assets[] = $newAsset;
        $this->activeAssetIndex = count($this->assets) - 1;
        $this->newAssetFile = null;

        Flux::toast(
            variant: 'success',
            text: __('Asset ":name" added.', ['name' => $originalName])
        );
    }

    /**
     * Add an existing library video as an asset.
     */
    public function addLibraryAsset(): void
    {
        if (! $this->libraryAssetVideoId) {
            return;
        }

        $video = Video::where('user_id', Auth::id())->find($this->libraryAssetVideoId);
        if (! $video || ! $video->video_path) {
            Flux::toast(variant: 'danger', text: __('Video not found in library.'));

            return;
        }

        $dim = $this->getMediaDimensions($video->video_path, 'video');
        $origAspect = $dim['aspect_ratio'];

        $canvasRatio = ($this->aspectRatio === '9:16') ? (9.0 / 16.0) : (16.0 / 9.0);
        $defaultWidth = ($this->aspectRatio === '9:16') ? 40.0 : 25.0;
        $defaultHeight = round($defaultWidth * ($canvasRatio / $origAspect), 1);

        if ($defaultHeight > 75.0) {
            $defaultHeight = 75.0;
            $defaultWidth = round($defaultHeight * ($origAspect / $canvasRatio), 1);
        } elseif ($defaultHeight < 10.0) {
            $defaultHeight = 10.0;
            $defaultWidth = round($defaultHeight * ($origAspect / $canvasRatio), 1);
        }

        $this->assets[] = [
            'id' => 'asset_'.Str::random(8),
            'type' => 'video',
            'name' => $video->title ?: $video->original_filename,
            'file_path' => $video->video_path,
            'preview_url' => '',
            'remove_green_screen' => false,
            'mute_audio' => true,
            'lock_aspect_ratio' => true,
            'original_width' => $dim['width'],
            'original_height' => $dim['height'],
            'original_aspect_ratio' => $origAspect,
            'x_percent' => 20.0,
            'y_percent' => 20.0,
            'width_percent' => $defaultWidth,
            'height_percent' => $defaultHeight,
            'start_seconds' => null,
            'end_seconds' => null,
        ];

        $this->activeAssetIndex = count($this->assets) - 1;
        $this->libraryAssetVideoId = null;

        Flux::toast(variant: 'success', text: __('Library video added as overlay asset.'));
    }

    /**
     * Remove an asset by index.
     */
    public function removeAsset(int $index): void
    {
        if (isset($this->assets[$index])) {
            unset($this->assets[$index]);
            $this->assets = array_values($this->assets);

            if ($this->activeAssetIndex === $index) {
                $this->activeAssetIndex = count($this->assets) > 0 ? 0 : null;
            } elseif ($this->activeAssetIndex > $index) {
                $this->activeAssetIndex--;
            }
        }
    }

    /**
     * Select an asset as active for canvas editing.
     */
    public function selectAsset(int $index): void
    {
        if (isset($this->assets[$index])) {
            $this->activeAssetIndex = $index;
        }
    }

    /**
     * Apply a quick position preset to an asset.
     */
    public function applyPositionPreset(int $index, string $preset): void
    {
        if (! isset($this->assets[$index])) {
            return;
        }

        $w = (float) $this->assets[$index]['width_percent'];
        $h = (float) $this->assets[$index]['height_percent'];

        $x = 0.0;
        $y = 0.0;

        switch ($preset) {
            case 'top-left':
                $x = 4.0;
                $y = 4.0;
                break;
            case 'top-center':
                $x = max(0.0, (100.0 - $w) / 2.0);
                $y = 4.0;
                break;
            case 'top-right':
                $x = max(0.0, 100.0 - $w - 4.0);
                $y = 4.0;
                break;
            case 'center-left':
                $x = 4.0;
                $y = max(0.0, (100.0 - $h) / 2.0);
                break;
            case 'center':
                $x = max(0.0, (100.0 - $w) / 2.0);
                $y = max(0.0, (100.0 - $h) / 2.0);
                break;
            case 'center-right':
                $x = max(0.0, 100.0 - $w - 4.0);
                $y = max(0.0, (100.0 - $h) / 2.0);
                break;
            case 'bottom-left':
                $x = 4.0;
                $y = max(0.0, 100.0 - $h - 4.0);
                break;
            case 'bottom-center':
                $x = max(0.0, (100.0 - $w) / 2.0);
                $y = max(0.0, 100.0 - $h - 4.0);
                break;
            case 'bottom-right':
                $x = max(0.0, 100.0 - $w - 4.0);
                $y = max(0.0, 100.0 - $h - 4.0);
                break;
        }

        $this->assets[$index]['x_percent'] = round($x, 1);
        $this->assets[$index]['y_percent'] = round($y, 1);
    }

    /**
     * Update asset transform coordinates from interactive canvas dragging.
     */
    public function updateAssetTransform(int $index, float $x, float $y, float $w, float $h): void
    {
        if (isset($this->assets[$index])) {
            $this->assets[$index]['x_percent'] = max(0.0, min(100.0, round($x, 1)));
            $this->assets[$index]['y_percent'] = max(0.0, min(100.0, round($y, 1)));
            $this->assets[$index]['width_percent'] = max(2.0, min(100.0, round($w, 1)));
            $this->assets[$index]['height_percent'] = max(2.0, min(100.0, round($h, 1)));
        }
    }

    /**
     * Submit and dispatch the video merge background job.
     */
    public function submitMerge(): void
    {
        $rules = [
            'aspectRatio' => 'required|in:16:9,9:16',
            'mainSourceType' => 'required|in:file,library,url',
            'title' => 'nullable|string|max:255',
            'assets' => 'nullable|array',
            'assets.*.x_percent' => 'required|numeric|min:0|max:100',
            'assets.*.y_percent' => 'required|numeric|min:0|max:100',
            'assets.*.width_percent' => 'required|numeric|min:2|max:100',
            'assets.*.height_percent' => 'required|numeric|min:2|max:100',
            'assets.*.start_seconds' => 'nullable|numeric|min:0',
            'assets.*.end_seconds' => 'nullable|numeric|min:0',
            'assets.*.lock_aspect_ratio' => 'nullable|boolean',
            'assets.*.original_aspect_ratio' => 'nullable|numeric',
            'assets.*.original_width' => 'nullable|numeric',
            'assets.*.original_height' => 'nullable|numeric',
        ];

        if ($this->mainSourceType === 'file') {
            $rules['mainVideoFile'] = 'required|file|mimes:mp4,mov,avi,webm,mkv|max:512000';
        } elseif ($this->mainSourceType === 'library') {
            $rules['selectedLibraryVideoId'] = 'required|exists:videos,id';
        } else {
            $rules['mainVideoUrl'] = 'required|url';
        }

        $this->validate($rules, [
            'mainVideoFile.required' => __('Please select a main video file to upload.'),
            'selectedLibraryVideoId.required' => __('Please select a video from your library.'),
            'mainVideoUrl.required' => __('Please enter a valid video URL.'),
        ]);

        $mainVideoPath = null;
        $mainVideoUrl = null;
        $projectTitle = $this->title;
        $hasAssets = ! empty($this->assets);

        if ($this->mainSourceType === 'file') {
            $mainVideoPath = $this->mainVideoFile->store('videos', 'local');
            if (empty($projectTitle)) {
                $projectTitle = pathinfo($this->mainVideoFile->getClientOriginalName(), PATHINFO_FILENAME);
            }
        } elseif ($this->mainSourceType === 'library') {
            $libraryVideo = Video::where('user_id', Auth::id())->findOrFail($this->selectedLibraryVideoId);
            $mainVideoPath = $libraryVideo->video_path;
            if (empty($projectTitle)) {
                $projectTitle = $libraryVideo->title ?: ($hasAssets ? 'Merged '.$libraryVideo->original_filename : $libraryVideo->original_filename);
            }
        } else {
            $mainVideoUrl = $this->mainVideoUrl;
            if (empty($projectTitle)) {
                $host = parse_url($this->mainVideoUrl, PHP_URL_HOST) ?: 'URL';
                $projectTitle = $hasAssets ? "Merged URL Video ({$host})" : "Downloaded URL Video ({$host})";
            }
        }

        // Create the VideoBuilderProject
        $project = VideoBuilderProject::create([
            'user_id' => Auth::id(),
            'title' => $projectTitle ?: ($hasAssets ? __('Merged Video Project') : __('Downloaded Video Project')),
            'aspect_ratio' => $this->aspectRatio,
            'main_source_type' => $this->mainSourceType,
            'main_video_path' => $mainVideoPath,
            'main_video_url' => $mainVideoUrl,
            'assets_config' => $this->assets,
            'save_to_library' => $this->saveToLibrary,
            'status' => 'pending',
        ]);

        // Dispatch background merge job
        MergeVideoBuilderJob::dispatch($project);

        $toastMessage = $hasAssets
            ? __('Video merge project ":title" submitted! Processing in the background.', ['title' => $project->title])
            : __('Direct video download for ":title" started! Processing in the background.', ['title' => $project->title]);

        Flux::toast(variant: 'success', text: $toastMessage);

        // Reset inputs
        $this->reset(['mainVideoFile', 'selectedLibraryVideoId', 'mainVideoUrl', 'title', 'assets', 'activeAssetIndex']);
    }

    /**
     * Manually save a completed project output to user's Video Library.
     */
    public function saveProjectToLibrary(int $projectId): void
    {
        $project = VideoBuilderProject::where('user_id', Auth::id())->find($projectId);
        if (! $project || ! $project->output_video_path || $project->saved_video_id) {
            return;
        }

        $videoTitle = $project->title ?: ($project->main_source_type === 'url' ? 'Downloaded Video' : 'Merged Video');
        $origFilename = $project->main_source_type === 'url'
            ? (basename(parse_url($project->main_video_url ?: '', PHP_URL_PATH) ?: '') ?: 'download.mp4')
            : basename($project->output_video_path);

        $libraryVideo = Video::create([
            'user_id' => $project->user_id,
            'title' => $videoTitle,
            'original_filename' => $origFilename,
            'video_path' => $project->output_video_path,
            'status' => 'completed',
            'processing_mode' => 'video_builder',
        ]);

        $project->update([
            'save_to_library' => true,
            'saved_video_id' => $libraryVideo->id,
        ]);

        Flux::toast(variant: 'success', text: __('Video ":title" saved to your Video Library!', ['title' => $videoTitle]));
    }

    /**
     * Delete a project.
     */
    public function deleteProject(int $projectId): void
    {
        $project = VideoBuilderProject::where('user_id', Auth::id())->find($projectId);
        if ($project) {
            if ($project->saved_video_id) {
                $savedVideo = Video::where('user_id', Auth::id())->find($project->saved_video_id);
                if ($savedVideo) {
                    $savedVideo->delete();
                }
            }

            if ($project->output_video_path && Storage::disk('public')->exists($project->output_video_path)) {
                Storage::disk('public')->delete($project->output_video_path);
            }
            $project->delete();
            Flux::toast(variant: 'success', text: __('Project deleted.'));
        }
    }

    /**
     * Render component.
     */
    public function render()
    {
        $libraryVideos = Video::where('user_id', Auth::id())
            ->whereNotNull('video_path')
            ->where('video_path', '!=', '')
            ->latest()
            ->get();

        $projects = VideoBuilderProject::where('user_id', Auth::id())
            ->latest()
            ->paginate(5);

        // Check if any projects are currently processing or pending to auto-poll
        $hasActiveJobs = VideoBuilderProject::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'downloading_source', 'rendering'])
            ->exists();

        return view('livewire.video-builder', [
            'libraryVideos' => $libraryVideos,
            'projects' => $projects,
            'hasActiveJobs' => $hasActiveJobs,
        ]);
    }
}
