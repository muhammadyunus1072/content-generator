<x-layouts::app :title="$episode->title ?: __('Webtoon Reader')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:webtoon-reader :episode="$episode" />
    </div>
</x-layouts::app>
