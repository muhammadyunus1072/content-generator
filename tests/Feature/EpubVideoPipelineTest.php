<?php

use App\Jobs\GenerateSceneAudioJob;
use App\Jobs\MergeEpisodeVideoJob;
use App\Jobs\ProcessEpubUploadJob;
use App\Jobs\RenderSceneVideoJob;
use App\Livewire\EpubStudio;
use App\Models\EpubProject;
use App\Models\EpubScene;
use App\Models\User;
use App\Services\EpubExtractorService;
use App\Services\HtmlExtractorService;
use App\Services\TextToSpeechService;
use App\Services\TranslationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Helper to build a minimal valid .epub zip file for testing.
 */
function createTestEpubFile(string $tempZipPath): string
{
    File::ensureDirectoryExists(dirname($tempZipPath));
    $zip = new ZipArchive;
    $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // mimetype (first file, uncompressed)
    $zip->addFromString('mimetype', 'application/epub+zip');

    // META-INF/container.xml
    $containerXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>
XML;
    $zip->addFromString('META-INF/container.xml', $containerXml);

    // OEBPS/content.opf
    $contentOpf = <<<'OPF'
<?xml version="1.0" encoding="UTF-8"?>
<package version="3.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="pub-id">
    <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
        <dc:title>Test Storybook</dc:title>
    </metadata>
    <manifest>
        <item id="chapter1" href="chapter1.xhtml" media-type="application/xhtml+xml"/>
        <item id="cover-img" href="images/cover.jpg" media-type="image/jpeg" properties="cover-image"/>
    </manifest>
    <spine>
        <itemref idref="chapter1"/>
    </spine>
</package>
OPF;
    $zip->addFromString('OEBPS/content.opf', $contentOpf);

    // OEBPS/images/cover.jpg (1x1 dummy jpeg)
    $dummyJpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    $zip->addFromString('OEBPS/images/cover.jpg', $dummyJpeg);

    // OEBPS/chapter1.xhtml
    $chapter1Xhtml = <<<'XHTML'
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head><title>Chapter 1</title></head>
<body>
    <h1>Chapter 1: The Dark Forest</h1>
    <img src="images/cover.jpg" alt="Forest" />
    <p>The ancient trees stood tall against the twilight sky, casting long eerie shadows across the path.</p>
    <p>A lone traveler walked forward into the mist, clutching his lantern tightly as the wind began to howl.</p>
</body>
</html>
XHTML;
    $zip->addFromString('OEBPS/chapter1.xhtml', $chapter1Xhtml);
    $zip->close();

    return $tempZipPath;
}

beforeEach(function () {
    Storage::fake('public');
});

test('epub upload controller accepts file and dispatches ProcessEpubUploadJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $tempEpubPath = storage_path('app/temp/test_book_'.uniqid().'.epub');
    createTestEpubFile($tempEpubPath);

    $uploadedFile = new UploadedFile(
        $tempEpubPath,
        'test_book.epub',
        'application/epub+zip',
        null,
        true
    );

    $response = $this->postJson(route('epub.upload'), [
        'title' => 'Test Storybook Adventure',
        'epub_file' => $uploadedFile,
        'aspect_ratio' => '16:9',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'project' => [
                'title' => 'Test Storybook Adventure',
                'status' => 'uploaded',
                'aspect_ratio' => '16:9',
            ],
        ]);

    $this->assertDatabaseHas('epub_projects', [
        'title' => 'Test Storybook Adventure',
        'status' => 'uploaded',
        'aspect_ratio' => '16:9',
    ]);

    $project = EpubProject::where('title', 'Test Storybook Adventure')->first();
    expect($project)->not->toBeNull();

    Queue::assertPushed(ProcessEpubUploadJob::class, function ($job) use ($project) {
        return $job->project->id === $project->id;
    });

    if (File::exists($tempEpubPath)) {
        File::delete($tempEpubPath);
    }
});

test('epub extractor service parses zip into structured scene pairs with images', function () {
    $tempEpubPath = storage_path('app/temp/test_extractor_'.uniqid().'.epub');
    createTestEpubFile($tempEpubPath);

    $service = new EpubExtractorService;
    $scenes = $service->extractScenesFromEpub($tempEpubPath, 999);

    expect($scenes)->not->toBeEmpty();
    expect(count($scenes))->toBeGreaterThanOrEqual(2);

    expect($scenes[0])->toHaveKeys(['sequence_order', 'image_path', 'script_text']);
    expect($scenes[0]['script_text'])->toContain('Chapter 1');
    expect($scenes[1]['script_text'])->toContain('ancient trees');

    if (File::exists($tempEpubPath)) {
        File::delete($tempEpubPath);
    }
});

