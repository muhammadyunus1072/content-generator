<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Video Upload Component -->
            <div class="lg:col-span-1">
                <livewire:video-upload />
            </div>

            <!-- Right: Video List Component -->
            <div class="lg:col-span-2">
                <livewire:video-list />
            </div>
        </div>

        <!-- Dedicated Clip Manager Section below the list -->
        <livewire:clip-manager />
    </div>
</x-layouts::app>
