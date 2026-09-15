<?php

namespace App\Console\Commands;

use App\Services\TextToSpeechService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class GenerateVoiceSamplesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voice:generate-samples {--voice= : Specific voice key to generate} {--force : Overwrite existing sample files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate MP3 audio sample files for all available TTS narrator voices';

    /**
     * Sample text requested by the user.
     */
    public const SAMPLE_TEXT = 'Proses ekstraksi dan verifikasi dokumen telah berhasil diselesaikan dengan tingkat akurasi 99,8 persen. Algoritma secara otomatis mengidentifikasi anomali data sebelum diunggah ke server utama.';

    /**
     * Execute the console command.
     */
    public function handle(TextToSpeechService $tts): int
    {
        $voices = TextToSpeechService::getDetailedVoiceCatalog();
        $targetVoice = $this->option('voice');
        $force = (bool) $this->option('force');

        if ($targetVoice) {
            $voices = array_filter($voices, fn ($v) => $v['key'] === $targetVoice);
            if (empty($voices)) {
                $this->error("Voice '{$targetVoice}' not found in catalog.");

                return 1;
            }
        }

        $outputDir = Storage::disk('public')->path('voice_samples');
        File::ensureDirectoryExists($outputDir);

        $this->info('🎙️ Synthesizing Voice Samples for '.count($voices).' voices...');
        $this->info('📝 Text: "'.self::SAMPLE_TEXT.'"');

        $bar = $this->output->createProgressBar(count($voices));
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        foreach ($voices as $voice) {
            $voiceKey = $voice['key'];
            $outputFile = "{$outputDir}/{$voiceKey}.mp3";

            if (File::exists($outputFile) && ! $force) {
                $successCount++;
                $bar->advance();

                continue;
            }

            try {
                $tts->synthesize(
                    text: self::SAMPLE_TEXT,
                    outputAudioAbsolutePath: $outputFile,
                    outputSubtitleAbsolutePath: null,
                    voice: $voiceKey
                );
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $this->newLine();
                $this->warn("⚠️ Failed synthesizing {$voiceKey}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Completed: {$successCount} samples generated in storage/app/public/voice_samples/");
        if ($failedCount > 0) {
            $this->warn("⚠️ {$failedCount} voices failed (e.g. language-specific pronunciation limits).");
        }

        return 0;
    }
}