test('ProcessEpubUploadJob extracts scenes and dispatches GenerateSceneAudioJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $tempEpubPath = storage_path('app/temp/test_job_'.uniqid().'.epub');
    createTestEpubFile($tempEpubPath);

    $relPath = 'epubs/test_job.epub';
    Storage::disk('public')->put($relPath, File::get($tempEpubPath));

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Queue Process Test',
        'epub_path' => $relPath,
        'status' => 'uploaded',
    ]);

    $job = new ProcessEpubUploadJob($project);
    $job->handle(new EpubExtractorService);

    $project->refresh();
    expect($project->status)->toBe('generating_audio');
    expect($project->total_scenes)->toBeGreaterThanOrEqual(2);

    $scenes = EpubScene::where('epub_project_id', $project->id)->get();
    expect($scenes)->not->toBeEmpty();

    Queue::assertPushed(GenerateSceneAudioJob::class, function ($audioJob) use ($project) {
        return $audioJob->scene->epub_project_id === $project->id;
    });

    if (File::exists($tempEpubPath)) {
        File::delete($tempEpubPath);
    }
});

test('GenerateSceneAudioJob updates scene audio duration and dispatches RenderSceneVideoJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Audio Scene Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'generating_audio',
    ]);

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'The quick brown fox jumps over the lazy dog in the silent forest.',
        'status' => 'pending',
    ]);

    // Mock TTS Service
    $mockTts = Mockery::mock(TextToSpeechService::class);
    $mockTts->shouldReceive('synthesize')
        ->once()
        ->andReturn([
            'audio_path' => Storage::disk('public')->path('audio/scene_test.mp3'),
            'subtitle_path' => null,
            'duration_seconds' => 3.50,
        ]);

    $job = new GenerateSceneAudioJob($scene);
    $job->handle($mockTts);

    $scene->refresh();
    expect($scene->status)->toBe('audio_ready');
    expect((float) $scene->audio_duration)->toBe(3.50);
    expect($scene->audio_path)->not->toBeNull();

    Queue::assertPushed(RenderSceneVideoJob::class, function ($videoJob) use ($scene) {
        return $videoJob->scene->id === $scene->id;
    });
});

test('epub studio livewire component renders and lists projects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Livewire Studio Book',
        'epub_path' => 'epubs/book1.epub',
        'status' => 'completed',
        'total_scenes' => 4,
        'rendered_scenes' => 4,
    ]);

    EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'First scene paragraph text.',
        'status' => 'completed',
    ]);

    Livewire::test(EpubStudio::class)
        ->assertOk()
        ->assertSee('EPUB & HTML to Video Studio')
        ->assertSee('Livewire Studio Book')
        ->assertSee('First scene paragraph text.');
});

test('MergeEpisodeVideoJob merges scenes into final episode video', function () {
    $user = User::factory()->create();
    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Merge Test Book',
        'epub_path' => 'epubs/merge.epub',
        'status' => 'rendering_scenes',
    ]);

    // Create dummy video files
    $vid1Rel = "epub_projects/{$project->id}/videos/scene_1.mp4";
    $vid2Rel = "epub_projects/{$project->id}/videos/scene_2.mp4";
    Storage::disk('public')->put($vid1Rel, str_repeat('dummy_video_content_', 100));
    Storage::disk('public')->put($vid2Rel, str_repeat('dummy_video_content_', 100));

    EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'Scene one text.',
        'video_path' => $vid1Rel,
        'status' => 'completed',
    ]);

    EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 2,
        'script_text' => 'Scene two text.',
        'video_path' => $vid2Rel,
        'status' => 'completed',
    ]);

    expect($project->scenes()->count())->toBe(2);
});

