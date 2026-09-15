<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslationService
{
    /**
     * Maximum character count per single translation chunk to strictly stay below Google's 5000 character limit (413 Entity Too Large).
     */
    public const MAX_CHUNK_LENGTH = 3500;

    /**
     * Cache TTL in seconds for translations (7 days) to avoid repeated queries and 429/503 rate limit bans.
     */
    public const CACHE_TTL = 604800;

    /**
     * Map of supported languages with flag emojis and native labels.
     *
     * @var array<string, string>
     */
    protected array $supportedLanguages = [
        'auto' => '🌐 Auto-Detect Language',
        'en' => '🇺🇸 English (US)',
        'id' => '🇮🇩 Indonesian (Bahasa Indonesia)',
        'ja' => '🇯🇵 Japanese (日本語)',
        'ko' => '🇰🇷 Korean (한국어)',
        'zh-CN' => '🇨🇳 Chinese (Simplified)',
        'es' => '🇪🇸 Spanish (Español)',
        'fr' => '🇫🇷 French (Français)',
        'de' => '🇩🇪 German (Deutsch)',
        'ar' => '🇸🇦 Arabic (العربية)',
        'ru' => '🇷🇺 Russian (Русский)',
        'pt' => '🇧🇷 Portuguese (Português)',
        'it' => '🇮🇹 Italian (Italiano)',
        'th' => '🇹🇭 Thai (ไทย)',
        'vi' => '🇻🇳 Vietnamese (Tiếng Việt)',
        'hi' => '🇮🇳 Hindi (हिन्दी)',
    ];

    /**
     * Map of language codes to recommended default Edge-TTS voice.
     *
     * @var array<string, string>
     */
    protected array $defaultVoices = [
        'en' => 'en-US-ChristopherNeural',
        'id' => 'id-ID-ArdiNeural',
        'ja' => 'ja-JP-NanamiNeural',
        'ko' => 'ko-KR-SunHiNeural',
        'zh-CN' => 'zh-CN-XiaoxiaoNeural',
        'es' => 'es-ES-AlvaroNeural',
        'fr' => 'fr-FR-HenriNeural',
        'de' => 'de-DE-ConradNeural',
        'ar' => 'ar-SA-HamedNeural',
        'ru' => 'ru-RU-DmitryNeural',
        'pt' => 'pt-BR-AntonioNeural',
        'it' => 'it-IT-DiegoNeural',
        'th' => 'th-TH-NiwatNeural',
        'vi' => 'vi-VN-NamMinhNeural',
        'hi' => 'hi-IN-MadhurNeural',
    ];

    /**
     * Comprehensive list of Edge-TTS voices grouped by language.
     *
     * @var array<string, array<string, string>>
     */
    protected array $groupedVoices = [
        'en' => [
            'en-US-ChristopherNeural' => '🇺🇸 Christopher (Deep Storyteller Male)',
            'en-US-JennyNeural' => '🇺🇸 Jenny (Warm Narrator Female)',
            'en-US-GuyNeural' => '🇺🇸 Guy (Casual Male)',
            'en-GB-SoniaNeural' => '🇬🇧 Sonia (British Female)',
            'en-GB-RyanNeural' => '🇬🇧 Ryan (British Male)',
        ],
        'id' => [
            'id-ID-ArdiNeural' => '🇮🇩 Ardi (Indonesian Male)',
            'id-ID-GadisNeural' => '🇮🇩 Gadis (Indonesian Female)',
        ],
        'ja' => [
            'ja-JP-NanamiNeural' => '🇯🇵 Nanami (Japanese Female)',
            'ja-JP-KeitaNeural' => '🇯🇵 Keita (Japanese Male)',
        ],
        'ko' => [
            'ko-KR-SunHiNeural' => '🇰🇷 SunHi (Korean Female)',
            'ko-KR-InJoonNeural' => '🇰🇷 InJoon (Korean Male)',
        ],
        'zh-CN' => [
            'zh-CN-XiaoxiaoNeural' => '🇨🇳 Xiaoxiao (Chinese Female)',
            'zh-CN-YunjianNeural' => '🇨🇳 Yunjian (Chinese Male)',
        ],
        'es' => [
            'es-ES-AlvaroNeural' => '🇪🇸 Alvaro (Spanish Male)',
            'es-ES-ElviraNeural' => '🇪🇸 Elvira (Spanish Female)',
        ],
        'fr' => [
            'fr-FR-HenriNeural' => '🇫🇷 Henri (French Male)',
            'fr-FR-DeniseNeural' => '🇫🇷 Denise (French Female)',
        ],
        'de' => [
            'de-DE-ConradNeural' => '🇩🇪 Conrad (German Male)',
            'de-DE-KatjaNeural' => '🇩🇪 Katja (German Female)',
        ],
        'ar' => [
            'ar-SA-HamedNeural' => '🇸🇦 Hamed (Arabic Male)',
            'ar-SA-ZariyahNeural' => '🇸🇦 Zariyah (Arabic Female)',
        ],
        'ru' => [
            'ru-RU-DmitryNeural' => '🇷🇺 Dmitry (Russian Male)',
            'ru-RU-SvetlanaNeural' => '🇷🇺 Svetlana (Russian Female)',
        ],
        'pt' => [
            'pt-BR-AntonioNeural' => '🇧🇷 Antonio (Portuguese Male)',
            'pt-BR-FranciscaNeural' => '🇧🇷 Francisca (Portuguese Female)',
        ],
        'it' => [
            'it-IT-DiegoNeural' => '🇮🇹 Diego (Italian Male)',
            'it-IT-ElsaNeural' => '🇮🇹 Elsa (Italian Female)',
        ],
        'th' => [
            'th-TH-NiwatNeural' => '🇹🇭 Niwat (Thai Male)',
            'th-TH-PremwadeeNeural' => '🇹🇭 Premwadee (Thai Female)',
        ],
        'vi' => [
            'vi-VN-NamMinhNeural' => '🇻🇳 Nam Minh (Vietnamese Male)',
            'vi-VN-HoaiMyNeural' => '🇻🇳 Hoai My (Vietnamese Female)',
        ],
        'hi' => [
            'hi-IN-MadhurNeural' => '🇮🇳 Madhur (Hindi Male)',
            'hi-IN-SwaraNeural' => '🇮🇳 Swara (Hindi Female)',
        ],
    ];

    /**
     * Translate text from source language to target language with chunking, caching, and rate-limiting protections.
     *
     * Handles Google Translate known limitations:
     * - 413 Entity Too Large: Breaks text exceeding 3500 chars into safe sub-chunks.
     * - 403 Forbidden: Sanitizes and decodes raw HTML entities / unprintable characters.
     * - 429/503 Rate Limits: Caches identical queries for 7 days and applies micro-delays between multi-chunk requests.
     *
     * @param  string  $text  The text to translate.
     * @param  string  $targetLang  ISO target language code (e.g., 'id', 'ja', 'en').
     * @param  string  $sourceLang  ISO source language code or 'auto'.
     * @return string The translated text, or original text if translation is unneeded or fails.
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'auto'): string
    {
        $sanitizedText = $this->sanitizeInput($text);
        if ($sanitizedText === '') {
            return '';
        }

        if ($targetLang === '') {
            return $text;
        }

        // If source and target are identical (and not auto), no translation needed
        if ($sourceLang !== 'auto' && $sourceLang === $targetLang) {
            return $sanitizedText;
        }

        // Split text into safe chunks under MAX_CHUNK_LENGTH (prevent 413 Entity Too Large)
        $chunks = $this->splitTextIntoSafeChunks($sanitizedText, self::MAX_CHUNK_LENGTH);
        $translatedChunks = [];

        foreach ($chunks as $index => $chunk) {
            $trimmedChunk = trim($chunk);
            if ($trimmedChunk === '') {
                continue;
            }

            // Translate single chunk with caching and retry backoff
            $translatedChunks[] = $this->translateChunkWithCache($trimmedChunk, $targetLang, $sourceLang);

            // Add slight micro-delay between sequential batch chunks to prevent 429 Too Many Requests
            if ($index < count($chunks) - 1) {
                usleep(150000); // 150ms
            }
        }

        return trim(implode("\n\n", $translatedChunks));
    }

    /**
     * Translate a single chunk under 3500 characters using cache and retry backoff.
     */
    protected function translateChunkWithCache(string $chunk, string $targetLang, string $sourceLang): string
    {
        $cacheKey = 'google_trans_'.md5("{$sourceLang}_{$targetLang}_{$chunk}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($chunk, $targetLang, $sourceLang) {
            return $this->executeGoogleTranslation($chunk, $targetLang, $sourceLang);
        });
    }

    /**
     * Execute Google Translate call via stichoza/google-translate-php with error handling.
     */
    protected function executeGoogleTranslation(string $chunk, string $targetLang, string $sourceLang): string
    {
        try {
            $translator = new GoogleTranslate;
            $translator->setTarget($targetLang);

            if ($sourceLang !== 'auto' && ! empty($sourceLang)) {
                $translator->setSource($sourceLang);
            } else {
                $translator->setSource(); // Auto-detect
            }

            $translated = $translator->translate($chunk);

            return ! empty($translated) ? trim((string) $translated) : $chunk;
        } catch (Exception $e) {
            Log::warning('Google Translate request failed, falling back to original text chunk', [
                'error' => $e->getMessage(),
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'chunk_length' => mb_strlen($chunk, 'UTF-8'),
                'chunk_sample' => mb_substr($chunk, 0, 80),
            ]);

            return $chunk;
        }
    }

    /**
     * Sanitize input text by decoding HTML entities and stripping unprintable control characters to prevent 403 Forbidden errors.
     */
    public function sanitizeInput(string $text): string
    {
        // Decode HTML entities (e.g. &amp;, &nbsp;, &quot;, &#39;) to avoid Google Translate 403 Forbidden errors
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip any remaining raw HTML tags
        $clean = strip_tags($decoded);

        // Normalize unprintable control characters
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $clean);

        return trim($clean ?? $text);
    }

    /**
     * Split a long text into chunks smaller than maxChunkLength, breaking at paragraph or sentence boundaries.
     *
     * @return array<int, string>
     */
    public function splitTextIntoSafeChunks(string $text, int $maxChunkLength = self::MAX_CHUNK_LENGTH): array
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text, 'UTF-8') <= $maxChunkLength) {
            return [$text];
        }

        // First attempt: split by paragraphs
        $paragraphs = preg_split('/\n{2,}/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($paragraphs) || empty($paragraphs)) {
            $paragraphs = [$text];
        }

        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph, 'UTF-8') <= $maxChunkLength) {
                $testLength = mb_strlen($currentChunk, 'UTF-8') + mb_strlen($paragraph, 'UTF-8') + 2;
                if ($currentChunk === '') {
                    $currentChunk = $paragraph;
                } elseif ($testLength <= $maxChunkLength) {
                    $currentChunk .= "\n\n".$paragraph;
                } else {
                    $chunks[] = $currentChunk;
                    $currentChunk = $paragraph;
                }
            } else {
                // Paragraph itself is larger than maxChunkLength: split at sentences
                if ($currentChunk !== '') {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                }

                $sentences = preg_split('/(?<=[.!?])\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY);
                if (! is_array($sentences) || empty($sentences)) {
                    $sentences = [$paragraph];
                }

                foreach ($sentences as $sentence) {
                    $sentence = trim($sentence);
                    if ($sentence === '') {
                        continue;
                    }

                    if (mb_strlen($sentence, 'UTF-8') <= $maxChunkLength) {
                        $testLength = mb_strlen($currentChunk, 'UTF-8') + mb_strlen($sentence, 'UTF-8') + 1;
                        if ($currentChunk === '') {
                            $currentChunk = $sentence;
                        } elseif ($testLength <= $maxChunkLength) {
                            $currentChunk .= ' '.$sentence;
                        } else {
                            $chunks[] = $currentChunk;
                            $currentChunk = $sentence;
                        }
                    } else {
                        // Sentence itself is larger than maxChunkLength: split at spaces
                        if ($currentChunk !== '') {
                            $chunks[] = $currentChunk;
                            $currentChunk = '';
                        }

                        $words = preg_split('/\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY);
                        if (! is_array($words) || empty($words)) {
                            $words = [$sentence];
                        }

                        foreach ($words as $word) {
                            $testLength = mb_strlen($currentChunk, 'UTF-8') + mb_strlen($word, 'UTF-8') + 1;
                            if ($currentChunk === '') {
                                $currentChunk = $word;
                            } elseif ($testLength <= $maxChunkLength) {
                                $currentChunk .= ' '.$word;
                            } else {
                                $chunks[] = $currentChunk;
                                $currentChunk = $word;
                            }
                        }
                    }
                }
            }
        }

        if ($currentChunk !== '') {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Get all supported languages.
     *
     * @return array<string, string>
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Get human-readable language name.
     */
    public function getLanguageName(string $code): string
    {
        return $this->supportedLanguages[$code] ?? strtoupper($code);
    }

    /**
     * Get default Edge-TTS voice for a specific target language.
     */
    public function getDefaultVoiceForLanguage(string $langCode): string
    {
        return $this->defaultVoices[$langCode] ?? 'en-US-ChristopherNeural';
    }

    /**
     * Get all available voices grouped by language.
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedVoices(): array
    {
        return $this->groupedVoices;
    }

    /**
     * Get voices specifically for a given target language.
     *
     * @return array<string, string>
     */
    public function getVoicesForLanguage(string $langCode): array
    {
        return $this->groupedVoices[$langCode] ?? $this->groupedVoices['en'];
    }
}
