<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEpubUploadJob;
use App\Models\EpubProject;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class EpubUploadController extends Controller
{
    /**
     * Handle EPUB file upload, create project record, and dispatch background parsing pipeline.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'epub_file' => 'nullable|file|max:102400', // max 100MB
            'document_file' => 'nullable|file|max:102400',
            'aspect_ratio' => 'nullable|string|in:16:9,9:16',
            'voice_name' => 'nullable|string|max:100',
        ]);

        try {
            $file = $request->file('epub_file') ?? $request->file('document_file');
            if (! $file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide an EPUB or HTML file.',
                ], 422);
            }

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension() ?: 'epub');

            $filename = 'doc_'.uniqid().'_'.time().".{$extension}";
            $relPath = "epubs/{$filename}";
            $absPath = Storage::disk('public')->path($relPath);
            File::ensureDirectoryExists(dirname($absPath));

            $file->storeAs('epubs', $filename, 'public');

            $project = EpubProject::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'] ?: $originalName,
                'epub_path' => $relPath,
                'status' => 'uploaded',
                'aspect_ratio' => $validated['aspect_ratio'] ?? '16:9',
                'voice_name' => $validated['voice_name'] ?? 'en-US-ChristopherNeural',
                'total_scenes' => 0,
                'rendered_scenes' => 0,
            ]);

            // Dispatch background queue job immediately
            ProcessEpubUploadJob::dispatch($project);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully and queued for video processing.',
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'aspect_ratio' => $project->aspect_ratio,
                    'voice_name' => $project->voice_name,
                    'created_at' => $project->created_at?->toISOString(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