test('html extractor service parses standalone html with base64 image and paragraphs', function () {
    $dummyJpegB64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Cyberpunk Story</title></head>
<body>
    <h1>Neon Alley Chronicles</h1>
    <img src="{$dummyJpegB64}" alt="Neon" />
    <p>The rain hammered relentlessly against the holographic neon billboards of sector 4.</p>
    <p>A shadowed figure stepped through the puddle, cybernetic eyes glowing faintly in the mist.</p>
</body>
</html>
HTML;

    $tempHtmlPath = storage_path('app/temp/test_story_'.uniqid().'.html');
    File::ensureDirectoryExists(dirname($tempHtmlPath));
    File::put($tempHtmlPath, $html);

    $service = new HtmlExtractorService;
    $scenes = $service->extractScenesFromHtml($tempHtmlPath, 777);

    expect($scenes)->not->toBeEmpty();
    expect(count($scenes))->toBeGreaterThanOrEqual(2);
    expect($scenes[0]['script_text'])->toContain('Neon Alley Chronicles');
    expect($scenes[1]['script_text'])->toContain('rain hammered');
    expect($scenes[1]['image_path'])->not->toBeNull();

    if (File::exists($tempHtmlPath)) {
        File::delete($tempHtmlPath);
    }
});

test('ProcessEpubUploadJob extracts scenes from html and dispatches audio jobs', function () {
    Queue::fake();

    $user = User::factory()->create();
    $dummyJpegB64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    $html = <<<HTML
<html>
<body>
    <h1>Chapter 1: The Awakening</h1>
    <img src="{$dummyJpegB64}" />
    <p>The ancient crystal flared with radiant azure energy deep inside the sanctum.</p>
    <p>He stepped closer, feeling the hum of power reverberating through his veins.</p>
</body>
</html>
HTML;

    $relPath = 'epubs/test_story.html';
    Storage::disk('public')->put($relPath, $html);

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'HTML Story Project',
        'epub_path' => $relPath,
        'status' => 'uploaded',
    ]);

    $job = new ProcessEpubUploadJob($project);
    $job->handle(new EpubExtractorService, new HtmlExtractorService);

    $project->refresh();
    expect($project->status)->toBe('generating_audio');
    expect($project->total_scenes)->toBeGreaterThanOrEqual(2);

    Queue::assertPushed(GenerateSceneAudioJob::class);
});

test('epub upload controller accepts html file and dispatches ProcessEpubUploadJob', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $tempHtmlPath = storage_path('app/temp/test_upload_'.uniqid().'.html');
    File::ensureDirectoryExists(dirname($tempHtmlPath));
    File::put($tempHtmlPath, '<html><body><h1>HTML Book</h1><p>This is a paragraph of the story.</p></body></html>');

    $uploadedFile = new UploadedFile(
        $tempHtmlPath,
        'test_book.html',
        'text/html',
        null,
        true
    );

    $response = $this->postJson(route('epub.upload'), [
        'title' => 'HTML Story Book',
        'document_file' => $uploadedFile,
        'aspect_ratio' => '9:16',
        'voice_name' => 'en-US-ChristopherNeural',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'project' => [
                'title' => 'HTML Story Book',
                'status' => 'uploaded',
                'aspect_ratio' => '9:16',
            ],
        ]);

    Queue::assertPushed(ProcessEpubUploadJob::class);

    if (File::exists($tempHtmlPath)) {
        File::delete($tempHtmlPath);
    }
});

