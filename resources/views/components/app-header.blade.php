@props(['back' => null, 'menu' => false])

{{-- Minimal top bar: circular back button on the left, optional action slot
     (gift/share) on the right. No brand wordmark — the banner carries it. --}}
<header {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 pb-1']) }}>
    <div class="flex w-11 justify-start">
        @if ($back)
            <a href="{{ $back }}" wire:navigate aria-label="กลับ"
               class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
        @endif
    </div>

    <div class="flex w-11 justify-end">
        {{ $slot }}
    </div>
</header>
