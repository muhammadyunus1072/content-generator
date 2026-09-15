<?php

namespace App\Livewire;

use App\Models\ContentFactoryTemplate;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class ContentFactory extends Component
{
    #[Url]
    public string $niche = 'all';

    #[Url]
    public string $platform = 'all';

    public string $search = '';

    public bool $showPreviewModal = false;

    public ?int $previewTemplateId = null;

    public function selectNiche(string $niche): void
    {
        $this->niche = $niche;
    }

    public function selectPlatform(string $platform): void
    {
        $this->platform = $platform;
    }

    public function openPreview(int $templateId): void
    {
        $this->previewTemplateId = $templateId;
        $this->showPreviewModal = true;
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewTemplateId = null;
    }

    public function useInStoryStudio(int $templateId)
    {
        return redirect()->route('story-studio', ['template_id' => $templateId]);
    }

    public function useInEpubStudio(int $templateId)
    {
        return redirect()->route('epub-studio', ['template_id' => $templateId]);
    }

    public function useInComicStudio(int $templateId)
    {
        return redirect()->route('comic-studio', ['template_id' => $templateId]);
    }

    public function getPreviewTemplateProperty(): ?ContentFactoryTemplate
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return ContentFactoryTemplate::find($this->previewTemplateId);
    }

    public function getTemplatesProperty(): Collection
    {
        return ContentFactoryTemplate::query()
            ->active()
            ->when($this->niche !== 'all', function ($query) {
                $query->where('niche', $this->niche);
            })
            ->when($this->platform !== 'all', function ($query) {
                $query->where(function ($q) {
                    $q->where('target_platform', $this->platform)
                        ->orWhere('target_platform', 'both');
                });
            })
            ->when(! empty($this->search), function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('visual_style', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function render()
    {
        $niches = [
            'all' => ['label' => 'All Niches', 'icon' => 'squares-2x2', 'count' => ContentFactoryTemplate::active()->count()],
            'manhwa_recap' => ['label' => 'Manhwa Recap', 'icon' => 'bolt', 'count' => ContentFactoryTemplate::active()->where('niche', 'manhwa_recap')->count()],
            'kids_story' => ['label' => 'Kids Storybook', 'icon' => 'heart', 'count' => ContentFactoryTemplate::active()->where('niche', 'kids_story')->count()],
            'science_explainer' => ['label' => 'Science Explainer', 'icon' => 'globe-americas', 'count' => ContentFactoryTemplate::active()->where('niche', 'science_explainer')->count()],
            'horror_narration' => ['label' => 'Horror & Creepypasta', 'icon' => 'eye', 'count' => ContentFactoryTemplate::active()->where('niche', 'horror_narration')->count()],
            'book_summary' => ['label' => 'Book Summary & Wealth', 'icon' => 'currency-dollar', 'count' => ContentFactoryTemplate::active()->where('niche', 'book_summary')->count()],
        ];

        return view('livewire.content-factory', [
            'templates' => $this->templates,
            'niches' => $niches,
            'previewTemplate' => $this->previewTemplate,
        ]);
    }
}
