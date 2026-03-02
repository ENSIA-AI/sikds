<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <!-- In your Blade view -->
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('action-completed', (event) => {
                window.location.reload(); // simpler way to refresh
            });
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-bottom', (event) => {
                setTimeout(function () {
                    window.scrollTo({ top: document.documentElement.scrollHeight / 3, behavior: 'smooth' });
                }, 500);
            });
        });
        {{-- Listen for the custom event --}}

        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-after-validation', (event) => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

    </script>

</x-layouts.app.sidebar>
