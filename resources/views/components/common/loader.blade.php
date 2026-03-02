<div>
    <div id="loader-container" wire:loading> {{-- Start hidden --}}
        {{-- Loader icon --}}
        <div {{ $attributes->merge([
        'class' => 'z-50 position-absolute
            w-auto d-flex align-items-center justify-content-center bg-white opacity-50',
    ]) }}>
            {{-- Inner container for the icon, preventing opacity inheritance --}}
            <span class="text-primary">
        {{-- Font Awesome spinning icon (larger size) --}}
        <i class="fas fa-spinner fa-spin fs-3" aria-hidden="true"></i>
        {{-- Screen reader text --}}
        <span>Loading...</span>
    </span>
        </div>
        {{-- End loader icon --}}
        {{-- Optional text below the icon --}}
    </div>
</div>