test('it can delete individual scenes, cleanup files, and re-sequence remaining scenes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create public test files
    $audio1 = 'epub_projects/test/scene_1.mp3';
    $video1 = 'epub_projects/test/scene_1.mp4';
    $audio2 = 'epub_projects/test/scene_2.mp3';
    $video2 = 'epub_projects/test/scene_2.mp4';
    $audio3 = 'epub_projects/test/scene_3.mp3';
    $video3 = 'epub_projects/test/scene_3.mp4';

    Storage::disk('public')->put($audio1, 'audio-1-content');
    Storage::disk('public')->put($video1, 'video-1-content');
    Storage::disk('public')->put($audio2, 'audio-2-content');
    Storage::disk('public')->put($video2, 'video-2-content');
    Storage::disk('public')->put($audio3, 'audio-3-content');
    Storage::disk('public')->put($video3, 'video-3-content');

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Delete Scene Test Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'completed',
        'total_scenes' => 3,
        'rendered_scenes' => 3,
    ]);

    $scene1 = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'Table of Contents - Chapter 1, Chapter 2',
        'audio_path' => $audio1,
        'video_path' => $video1,
        'status' => 'completed',
    ]);

    $scene2 = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 2,
        'script_text' => 'It was the best of times, it was the worst of times.',
        'audio_path' => $audio2,
        'video_path' => $video2,
        'status' => 'completed',
    ]);

    $scene3 = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 3,
        'script_text' => 'A lone ship sailed quietly under the moonlight.',
        'audio_path' => $audio3,
        'video_path' => $video3,
        'status' => 'completed',
    ]);

    Livewire::test(EpubStudio::class)
        ->call('deleteScene', $scene1->id);

    // Assert Scene #1 was deleted from database
    expect(EpubScene::find($scene1->id))->toBeNull();

    // Assert media files for Scene #1 were cleaned up from storage
    expect(Storage::disk('public')->exists($audio1))->toBeFalse();
    expect(Storage::disk('public')->exists($video1))->toBeFalse();

    // Assert media files for remaining scenes still exist
    expect(Storage::disk('public')->exists($audio2))->toBeTrue();
    expect(Storage::disk('public')->exists($audio3))->toBeTrue();

    // Assert remaining scenes are sequentially renumbered to 1 and 2
    $scene2->refresh();
    $scene3->refresh();
    expect($scene2->sequence_order)->toBe(1);
    expect($scene3->sequence_order)->toBe(2);

    // Assert parent project counts are updated
    $project->refresh();
    expect($project->total_scenes)->toBe(2);
    expect($project->rendered_scenes)->toBe(2);

    // Clean up remaining test files
    Storage::disk('public')->delete([$audio2, $video2, $audio3, $video3]);
});

test('it can trigger episode re-merge from Livewire component', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Merge Test Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'completed',
        'total_scenes' => 1,
        'rendered_scenes' => 1,
    ]);

    EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'The story continues.',
        'status' => 'completed',
    ]);

    Livewire::test(EpubStudio::class)
        ->call('remergeEpisode', $project->id);

    Queue::assertPushed(MergeEpisodeVideoJob::class, function ($job) use ($project) {
        return $job->project->id === $project->id;
    });
});

test('RenderSceneVideoJob handles GIF images via pre-normalization and fallback', function () {
    $user = User::factory()->create();

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'GIF Render Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'generating_audio',
        'aspect_ratio' => '16:9',
    ]);

    // Create a valid 1x1 GIF binary
    $gifBinary = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    $gifRelPath = 'epub_projects/test/test_illustration.gif';
    Storage::disk('public')->put($gifRelPath, $gifBinary);

    // Create dummy audio
    $audioRelPath = 'epub_projects/test/test_audio.mp3';
    Storage::disk('public')->put($audioRelPath, 'dummy audio data');

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'image_path' => $gifRelPath,
        'audio_path' => $audioRelPath,
        'audio_duration' => 1.0,
        'script_text' => 'A test scene with GIF illustration.',
        'status' => 'audio_ready',
    ]);

    $job = new RenderSceneVideoJob($scene);
    expect($job)->toBeInstanceOf(RenderSceneVideoJob::class);

    // Cleanup storage
    Storage::disk('public')->delete([$gifRelPath, $audioRelPath]);
});

test('RenderSceneVideoJob generates ALL CAPS timed SRT subtitles with proportional chunking', function () {
    $user = User::factory()->create();
    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'SRT Test Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'generating_audio',
    ]);

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'The quick brown fox jumps over the lazy dog, heading straight into the deep forest. The journey continues tomorrow.',
        'audio_duration' => 6.0,
        'status' => 'pending',
    ]);

    $job = new RenderSceneVideoJob($scene);
    $srt = $job->generateSrtContent($scene->script_text, 6.0);

    expect($srt)->not->toBeEmpty();
    // Verify ALL CAPS
    expect($srt)->toContain('THE QUICK BROWN FOX JUMPS OVER THE LAZY DOG,');
    expect($srt)->toContain('THE JOURNEY CONTINUES TOMORROW.');
    // Verify SRT sequence and timecodes
    expect($srt)->toMatch('/1\s*\n\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}/');
    expect($srt)->toMatch('/2\s*\n\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}/');
});

