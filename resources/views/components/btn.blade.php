@props([
    'variant'  => 'primary',   // primary (orange) | secondary (white/blue) | blue
    'href'     => null,        // render as <a> when set, else <button>
    'icon'     => null,        // leading Lucide icon name
    'trailing' => null,        // trailing icon name shown in a circle on the right
])

@php
    $base = 'relative inline-flex min-h-[56px] w-full items-center justify-center gap-2 rounded-full px-6 text-lg font-extrabold transition active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100';
    $styles = match ($variant) {
        'secondary' => 'bg-white text-chillo-blue ring-2 ring-chillo-blue hover:bg-chillo-blue-light',
        'blue'      => 'bg-chillo-blue text-white shadow-soft hover:bg-chillo-blue-dark',
        default     => 'bg-chillo-orange text-white shadow-button hover:bg-chillo-orange-dark',
    };
    $classes = $base.' '.$styles;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-5 w-5" />@endif
        <span>{{ $slot }}</span>
        @if ($trailing)
            <span class="absolute right-2 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/25">
                <x-icon :name="$trailing" class="h-5 w-5" />
            </span>
        @endif
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-5 w-5" />@endif
        <span>{{ $slot }}</span>
        @if ($trailing)
            <span class="absolute right-2 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/25">
                <x-icon :name="$trailing" class="h-5 w-5" />
            </span>
        @endif
    </button>
@endif
