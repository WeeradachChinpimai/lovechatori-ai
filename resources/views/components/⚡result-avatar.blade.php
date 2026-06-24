<?php

use App\Models\SlushSession;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public SlushSession $session;
    public array $analysis = [];
    public string $avatarUrl = '';
    public string $shareUrl = '';

    public function mount(string $uuid)
    {
        $this->session = SlushSession::with('coupon')
            ->where('session_uuid', $uuid)->firstOrFail();

        if (! $this->session->isDone()) {
            return $this->redirect(route('play.processing', $uuid), navigate: true);
        }

        $this->analysis = $this->session->ai_response_json ?? [];
        $this->avatarUrl = $this->session->generated_avatar_path
            ? Storage::disk(config('slush.media_disk'))->url($this->session->generated_avatar_path)
            : '';
        $this->shareUrl = route('play.result', $uuid);
    }
};
?>

<div class="flex flex-1 flex-col animate-pop"
     x-data="{
        saving: false,
        async download() {
            if (this.saving) return;
            this.saving = true;
            const url = @js($avatarUrl);
            const ext = (url.split('?')[0].split('.').pop() || 'png').toLowerCase();
            try {
                const res = await fetch(url);
                const blob = await res.blob();
                const file = new File([blob], 'slush-avatar.' + ext, { type: blob.type });
                // Prefer the native share sheet on mobile (lets users save to Photos).
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], title: 'AI Future Slush Avatar' });
                } else {
                    const obj = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = obj; a.download = file.name;
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(() => URL.revokeObjectURL(obj), 2000);
                }
            } catch (e) {
                window.open(url, '_blank');
            } finally {
                this.saving = false;
            }
        },
        async share() {
            const data = { title: 'AI Future Slush Avatar', text: 'ดูตัวตนในอนาคตของฉันสิ!', url: @js($shareUrl) };
            try { if (navigator.share) { await navigator.share(data); } else { await navigator.clipboard.writeText(data.url); alert('คัดลอกลิงก์แล้ว!'); } } catch (e) {}
        }
     }">

    {{-- App bar: back (left) + title (center) + share (right) on one row --}}
    <div class="relative flex min-h-11 items-center justify-center">
        <a href="{{ route('play.upload') }}" wire:navigate aria-label="กลับ"
           class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="px-12 text-center text-lg font-extrabold leading-tight text-chillo-blue">นี่คือตัวตนใน<span class="text-chillo-orange">อนาคต</span>ของคุณ! 🎉</h1>
        <button type="button" @click="share()" aria-label="แชร์"
                class="absolute right-0 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
            <x-icon name="share-2" class="h-5 w-5" />
        </button>
    </div>

    {{-- Avatar poster (real AI-generated image) --}}
    <div class="relative mt-4 overflow-hidden rounded-[32px] border border-soft bg-white shadow-soft">
        @if ($avatarUrl)
            <div x-data="{ loaded: false }" x-init="$refs.img.complete && (loaded = true)"
                 class="relative aspect-square w-full">
                {{-- gray shimmer placeholder while the image loads from storage --}}
                <div x-show="!loaded" class="absolute inset-0 skeleton-shimmer"></div>
                <img x-ref="img" src="{{ $avatarUrl }}" alt="avatar"
                     x-on:load="loaded = true"
                     x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                     class="h-full w-full object-cover transition-opacity duration-500">
            </div>
        @else
            <div class="flex aspect-square items-center justify-center text-6xl">🥤</div>
        @endif
        <span class="absolute bottom-3 right-3 rounded-full bg-chillo-blue/90 px-3 py-1 text-xs font-bold text-white shadow">#FutureChillo</span>
    </div>

    {{-- Character info --}}
    <div class="mt-5 space-y-3">
        <div class="text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-chillo-orange">ชื่อตัวละคร</p>
            <p class="text-2xl font-extrabold text-chillo-blue">{{ $analysis['character_name'] ?? 'Slush Hero' }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3">
            @php
                $rows = [
                    ['icon' => 'shield',   'label' => 'บทบาทในอนาคต',           'value' => $analysis['future_role'] ?? '-',  'accent' => false],
                    ['icon' => 'zap',      'label' => 'พลังพิเศษ',               'value' => $analysis['special_power'] ?? '-', 'accent' => false],
                    ['icon' => 'cup-soda', 'label' => 'รสสเลอปี้ที่เหมาะกับคุณ', 'value' => $analysis['slush_flavor'] ?? '-', 'accent' => true],
                ];
            @endphp
            @foreach ($rows as $row)
                <x-info-card class="flex items-start gap-3 p-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $row['accent'] ? 'bg-chillo-orange-light text-chillo-orange' : 'bg-chillo-blue-light text-chillo-blue' }}">
                        <x-icon :name="$row['icon']" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-bold text-chillo-blue">{{ $row['label'] }}</p>
                        <p class="font-bold {{ $row['accent'] ? 'text-chillo-orange' : 'text-ink' }}">{{ $row['value'] }}</p>
                    </div>
                </x-info-card>
            @endforeach
        </div>

        @if (! empty($analysis['short_caption']))
            <p class="text-center text-sm italic text-ink-soft">“{{ $analysis['short_caption'] }}”</p>
        @endif
    </div>

    {{-- Coupon (real code + QR) --}}
    @if ($session->coupon)
        <div class="relative mt-6 overflow-hidden rounded-[28px] bg-chillo-orange p-5 text-white shadow-button">
            <p class="inline-flex items-center gap-2 text-sm font-bold opacity-95"><x-icon name="ticket-percent" class="h-5 w-5" /> คูปองส่วนลดของคุณ</p>
            <p class="mt-1 text-3xl font-extrabold">{{ $session->coupon->discount_label }}</p>
            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="rounded-xl bg-white/25 px-3 py-2 font-mono text-lg font-bold tracking-wider">{{ $session->coupon->code }}</span>
                <div class="rounded-xl bg-white p-1.5">
                    <img src="{{ \App\Support\Qr::dataUri($shareUrl, 72, [60, 60, 60]) }}" alt="QR คูปอง" width="72" height="72">
                </div>
            </div>
            <p class="mt-3 text-xs opacity-95">ใช้ได้ถึง {{ optional($session->coupon->expired_at)->format('d/m/Y H:i') }} น. — แสดงรหัสนี้ที่หน้าร้าน</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="mt-6 grid grid-cols-2 gap-3">
        @if ($avatarUrl)
            <button type="button" x-on:click="download()" x-bind:disabled="saving"
               class="inline-flex min-h-[56px] items-center justify-center gap-2 rounded-full bg-chillo-blue px-4 text-base font-extrabold text-white shadow-soft transition active:scale-[0.98] disabled:opacity-60">
                <x-icon name="download" class="h-5 w-5" />
                <span x-show="!saving">บันทึกผลลัพธ์</span>
                <span x-show="saving" style="display:none">กำลังบันทึก…</span>
            </button>
        @else
            <button type="button" @click="share()"
                    class="inline-flex min-h-[56px] items-center justify-center gap-2 rounded-full bg-chillo-blue px-4 text-base font-extrabold text-white shadow-soft transition active:scale-[0.98]">
                <x-icon name="share-2" class="h-5 w-5" /> แชร์ให้เพื่อน
            </button>
        @endif
        <a href="{{ route('play.upload') }}" wire:navigate
           class="inline-flex min-h-[56px] items-center justify-center gap-2 rounded-full bg-white px-4 text-base font-extrabold text-chillo-blue shadow-soft ring-2 ring-chillo-blue transition active:scale-[0.98]">
            <x-icon name="rotate-ccw" class="h-5 w-5" /> ลองใหม่อีกครั้ง
        </a>
    </div>
</div>
