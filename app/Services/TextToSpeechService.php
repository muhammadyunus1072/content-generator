<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TextToSpeechService
{
    /**
     * Default speech rate adjustment for professional storyteller pacing (-15% slows down fast robotic delivery).
     */
    public const DEFAULT_RATE = '-15%';

    /**
     * Default pitch adjustment for deeper, authoritative narration voice (-10Hz).
     */
    public const DEFAULT_PITCH = '-10Hz';

    /**
     * Synthesize speech from text and write media + subtitle files.
     *
     * @return array{audio_path: string, subtitle_path: string|null, duration_seconds: float}
     */
    public function synthesize(
        string $text,
        string $outputAudioAbsolutePath,
        ?string $outputSubtitleAbsolutePath = null,
        string $voice = 'id-ID-ArdiNeural',
        string $provider = 'edge-tts',
        ?string $rate = null,
        ?string $pitch = null,
        ?string $baseUrl = null
    ): array {
        File::ensureDirectoryExists(dirname($outputAudioAbsolutePath));
        if ($outputSubtitleAbsolutePath) {
            File::ensureDirectoryExists(dirname($outputSubtitleAbsolutePath));
        }

        $normProvider = strtolower(str_replace('-', '_', $provider));

        if (in_array($normProvider, ['docker_openai', 'docker_tts', 'docker_kokoro', 'kokoro'])) {
            return $this->synthesizeWithDockerOpenAi(
                text: $text,
                audioPath: $outputAudioAbsolutePath,
                voice: $voice,
                baseUrl: $baseUrl ?: 'http://localhost:8880'
            );
        }

        if (in_array($normProvider, ['docker_cog', 'docker_replicate'])) {
            return $this->synthesizeWithDockerCog(
                text: $text,
                audioPath: $outputAudioAbsolutePath,
                voice: $voice,
                baseUrl: $baseUrl ?: 'http://localhost:5000'
            );
        }

        return $this->synthesizeWithEdgeTts(
            text: $text,
            audioPath: $outputAudioAbsolutePath,
            subtitlePath: $outputSubtitleAbsolutePath,
            voice: $voice,
            rate: $rate ?? self::DEFAULT_RATE,
            pitch: $pitch ?? self::DEFAULT_PITCH
        );
    }

    /**
     * Pre-process script text with dramatic punctuation to force natural storytelling pauses.
     *
     * 1. Replaces standard sentence-ending periods (. ) with ellipses (... ) for prolonged, cinematic pauses.
     * 2. Ensures commas have consistent spacing for rhythmic breathing.
     */
    public function applyDramaticPunctuation(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // 1. Normalize and space commas cleanly for natural breathing pauses
        $text = preg_replace('/\s*,\s*/u', ', ', $text) ?? $text;

        // 2. Replace standard sentence-ending periods (. followed by space or end of string) with ellipses (... )
        // Using lookbehind and lookahead to avoid expanding existing ellipses or floating decimals
        $text = preg_replace('/(?<!\.)\.(?!\.)(\s+|$)/u', '...$1', $text) ?? $text;

        // 3. Collapse accidental duplicate multi-dots (e.g., .... or ..... -> ...)
        $text = preg_replace('/\.{4,}/u', '...', $text) ?? $text;
        $text = preg_replace('/\s*\.{3}\s*/u', '... ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Synthesize using Edge-TTS python module with rate, pitch, and dramatic punctuation.
     *
     * @return array{audio_path: string, subtitle_path: string|null, duration_seconds: float}
     */
    protected function synthesizeWithEdgeTts(
        string $text,
        string $audioPath,
        ?string $subtitlePath,
        string $voice,
        string $rate = self::DEFAULT_RATE,
        string $pitch = self::DEFAULT_PITCH
    ): array {
        $dramaticText = $this->applyDramaticPunctuation($text);

        $tempTextFile = storage_path('app/temp/tts_'.uniqid().'.txt');
        File::ensureDirectoryExists(dirname($tempTextFile));
        File::put($tempTextFile, $dramaticText);

        $cmd = [
            'python',
            '-m',
            'edge_tts',
            '-v',
            $voice,
            '--rate',
            $rate,
            '--pitch',
            $pitch,
            '-f',
            $tempTextFile,
            '--write-media',
            $audioPath,
        ];

        if ($subtitlePath) {
            $cmd[] = '--write-subtitles';
            $cmd[] = $subtitlePath;
        }

        $process = new Process($cmd);
        $process->setTimeout(120);
        $process->setEnv([
            'PYTHONIOENCODING' => 'utf-8',
            'LANG' => 'en_US.UTF-8',
        ]);

        $process->run();

        if (File::exists($tempTextFile)) {
            File::delete($tempTextFile);
        }

        if (! $process->isSuccessful() || ! File::exists($audioPath)) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            Log::error('Edge-TTS execution failed', [
                'voice' => $voice,
                'rate' => $rate,
                'pitch' => $pitch,
                'error' => $errorOutput,
            ]);
            throw new Exception('Edge-TTS synthesis failed: '.$errorOutput);
        }

        $duration = $this->getAudioDuration($audioPath);

        return [
            'audio_path' => $audioPath,
            'subtitle_path' => $subtitlePath && File::exists($subtitlePath) ? $subtitlePath : null,
            'duration_seconds' => $duration,
        ];
    }

    /**
     * Probe the duration of the generated audio file using ffprobe.
     */
    public function getAudioDuration(string $audioPath): float
    {
        $ffprobeBin = env('FFPROBE_BINARIES', 'C:/ffmpeg/bin/ffprobe.exe');

        $cmd = [
            $ffprobeBin,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $audioPath,
        ];

        $process = new Process($cmd);
        $process->run();

        if ($process->isSuccessful()) {
            $duration = (float) trim($process->getOutput());
            if ($duration > 0) {
                return round($duration, 2);
            }
        }

        return 4.0;
    }

    /**
     * Get flat associative array of available voices for dropdowns.
     *
     * @return array<string, string>
     */
    public static function getAvailableVoices(): array
    {
        $detailed = self::getDetailedVoiceCatalog();
        $flat = [];

        foreach ($detailed as $voice) {
            $flat[$voice['key']] = "[{$voice['category']}] {$voice['name']} ({$voice['gender']}) - {$voice['personality']}";
        }

        return $flat;
    }

    /**
     * Get structured voice catalog with full metadata for showcases and audio players.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDetailedVoiceCatalog(): array
    {
        return [
            // --- INDONESIAN & REGIONAL NUSANTARA ---
            [
                'key' => 'id-ID-ArdiNeural',
                'name' => 'Ardi (Indonesian)',
                'locale' => 'id-ID',
                'language' => 'Bahasa Indonesia',
                'gender' => 'Male',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Berwibawa, Suara Berat, Dokumenter & Berita Formal',
                'is_featured' => true,
            ],
            [
                'key' => 'id-ID-GadisNeural',
                'name' => 'Gadis (Indonesian)',
                'locale' => 'id-ID',
                'language' => 'Bahasa Indonesia',
                'gender' => 'Female',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Jelas, Lembut, Ramah & Narasi Profesional',
                'is_featured' => true,
            ],
            [
                'key' => 'jv-ID-DimasNeural',
                'name' => 'Dimas (Javanese)',
                'locale' => 'jv-ID',
                'language' => 'Basa Jawa',
                'gender' => 'Male',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Logat Jawa Alus, Pria Ramah & Storyteller Budaya',
                'is_featured' => false,
            ],
            [
                'key' => 'jv-ID-SitiNeural',
                'name' => 'Siti (Javanese)',
                'locale' => 'jv-ID',
                'language' => 'Basa Jawa',
                'gender' => 'Female',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Logat Jawa Lembut, Wanita Santun',
                'is_featured' => false,
            ],
            [
                'key' => 'su-ID-JajangNeural',
                'name' => 'Jajang (Sundanese)',
                'locale' => 'su-ID',
                'language' => 'Basa Sunda',
                'gender' => 'Male',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Aksen Sunda Pria, Hangat & Ceria',
                'is_featured' => false,
            ],
            [
                'key' => 'su-ID-TutiNeural',
                'name' => 'Tuti (Sundanese)',
                'locale' => 'su-ID',
                'language' => 'Basa Sunda',
                'gender' => 'Female',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Aksen Sunda Wanita, Lembut & Merdu',
                'is_featured' => false,
            ],
            [
                'key' => 'ms-MY-OsmanNeural',
                'name' => 'Osman (Malay)',
                'locale' => 'ms-MY',
                'language' => 'Bahasa Melayu',
                'gender' => 'Male',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Melayu Formal Pria, Tegas & Positif',
                'is_featured' => false,
            ],
            [
                'key' => 'ms-MY-YasminNeural',
                'name' => 'Yasmin (Malay)',
                'locale' => 'ms-MY',
                'language' => 'Bahasa Melayu',
                'gender' => 'Female',
                'category' => 'Indonesian & Nusantara',
                'personality' => 'Melayu Wanita Ramah & Natural',
                'is_featured' => false,
            ],

            // --- ENGLISH MULTILINGUAL & TOP STORYTELLERS ---
            [
                'key' => 'en-US-AndrewMultilingualNeural',
                'name' => 'Andrew (Multilingual)',
                'locale' => 'en-US',
                'language' => 'English (Multilingual AI)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Warm, Confident, Authentic, Deep Narrator',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-AvaMultilingualNeural',
                'name' => 'Ava (Multilingual)',
                'locale' => 'en-US',
                'language' => 'English (Multilingual AI)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Expressive, Caring, Modern, Clear Narration',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-BrianMultilingualNeural',
                'name' => 'Brian (Multilingual)',
                'locale' => 'en-US',
                'language' => 'English (Multilingual AI)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Approachable, Casual, Sincere Storyteller',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-EmmaMultilingualNeural',
                'name' => 'Emma (Multilingual)',
                'locale' => 'en-US',
                'language' => 'English (Multilingual AI)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Cheerful, Conversational, Dynamic YouTube Voice',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-ChristopherNeural',
                'name' => 'Christopher',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Action Movie Trailer, Epic Authority, Dramatic',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-GuyNeural',
                'name' => 'Guy',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Passionate Storyteller, Webtoon & Fantasy Novels',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-JennyNeural',
                'name' => 'Jenny',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Friendly, Considerate, Comfort Audiobook Voice',
                'is_featured' => true,
            ],
            [
                'key' => 'en-US-AriaNeural',
                'name' => 'Aria',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Positive, Confident, Modern News & Novel',
                'is_featured' => false,
            ],
            [
                'key' => 'en-US-EricNeural',
                'name' => 'Eric',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Rational, Thoughtful, Analytical Documentaries',
                'is_featured' => false,
            ],
            [
                'key' => 'en-US-RogerNeural',
                'name' => 'Roger',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Lively, Humorous, Dynamic Story Narration',
                'is_featured' => false,
            ],
            [
                'key' => 'en-US-AnaNeural',
                'name' => 'Ana',
                'locale' => 'en-US',
                'language' => 'English (US)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Cute, Cartoon, Youthful Character',
                'is_featured' => false,
            ],
            [
                'key' => 'en-GB-RyanNeural',
                'name' => 'Ryan (British)',
                'locale' => 'en-GB',
                'language' => 'English (UK)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Classic BBC Style British Documentary Narrator',
                'is_featured' => true,
            ],
            [
                'key' => 'en-GB-SoniaNeural',
                'name' => 'Sonia (British)',
                'locale' => 'en-GB',
                'language' => 'English (UK)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Elegant, Sophisticated British Storyteller',
                'is_featured' => true,
            ],
            [
                'key' => 'en-GB-ThomasNeural',
                'name' => 'Thomas (British)',
                'locale' => 'en-GB',
                'language' => 'English (UK)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Formal UK News, Trustworthy & Clear',
                'is_featured' => false,
            ],
            [
                'key' => 'en-AU-WilliamMultilingualNeural',
                'name' => 'William (Australian)',
                'locale' => 'en-AU',
                'language' => 'English (Australia)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Bold Australian Accent, Adventurous Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'en-AU-NatashaNeural',
                'name' => 'Natasha (Australian)',
                'locale' => 'en-AU',
                'language' => 'English (Australia)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Warm, Bright, Friendly Aussie Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'en-SG-WayneNeural',
                'name' => 'Wayne (Singapore)',
                'locale' => 'en-SG',
                'language' => 'English (Singapore)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Clear Asian English Male Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'en-SG-LunaNeural',
                'name' => 'Luna (Singapore)',
                'locale' => 'en-SG',
                'language' => 'English (Singapore)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Professional Asian English Female Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'en-PH-JamesNeural',
                'name' => 'James (Philippines)',
                'locale' => 'en-PH',
                'language' => 'English (Philippines)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Warm Southeast Asian English Male',
                'is_featured' => false,
            ],
            [
                'key' => 'en-PH-RosaNeural',
                'name' => 'Rosa (Philippines)',
                'locale' => 'en-PH',
                'language' => 'English (Philippines)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Gentle Southeast Asian English Female',
                'is_featured' => false,
            ],
            [
                'key' => 'en-IN-PrabhatNeural',
                'name' => 'Prabhat (Indian)',
                'locale' => 'en-IN',
                'language' => 'English (India)',
                'gender' => 'Male',
                'category' => 'English & Multilingual',
                'personality' => 'Clear Indian English Male Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'en-IN-NeerjaExpressiveNeural',
                'name' => 'Neerja (Indian)',
                'locale' => 'en-IN',
                'language' => 'English (India)',
                'gender' => 'Female',
                'category' => 'English & Multilingual',
                'personality' => 'Expressive Indian English Storyteller',
                'is_featured' => false,
            ],

            // --- ASIAN ANIME, MANHWA & DRAMA NARRATORS ---
            [
                'key' => 'ja-JP-KeitaNeural',
                'name' => 'Keita (Japanese)',
                'locale' => 'ja-JP',
                'language' => '日本語 (Japanese)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Anime Recap Narrator, Energetic Shonen Male',
                'is_featured' => true,
            ],
            [
                'key' => 'ja-JP-NanamiNeural',
                'name' => 'Nanami (Japanese)',
                'locale' => 'ja-JP',
                'language' => '日本語 (Japanese)',
                'gender' => 'Female',
                'category' => 'Anime & Asian Media',
                'personality' => 'Anime Heroine, Emotional Light Novel Storyteller',
                'is_featured' => true,
            ],
            [
                'key' => 'ko-KR-InJoonNeural',
                'name' => 'InJoon (Korean)',
                'locale' => 'ko-KR',
                'language' => '한국어 (Korean)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Manhwa Dramatic Recap, Action Webtoon Male',
                'is_featured' => true,
            ],
            [
                'key' => 'ko-KR-SunHiNeural',
                'name' => 'SunHi (Korean)',
                'locale' => 'ko-KR',
                'language' => '한국어 (Korean)',
                'gender' => 'Female',
                'category' => 'Anime & Asian Media',
                'personality' => 'K-Drama & Webtoon Romance Emotional Narrator',
                'is_featured' => true,
            ],
            [
                'key' => 'ko-KR-HyunsuMultilingualNeural',
                'name' => 'Hyunsu (Korean Multilingual)',
                'locale' => 'ko-KR',
                'language' => '한국어 (Korean Multilingual)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Deep Manhwa Protagonist Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'zh-CN-YunxiNeural',
                'name' => 'Yunxi (Chinese)',
                'locale' => 'zh-CN',
                'language' => '中文 (Mandarin)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Donghua / Xianxia Novel Sunshine Male',
                'is_featured' => true,
            ],
            [
                'key' => 'zh-CN-XiaoxiaoNeural',
                'name' => 'Xiaoxiao (Chinese)',
                'locale' => 'zh-CN',
                'language' => '中文 (Mandarin)',
                'gender' => 'Female',
                'category' => 'Anime & Asian Media',
                'personality' => 'Web Novel Warm Storyteller, Audio Drama',
                'is_featured' => true,
            ],
            [
                'key' => 'zh-CN-YunjianNeural',
                'name' => 'Yunjian (Chinese)',
                'locale' => 'zh-CN',
                'language' => '中文 (Mandarin)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Action & Battle Passionate Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'zh-HK-WanLungNeural',
                'name' => 'WanLung (Cantonese)',
                'locale' => 'zh-HK',
                'language' => '粵語 (Cantonese)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Classic Hong Kong Cinema Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'zh-TW-YunJheNeural',
                'name' => 'YunJhe (Taiwan)',
                'locale' => 'zh-TW',
                'language' => '國語 (Taiwanese Mandarin)',
                'gender' => 'Male',
                'category' => 'Anime & Asian Media',
                'personality' => 'Smooth, Relaxed Mandarin Narrator',
                'is_featured' => false,
            ],

            // --- EUROPEAN & GLOBAL NARRATORS ---
            [
                'key' => 'de-DE-FlorianMultilingualNeural',
                'name' => 'Florian (German Multilingual)',
                'locale' => 'de-DE',
                'language' => 'Deutsch (German)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Deep, Strong German Narrative Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'de-DE-KatjaNeural',
                'name' => 'Katja (German)',
                'locale' => 'de-DE',
                'language' => 'Deutsch (German)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Clear, Positive German Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'fr-FR-RemyMultilingualNeural',
                'name' => 'Remy (French Multilingual)',
                'locale' => 'fr-FR',
                'language' => 'Français (French)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Sophisticated, Elegant French Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'fr-FR-DeniseNeural',
                'name' => 'Denise (French)',
                'locale' => 'fr-FR',
                'language' => 'Français (French)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Artistic, Expressive French Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'es-ES-AlvaroNeural',
                'name' => 'Alvaro (Spanish Spain)',
                'locale' => 'es-ES',
                'language' => 'Español (Castilian)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Dramatic Iberian Spanish Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'es-ES-ElviraNeural',
                'name' => 'Elvira (Spanish Spain)',
                'locale' => 'es-ES',
                'language' => 'Español (Castilian)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Warm, Melodic Castilian Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'es-MX-JorgeNeural',
                'name' => 'Jorge (Spanish Mexico)',
                'locale' => 'es-MX',
                'language' => 'Español (Latin America)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Cinematic Latin American Spanish Male',
                'is_featured' => false,
            ],
            [
                'key' => 'es-MX-DaliaNeural',
                'name' => 'Dalia (Spanish Mexico)',
                'locale' => 'es-MX',
                'language' => 'Español (Latin America)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Enthusiastic Latin American Female',
                'is_featured' => false,
            ],
            [
                'key' => 'it-IT-GiuseppeMultilingualNeural',
                'name' => 'Giuseppe (Italian Multilingual)',
                'locale' => 'it-IT',
                'language' => 'Italiano (Italian)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Passionate, Melodic Italian Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'it-IT-ElsaNeural',
                'name' => 'Elsa (Italian)',
                'locale' => 'it-IT',
                'language' => 'Italiano (Italian)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Smooth, Classical Italian Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'pt-BR-ThalitaMultilingualNeural',
                'name' => 'Thalita (Portuguese Brazil)',
                'locale' => 'pt-BR',
                'language' => 'Português (Brasil)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Vibrant Brazilian Portuguese Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'pt-BR-AntonioNeural',
                'name' => 'Antonio (Portuguese Brazil)',
                'locale' => 'pt-BR',
                'language' => 'Português (Brasil)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Warm Brazilian Portuguese Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'ru-RU-DmitryNeural',
                'name' => 'Dmitry (Russian)',
                'locale' => 'ru-RU',
                'language' => 'Русский (Russian)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Deep Epic Russian Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'ru-RU-SvetlanaNeural',
                'name' => 'Svetlana (Russian)',
                'locale' => 'ru-RU',
                'language' => 'Русский (Russian)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Clear, Expressive Russian Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'ar-SA-HamedNeural',
                'name' => 'Hamed (Arabic)',
                'locale' => 'ar-SA',
                'language' => 'العربية (Arabic)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Classical Eloquent Arabic Male',
                'is_featured' => false,
            ],
            [
                'key' => 'ar-SA-ZariyahNeural',
                'name' => 'Zariyah (Arabic)',
                'locale' => 'ar-SA',
                'language' => 'العربية (Arabic)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Graceful Arabic Female Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'tr-TR-AhmetNeural',
                'name' => 'Ahmet (Turkish)',
                'locale' => 'tr-TR',
                'language' => 'Türkçe (Turkish)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Authoritative Turkish Documentary Male',
                'is_featured' => false,
            ],
            [
                'key' => 'tr-TR-EmelNeural',
                'name' => 'Emel (Turkish)',
                'locale' => 'tr-TR',
                'language' => 'Türkçe (Turkish)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Emotional Turkish Drama Voice',
                'is_featured' => false,
            ],
            [
                'key' => 'th-TH-NiwatNeural',
                'name' => 'Niwat (Thai)',
                'locale' => 'th-TH',
                'language' => 'ไทย (Thai)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Thai Novel & Action Male Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'th-TH-PremwadeeNeural',
                'name' => 'Premwadee (Thai)',
                'locale' => 'th-TH',
                'language' => 'ไทย (Thai)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Gentle Thai Storyteller',
                'is_featured' => false,
            ],
            [
                'key' => 'vi-VN-NamMinhNeural',
                'name' => 'NamMinh (Vietnamese)',
                'locale' => 'vi-VN',
                'language' => 'Tiếng Việt (Vietnamese)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Vietnamese Audio Novel Male',
                'is_featured' => false,
            ],
            [
                'key' => 'vi-VN-HoaiMyNeural',
                'name' => 'HoaiMy (Vietnamese)',
                'locale' => 'vi-VN',
                'language' => 'Tiếng Việt (Vietnamese)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Vietnamese Audio Novel Female',
                'is_featured' => false,
            ],
            [
                'key' => 'hi-IN-MadhurNeural',
                'name' => 'Madhur (Hindi)',
                'locale' => 'hi-IN',
                'language' => 'हिन्दी (Hindi)',
                'gender' => 'Male',
                'category' => 'European & Global',
                'personality' => 'Hindi Epic & Mythology Male Narrator',
                'is_featured' => false,
            ],
            [
                'key' => 'hi-IN-SwaraNeural',
                'name' => 'Swara (Hindi)',
                'locale' => 'hi-IN',
                'language' => 'हिन्दी (Hindi)',
                'gender' => 'Female',
                'category' => 'European & Global',
                'personality' => 'Hindi Storyteller & Emotional Female',
                'is_featured' => false,
            ],
        ];
    }

    /**
     * Synthesize using OpenAI-compatible TTS endpoint (e.g. Kokoro-FastAPI, LocalAI).
     *
     * @return array{audio_path: string, subtitle_path: null, duration_seconds: float}
     */
    public function synthesizeWithDockerOpenAi(
        string $text,
        string $audioPath,
        string $voice = 'af_heart',
        string $baseUrl = 'http://localhost:8880'
    ): array {
        $baseUrl = rtrim($baseUrl ?: 'http://localhost:8880', '/');
        $endpoint = str_ends_with($baseUrl, '/v1/audio/speech') ? $baseUrl : "{$baseUrl}/v1/audio/speech";

        $response = Http::timeout(60)
            ->post($endpoint, [
                'model' => 'kokoro',
                'input' => $text,
                'voice' => $voice,
            ]);

        if (! $response->successful()) {
            throw new Exception("Local Docker OpenAI TTS failed (HTTP {$response->status()}): ".$response->body());
        }

        File::put($audioPath, $response->body());
        $duration = $this->getAudioDuration($audioPath);

        return [
            'audio_path' => $audioPath,
            'subtitle_path' => null,
            'duration_seconds' => $duration,
        ];
    }

    /**
     * Synthesize using Replicate Cog prediction endpoint (e.g. localhost:5000/predictions).
     *
     * @return array{audio_path: string, subtitle_path: null, duration_seconds: float}
     */
    public function synthesizeWithDockerCog(
        string $text,
        string $audioPath,
        string $voice = 'af_heart',
        string $baseUrl = 'http://localhost:5000'
    ): array {
        $baseUrl = rtrim($baseUrl ?: 'http://localhost:5000', '/');
        $endpoint = str_ends_with($baseUrl, '/predictions') ? $baseUrl : "{$baseUrl}/predictions";

        $response = Http::timeout(60)
            ->post($endpoint, [
                'input' => [
                    'text' => $text,
                    'voice' => $voice,
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception("Local Docker Cog TTS failed (HTTP {$response->status()}): ".$response->body());
        }

        $output = $response->json('output');
        if (is_string($output) && str_starts_with($output, 'data:audio')) {
            $base64Data = substr($output, strpos($output, ',') + 1);
            File::put($audioPath, base64_decode($base64Data));
        } elseif (is_string($output) && (str_starts_with($output, 'http://') || str_starts_with($output, 'https://'))) {
            $download = Http::timeout(30)->get($output);
            File::put($audioPath, $download->body());
        } elseif (is_array($output) && isset($output[0]) && is_string($output[0])) {
            $first = $output[0];
            if (str_starts_with($first, 'data:audio')) {
                $base64Data = substr($first, strpos($first, ',') + 1);
                File::put($audioPath, base64_decode($base64Data));
            } else {
                $download = Http::timeout(30)->get($first);
                File::put($audioPath, $download->body());
            }
        } else {
            File::put($audioPath, $response->body());
        }

        $duration = $this->getAudioDuration($audioPath);

        return [
            'audio_path' => $audioPath,
            'subtitle_path' => null,
            'duration_seconds' => $duration,
        ];
    }

    /**
     * Parse WebVTT subtitle text into an array of structured cue cards.
     *
     * @return array<int, array{index: int, start: float, end: float, start_formatted: string, end_formatted: string, text: string}>
     */
    public static function parseVttToCues(string $vttContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($vttContent)) ?: [];
        $cues = [];
        $currentCue = null;
        $index = 1;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === 'WEBVTT' || str_starts_with($line, 'NOTE')) {
                continue;
            }

            if (preg_match('/((?:\d{2}:)?\d{2}:\d{2}[\.,]\d{3})\s*-->\s*((?:\d{2}:)?\d{2}:\d{2}[\.,]\d{3})/', $line, $matches)) {
                if ($currentCue && ! empty($currentCue['text'])) {
                    $cues[] = $currentCue;
                }

                $startSec = self::timestampToSeconds($matches[1]);
                $endSec = self::timestampToSeconds($matches[2]);

                $currentCue = [
                    'index' => $index++,
                    'start' => $startSec,
                    'end' => $endSec,
                    'start_formatted' => self::formatCueTime($startSec),
                    'end_formatted' => self::formatCueTime($endSec),
                    'text' => '',
                ];
            } elseif ($currentCue && ! is_numeric($line)) {
                $cleanedText = strip_tags($line);
                $currentCue['text'] = trim($currentCue['text'].' '.$cleanedText);
            }
        }

        if ($currentCue && ! empty($currentCue['text'])) {
            $cues[] = $currentCue;
        }

        return $cues;
    }

    /**
     * Convert WebVTT content into SubRip (.srt) subtitle string.
     */
    public static function convertVttToSrt(string $vttContent): string
    {
        $cues = self::parseVttToCues($vttContent);
        $srt = '';

        foreach ($cues as $cue) {
            $startFormatted = self::formatSrtTime($cue['start']);
            $endFormatted = self::formatSrtTime($cue['end']);
            $srt .= "{$cue['index']}\n{$startFormatted} --> {$endFormatted}\n{$cue['text']}\n\n";
        }

        return trim($srt);
    }

    /**
     * Convert timestamp string (00:01:23.456 or 01:23.456) to total seconds as float.
     */
    public static function timestampToSeconds(string $timestamp): float
    {
        $timestamp = str_replace(',', '.', trim($timestamp));
        $parts = explode(':', $timestamp);

        if (count($parts) === 3) {
            return ((float) $parts[0] * 3600) + ((float) $parts[1] * 60) + (float) $parts[2];
        }

        if (count($parts) === 2) {
            return ((float) $parts[0] * 60) + (float) $parts[1];
        }

        return (float) $timestamp;
    }

    /**
     * Format seconds to readable mm:ss.ms.
     */
    public static function formatCueTime(float $seconds): string
    {
        $mins = floor($seconds / 60);
        $secs = $seconds - ($mins * 60);

        return sprintf('%02d:%05.2f', $mins, $secs);
    }

    /**
     * Format seconds to standard SRT format (HH:MM:SS,mmm).
     */
    public static function formatSrtTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $millis = round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $hours, $mins, $secs, $millis);
    }

    /**
     * Catalog of local Docker Kokoro-82M voices.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDockerVoices(): array
    {
        return [
            ['key' => 'af_heart', 'name' => 'Heart (American Female)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Female', 'personality' => 'Warm, Natural & Expressive ⭐ Top Pick', 'is_featured' => true],
            ['key' => 'af_bella', 'name' => 'Bella (American Female)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Female', 'personality' => 'Energetic & Upbeat', 'is_featured' => true],
            ['key' => 'af_sarah', 'name' => 'Sarah (American Female)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Female', 'personality' => 'Clear & Professional', 'is_featured' => false],
            ['key' => 'am_adam', 'name' => 'Adam (American Male)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Male', 'personality' => 'Calm, Deep Voice & Documentary', 'is_featured' => true],
            ['key' => 'am_michael', 'name' => 'Michael (American Male)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Male', 'personality' => 'Authoritative & Storyteller', 'is_featured' => false],
            ['key' => 'bf_alice', 'name' => 'Alice (British Female)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Female', 'personality' => 'Refined, Eloquent British Accent', 'is_featured' => false],
            ['key' => 'bm_george', 'name' => 'George (British Male)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Male', 'personality' => 'Classic BBC Style English Narrator', 'is_featured' => false],
            ['key' => 'jf_alpha', 'name' => 'Alpha (Japanese Female)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Female', 'personality' => 'Sweet Anime Dialogue Style', 'is_featured' => false],
            ['key' => 'jm_kento', 'name' => 'Kento (Japanese Male)', 'category' => 'Docker Kokoro-82M', 'gender' => 'Male', 'personality' => 'Heroic Shonen Anime Style', 'is_featured' => false],
        ];
    }
}
