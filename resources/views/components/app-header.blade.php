@props(['back' => null, 'menu' => false])

{{-- Sticky-feel top bar: circular back/menu on the left, CHILLO wordmark
     centered, optional action slot (gift/share) on the right. --}}
<header {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 pb-1']) }}>
    <div class="flex w-11 justify-start">
        @if ($back)
            <a href="{{ $back }}" wire:navigate aria-label="กลับ"
               class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
        @elseif ($menu)
            <button type="button" aria-label="เมนู"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
        @endif
    </div>

    <div class="select-none text-center leading-none">
        <span class="font-sans text-2xl font-extrabold tracking-tight text-chillo-blue">CHILL<span class="text-chillo-orange">O</span></span>
        <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[0.25em] text-ink-faint">Feel the Chill</span>
    </div>

    <div class="flex w-11 justify-end">
        {{ $slot }}
    </div>
</header>
