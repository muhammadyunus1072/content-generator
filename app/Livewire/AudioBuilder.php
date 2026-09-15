<?php

namespace App\Livewire;

use App\Models\AudioGeneration;
use App\Services\TextToSpeechService;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AudioBuilder extends Component
{
    use WithPagination;

    public string $text = 'Di sebuah desa terpencil yang tenang di kaki pegunungan, hiduplah seorang pemuda yang memiliki kemampuan mendengar bisikan alam. Namun, rahasia besar yang tersembunyi selama ribuan tahun mulai terungkap saat sebuah artefak kuno bercahaya di tengah hutan terlarang.';

    public string $title = '';

    public string $provider = 'edge_tts'; // 'edge_tts', 'docker_tts'

    public string $dockerProtocol = 'openai'; // 'openai', 'cog'

    public string $dockerBaseUrl = 'http://localhost:8880';

    public string $voice = 'id-ID-ArdiNeural';

    public string $customVoice = '';

    public string $rate = '-15%';

    public string $pitch = '-10Hz';

    public bool $applyDramaticPause = true;

    public string $activeVoiceCategory = 'all';

    public string $voiceSearch = '';

    public ?int $currentAudioId = null;

    public ?string $errorMessage = null;

    public bool $isGenerating = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->loadAudio($id);
        } else {
            $latest = AudioGeneration::forUser(Auth::id())->latest()->first();
            if ($latest) {
                $this->currentAudioId = $latest->id;
            }
        }
    }

    public function updatedProvider(string $value): void
    {
        if ($value === 'docker_tts') {
            $this->voice = 'af_heart';
            $this->activeVoiceCategory = 'Docker Kokoro-82M';
            $this->dockerBaseUrl = $this->dockerProtocol === 'cog' ? 'http://localhost:5000' : 'http://localhost:8880';
        } else {
            $this->voice = 'id-ID-ArdiNeural';
            $this->activeVoiceCategory = 'Indonesian & Nusantara';
        }
    }

    public function updatedDockerProtocol(string $value): void
    {
        $this->dockerBaseUrl = $value === 'cog' ? 'http://localhost:5000' : 'http://localhost:8880';
    }

    public function setVoice(string $voiceKey): void
    {
        $this->voice = $voiceKey;
        $this->customVoice = '';
    }

    public function setVoiceCategory(string $category): void
    {
        $this->activeVoiceCategory = $category;
    }

    public function applySample(string $type): void
    {
        switch ($type) {
            case 'documentary':
                $this->text = 'Bumi menyimpan misteri tak terhitung di kedalaman samudera dan puncak gunung tertinggi. Setiap formasi batuan mencatat jutaan tahun sejarah evolusi yang menakjubkan.';
                $this->rate = '-15%';
                $this->pitch = '-10Hz';
                $this->applyDramaticPause = true;
                if ($this->provider === 'edge_tts') {
                    $this->voice = 'id-ID-ArdiNeural';
                }
                break;

            case 'anime':
                $this->text = 'Katakan padaku! Mengapa kau selalu menyembunyikan kekuatan aslimu saat bertarung di depanku?! Aku tidak akan membiarkanmu pergi begitu saja kali ini!';
                $this->rate = '+0%';
                $this->pitch = '+5Hz';
                $this->applyDramaticPause = false;
                if ($this->provider === 'edge_tts') {
                    $this->voice = 'ja-JP-NanamiNeural';
                }
                break;

            case 'audiobook':
                $this->text = 'Malam semakin larut. Angin dingin berhembus melalui celah jendela tua yang sedikit terbuka. Di sudut ruangan, lilin menyala redup, menerangi sebuah buku bersampul kulit.';
                $this->rate = '-20%';
                $this->pitch = '-5Hz';
                $this->applyDramaticPause = true;
                if ($this->provider === 'edge_tts') {
                    $this->voice = 'id-ID-GadisNeural';
                }
                break;
        }
    }

    public function generateAudio(TextToSpeechService $ttsService): void
    {
        $cleanText = trim($this->text);
        if ($cleanText === '') {
            Flux::toast(variant: 'danger', text: 'Please enter text to synthesize.');

            return;
        }

        $this->isGenerating = true;
        $this->errorMessage = null;

        try {
            $effectiveVoice = trim($this->customVoice) !== '' ? trim($this->customVoice) : $this->voice;

            // Generate paths
            $fileUuid = Str::uuid()->toString();
            $audioRelative = "audio_builder/{$fileUuid}.mp3";
            $audioAbsolute = Storage::disk('public')->path($audioRelative);
            File::ensureDirectoryExists(dirname($audioAbsolute));

            $subtitleRelative = null;
            $subtitleAbsolute = null;
            $parsedCues = null;

            if ($this->provider === 'edge_tts') {
                $subtitleRelative = "audio_builder/{$fileUuid}.vtt";
                $subtitleAbsolute = Storage::disk('public')->path($subtitleRelative);
            }

            $effectiveProvider = $this->provider;
            if ($this->provider === 'docker_tts') {
                $effectiveProvider = $this->dockerProtocol === 'cog' ? 'docker_cog' : 'docker_openai';
            }

            // Synthesize
            $result = $ttsService->synthesize(
                text: $cleanText,
                outputAudioAbsolutePath: $audioAbsolute,
                outputSubtitleAbsolutePath: $subtitleAbsolute,
                voice: $effectiveVoice,
                provider: $effectiveProvider,
                rate: $this->rate,
                pitch: $this->pitch,
                baseUrl: $this->dockerBaseUrl
            );

            // Parse cues if VTT exists
            if ($subtitleAbsolute && File::exists($subtitleAbsolute)) {
                $vttContent = File::get($subtitleAbsolute);
                $parsedCues = TextToSpeechService::parseVttToCues($vttContent);
            }

            $voiceName = $this->resolveVoiceName($effectiveVoice);
            $autoTitle = trim($this->title) !== '' ? trim($this->title) : Str::limit($cleanText, 45);
            $fileSize = File::exists($audioAbsolute) ? filesize($audioAbsolute) : 0;

            // Store in database
            $generation = AudioGeneration::create([
                'user_id' => Auth::id(),
                'title' => $autoTitle,
                'text' => $cleanText,
                'provider' => $effectiveProvider,
                'voice' => $effectiveVoice,
                'voice_name' => $voiceName,
                'rate' => $this->rate,
                'pitch' => $this->pitch,
                'audio_path' => $audioRelative,
                'subtitle_path' => $subtitleRelative,
                'subtitle_cues' => $parsedCues,
                'duration_seconds' => $result['duration_seconds'] ?? 0,
                'file_size_bytes' => $fileSize,
                'options' => [
                    'dramatic_pause' => $this->applyDramaticPause,
                    'docker_base_url' => $this->dockerBaseUrl,
                ],
            ]);

            $this->currentAudioId = $generation->id;
            $this->title = '';

            Flux::toast(variant: 'success', text: "Audio generated successfully ({$generation->formatted_duration})!");
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Flux::toast(variant: 'danger', text: 'Synthesis failed: '.$e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function loadAudio(int $id): void
    {
        $gen = AudioGeneration::forUser(Auth::id())->find($id);
        if ($gen) {
            $this->currentAudioId = $gen->id;
            $this->text = $gen->text;
            $this->voice = $gen->voice;
            $this->rate = $gen->rate ?: '-15%';
            $this->pitch = $gen->pitch ?: '-10Hz';
            if (str_starts_with($gen->provider, 'docker')) {
                $this->provider = 'docker_tts';
                $this->dockerProtocol = str_contains($gen->provider, 'cog') ? 'cog' : 'openai';
            } else {
                $this->provider = 'edge_tts';
            }
        }
    }

    public function deleteAudio(int $id): void
    {
        $gen = AudioGeneration::forUser(Auth::id())->find($id);
        if ($gen) {
            if ($gen->audio_path) {
                Storage::disk('public')->delete($gen->audio_path);
            }
            if ($gen->subtitle_path) {
                Storage::disk('public')->delete($gen->subtitle_path);
            }
            $gen->delete();

            if ($this->currentAudioId === $id) {
                $latest = AudioGeneration::forUser(Auth::id())->latest()->first();
                $this->currentAudioId = $latest ? $latest->id : null;
            }

            Flux::toast(variant: 'success', text: 'Audio record deleted.');
        }
    }

    public function downloadSrt(int $id)
    {
        $gen = AudioGeneration::forUser(Auth::id())->findOrFail($id);
        if (! $gen->subtitle_path || ! Storage::disk('public')->exists($gen->subtitle_path)) {
            Flux::toast(variant: 'danger', text: 'Subtitle file not found for this audio.');

            return;
        }

        $vttContent = Storage::disk('public')->get($gen->subtitle_path);
        $srtContent = TextToSpeechService::convertVttToSrt($vttContent);
        $filename = Str::slug($gen->title ?: 'subtitles').'.srt';

        return response()->streamDownload(function () use ($srtContent) {
            echo $srtContent;
        }, $filename, ['Content-Type' => 'text/plain']);
    }

    public function downloadVtt(int $id)
    {
        $gen = AudioGeneration::forUser(Auth::id())->findOrFail($id);
        if (! $gen->subtitle_path || ! Storage::disk('public')->exists($gen->subtitle_path)) {
            Flux::toast(variant: 'danger', text: 'Subtitle file not found.');

            return;
        }

        $vttContent = Storage::disk('public')->get($gen->subtitle_path);
        $filename = Str::slug($gen->title ?: 'subtitles').'.vtt';

        return response()->streamDownload(function () use ($vttContent) {
            echo $vttContent;
        }, $filename, ['Content-Type' => 'text/vtt']);
    }

    public function getFilteredVoicesProperty(): array
    {
        $catalog = $this->provider === 'docker_tts'
            ? TextToSpeechService::getDockerVoices()
            : TextToSpeechService::getDetailedVoiceCatalog();

        return array_filter($catalog, function ($v) {
            // Category filter
            if ($this->activeVoiceCategory !== 'all' && ($v['category'] ?? '') !== $this->activeVoiceCategory) {
                return false;
            }

            // Search query
            if (trim($this->voiceSearch) !== '') {
                $q = strtolower($this->voiceSearch);
                $haystack = strtolower(($v['name'] ?? '').' '.($v['key'] ?? '').' '.($v['personality'] ?? '').' '.($v['language'] ?? ''));
                if (! str_contains($haystack, $q)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function getCurrentAudioProperty(): ?AudioGeneration
    {
        if (! $this->currentAudioId) {
            return null;
        }

        return AudioGeneration::forUser(Auth::id())->find($this->currentAudioId);
    }

    public function getHistoryProperty()
    {
        return AudioGeneration::forUser(Auth::id())
            ->latest()
            ->paginate(8);
    }

    protected function resolveVoiceName(string $voiceKey): string
    {
        $all = array_merge(TextToSpeechService::getDetailedVoiceCatalog(), TextToSpeechService::getDockerVoices());
        foreach ($all as $item) {
            if ($item['key'] === $voiceKey) {
                return $item['name'];
            }
        }

        return $voiceKey;
    }

    public function render()
    {
        return view('livewire.audio-builder', [
            'currentAudio' => $this->currentAudio,
            'filteredVoices' => $this->filteredVoices,
            'history' => $this->history,
        ]);
    }
}
