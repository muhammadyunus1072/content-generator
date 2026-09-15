<x-layouts::app :title="$episode->title ?: __('Manga Reader')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:manga-reader :episode="$episode" />
    </div>
</x-layouts::app>
