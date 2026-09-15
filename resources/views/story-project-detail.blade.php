<x-layouts::app :title="$story->title">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:story-project-detail :story="$story" />
    </div>
</x-layouts::app>
