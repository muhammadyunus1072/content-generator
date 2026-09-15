<?php

use App\Http\Controllers\EpubUploadController;
use App\Jobs\ExtractAudioJob;
use App\Jobs\GenerateContentJob;
use App\Jobs\GenerateSubtitlesJob;
use App\Models\ContentTitle;
use App\Models\Episode;
use App\Models\Story;
use App\Models\Video;
use App\Services\GeminiImageService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/test-gemini-image', function (GeminiImageService $service) {
    try {
        // Prompt test statis
        $prompt = 'Ultra-realistic Indonesian female character named Naya Arunika, 23 years old, mixed Sundanese and Javanese heritage, proportional oval face, naturally fair warm-toned Indonesian skin with visible realistic skin texture, subtle pores and natural imperfections, expressive deep brown eyes, soft naturally arched eyebrows, proportional natural nose, natural rosy lips, gentle confident smile, friendly direct eye contact with a subtle mysterious presence, neat relaxed chin-to-neck-length dark espresso brown bob haircut with slightly curved inward ends, highly feminine curvy body silhouette with natural realistic anatomy and balanced proportions, wearing a signature sage green spaghetti-strap tank top, high-waisted blue denim shorts, black and white classic skate sneakers, delicate silver necklace with a tiny water-drop pendant, minimal silver stud earrings, small silver wristwatch, short nude-colored nails. Calm, cheerful, approachable, intelligent and confident personality. Relaxed shoulders, elegant casual posture, subtle head tilt, cinematic realism, premium commercial photography, realistic anatomy, natural skin details, high facial consistency, recognizable recurring TikTok character, sophisticated wellness and beauty creator identity, sage green visual branding, no exaggerated glamour makeup, no fantasy styling.';

        // Panggil service
        $imageBytes = $service->generateImage($prompt);

        // Return langsung sebagai response gambar agar tampil di browser
        return response($imageBytes)->header('Content-Type', 'image/jpeg');
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('video-builder', 'video-builder')->name('video-builder');
    Route::view('audio-builder', 'audio-builder')->name('audio-builder');
    Route::view('image-generator', 'image-generator')->name('image-generator');
    Route::view('story-studio', 'story-studio')->name('story-studio');
    Route::view('comic-studio', 'comic-studio')->name('comic-studio');
    Route::get('webtoon-reader/{episode}', function (Episode $episode) {
        return view('webtoon-reader', ['episode' => $episode]);
    })->name('webtoon-reader');
    Route::get('manga-reader/{episode}', function (Episode $episode) {
        return view('manga-reader', ['episode' => $episode]);
    })->name('manga-reader');
    Route::get('story-studio/title/{contentTitle}', function (ContentTitle $contentTitle) {
        return view('content-title-detail', ['contentTitle' => $contentTitle]);
    })->name('story-studio.title');
    Route::get('story-studio/{story}', function (Story $story) {
        return view('story-project-detail', ['story' => $story]);
    })->name('story-studio.detail');
    Route::view('ai-config', 'ai-config')->name('ai-config');
    Route::view('content-factory', 'content-factory')->name('content-factory');
    Route::view('epub-studio', 'epub-studio')->name('epub-studio');
    Route::post('epub/upload', [EpubUploadController::class, 'upload'])->name('epub.upload');
    Route::post('api/epub/upload', [EpubUploadController::class, 'upload'])->name('api.epub.upload');
});

Route::get('/test', function () {
    $video = Video::find(7);
    // ExtractAudioJob::dispatch($video);
    GenerateSubtitlesJob::dispatch($video);
    GenerateContentJob::dispatch($video);
});
require __DIR__.'/settings.php';
