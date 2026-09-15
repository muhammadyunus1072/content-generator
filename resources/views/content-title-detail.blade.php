<x-layouts::app :title="$contentTitle->title">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:content-title-detail :contentTitle="$contentTitle" />
    </div>
</x-layouts::app>
