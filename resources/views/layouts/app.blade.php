<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    @include('partials.admin.media-browser')
</x-layouts::app.sidebar>