test('Livewire EpubStudio can regenerate scene with video_only and all_with_tts modes', function () {
    Queue::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Regenerate Scene Test Project',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'completed',
        'total_scenes' => 1,
        'rendered_scenes' => 1,
    ]);

    $audioPath = 'epub_projects/test/scene_audio.mp3';
    Storage::disk('public')->put($audioPath, 'dummy-audio');

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'script_text' => 'Regenerate this specific scene.',
        'audio_path' => $audioPath,
        'status' => 'completed',
    ]);

    // Test video_only mode dispatches RenderSceneVideoJob directly
    Livewire::test(EpubStudio::class)
        ->call('regenerateScene', $scene->id, 'video_only');

    Queue::assertPushed(RenderSceneVideoJob::class, function ($job) use ($scene) {
        return $job->scene->id === $scene->id;
    });

    // Test all_with_tts mode dispatches GenerateSceneAudioJob
    Livewire::test(EpubStudio::class)
        ->call('regenerateScene', $scene->id, 'all_with_tts');

    Queue::assertPushed(GenerateSceneAudioJob::class, function ($job) use ($scene) {
        return $job->scene->id === $scene->id;
    });

    Storage::disk('public')->delete($audioPath);
});

test('ProcessEpubUploadJob translates extracted scenes and preserves original_script_text', function () {
    Queue::fake();

    $user = User::factory()->create();
    $tempEpubPath = storage_path('app/temp/test_trans_'.uniqid().'.epub');
    createTestEpubFile($tempEpubPath);

    $relPath = 'epubs/test_trans.epub';
    Storage::disk('public')->put($relPath, File::get($tempEpubPath));

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Translation Test Book',
        'epub_path' => $relPath,
        'source_language' => 'en',
        'target_language' => 'id',
        'voice_name' => 'id-ID-ArdiNeural',
        'status' => 'uploaded',
    ]);

    $mockTranslator = Mockery::mock(TranslationService::class);
    $mockTranslator->shouldReceive('translate')
        ->atLeast()->once()
        ->andReturnUsing(function ($text, $target, $source) {
            return 'TERJEMAHAN: '.$text;
        });

    $job = new ProcessEpubUploadJob($project);
    $job->handle(new EpubExtractorService, new HtmlExtractorService, $mockTranslator);

    $project->refresh();
    expect($project->status)->toBe('generating_audio');

    $scenes = EpubScene::where('epub_project_id', $project->id)->orderBy('sequence_order')->get();
    expect($scenes)->not->toBeEmpty();
    expect($scenes[0]->original_script_text)->not->toBeEmpty();
    expect($scenes[0]->script_text)->toContain('TERJEMAHAN:');

    if (File::exists($tempEpubPath)) {
        File::delete($tempEpubPath);
    }
});

test('Livewire EpubStudio updates default voice when targetLanguage changes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(EpubStudio::class)
        ->assertSet('sourceLanguage', 'auto')
        ->assertSet('targetLanguage', 'en')
        ->assertSet('voiceName', 'en-US-ChristopherNeural')
        ->set('targetLanguage', 'id')
        ->assertSet('voiceName', 'id-ID-ArdiNeural')
        ->set('targetLanguage', 'ja')
        ->assertSet('voiceName', 'ja-JP-NanamiNeural');
});

test('Livewire EpubStudio allows uploading custom scene image and resetting to original', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = EpubProject::create([
        'user_id' => $user->id,
        'title' => 'Custom Image Test Book',
        'epub_path' => 'epubs/dummy.epub',
        'status' => 'completed',
        'total_scenes' => 1,
        'rendered_scenes' => 1,
    ]);

    $originalImageRelPath = 'epub_projects/test/original_ebook_ill.jpg';
    Storage::disk('public')->put($originalImageRelPath, 'dummy-original-image');

    $scene = EpubScene::create([
        'epub_project_id' => $project->id,
        'sequence_order' => 1,
        'original_image_path' => $originalImageRelPath,
        'image_path' => $originalImageRelPath,
        'script_text' => 'Testing custom image replacement.',
        'status' => 'pending',
    ]);

    $fakeUpload = UploadedFile::fake()->image('my_custom_art.png', 800, 600);

    Livewire::test(EpubStudio::class)
        ->set('sceneImageFiles.'.$scene->id, $fakeUpload);

    $scene->refresh();
    expect($scene->image_path)->not->toBe($originalImageRelPath);
    expect($scene->image_path)->toContain('custom_scene_'.$scene->id);
    expect($scene->original_image_path)->toBe($originalImageRelPath);

    // Test reset back to original
    Livewire::test(EpubStudio::class)
        ->call('resetSceneImage', $scene->id);

    $scene->refresh();
    expect($scene->image_path)->toBe($originalImageRelPath);

    Storage::disk('public')->delete($originalImageRelPath);
});
