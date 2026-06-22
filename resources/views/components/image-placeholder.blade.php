@props([
    'caption' => null,     // short text describing the intended image
    'note'    => null,     // longer hint placed in an HTML comment for the future asset
    'shimmer' => true,     // subtle shimmer sweep
])

{{-- Decorative image placeholder. Swap for an AI/brand-generated image later.
     Real, functional images (QR codes, the generated avatar) must NOT use this. --}}
<!-- IMAGE PLACEHOLDER: {{ $note ?? $caption ?? 'CHILLO brand art' }} -->
<div {{ $attributes->merge(['class' => 'relative flex flex-col items-center justify-center gap-2 overflow-hidden rounded-[28px] border-2 border-dashed border-chillo-blue/25 bg-gradient-to-br from-chillo-blue-light/80 to-chillo-orange-light/80 p-5 text-center']) }}>
    @if ($shimmer)<div class="placeholder-shimmer"></div>@endif
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/70 text-chillo-blue shadow-sm">
        <x-icon name="image" class="h-6 w-6" />
    </span>
    <p class="text-[11px] font-bold uppercase tracking-wider text-chillo-blue/70">Image placeholder</p>
    @if ($caption)
        <span class="max-w-[16rem] text-xs leading-snug text-ink-soft">{{ $caption }}</span>
    @endif
    {{ $slot }}
</div>
