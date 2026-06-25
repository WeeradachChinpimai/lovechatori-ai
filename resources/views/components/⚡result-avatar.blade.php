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
        showSave: false,
        async download() {
            if (this.saving) return;
            this.saving = true;
            const url = @js($avatarUrl);
            const ext = (url.split('?')[0].split('.').pop() || 'png').toLowerCase();
            // In-app browsers (LINE, Facebook, IG, WeChat) block <a download> on Android.
            const inApp = /Line|FBAN|FBAV|Instagram|Messenger|MicroMessenger/i.test(navigator.userAgent);
            try {
                const res = await fetch(url);
                const blob = await res.blob();
                const file = new File([blob], 'slush-avatar.' + ext, { type: blob.type || 'image/png' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    // Native share sheet (iOS Safari + most Android) → save to Photos.
                    await navigator.share({ files: [file], title: 'AI Future Slush Avatar' });
                } else if (!inApp) {
                    // Normal browsers honour the download attribute.
                    const obj = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = obj; a.download = file.name;
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(() => URL.revokeObjectURL(obj), 2000);
                } else {
                    // LINE/webview: show the image so the user can long-press → Save image.
                    this.showSave = true;
                }
            } catch (e) {
                this.showSave = true;
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
    <div class="relative mt-3 overflow-hidden rounded-[28px] border border-soft bg-white shadow-soft">
        @if ($avatarUrl)
            <div x-data="{ loaded: false }" x-init="$refs.img.complete && (loaded = true)"
                 class="relative aspect-[4/3] w-full">
                {{-- gray shimmer placeholder while the image loads from storage --}}
                <div x-show="!loaded" class="absolute inset-0 skeleton-shimmer"></div>
                <img x-ref="img" src="{{ $avatarUrl }}" alt="avatar"
                     x-on:load="loaded = true"
                     x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                     class="h-full w-full object-cover transition-opacity duration-500">
            </div>
        @else
            <div class="flex aspect-[4/3] items-center justify-center text-6xl">🥤</div>
        @endif
        <span class="absolute bottom-3 right-3 rounded-full bg-chillo-blue/90 px-3 py-1 text-xs font-bold text-white shadow">#FutureChillo</span>
    </div>

    {{-- Character info (two compact cards, side by side) --}}
    <div class="mt-6 grid grid-cols-2 gap-3">
        @php
            $rows = [
                ['icon' => 'shield',   'label' => 'บทบาทในอนาคต',           'value' => $analysis['future_role'] ?? '-',  'accent' => false],
                ['icon' => 'cup-soda', 'label' => 'รสสเลอปี้ที่เหมาะกับคุณ', 'value' => $analysis['slush_flavor'] ?? '-', 'accent' => true],
            ];
        @endphp
        @foreach ($rows as $row)
            <x-info-card class="relative flex flex-col items-center justify-center gap-1.5 px-4 pb-4 pt-6 text-center">
                {{-- icon badge hanging off the top-left corner --}}
                <span class="absolute -left-2.5 -top-2.5 flex h-10 w-10 items-center justify-center rounded-2xl shadow-md ring-2 ring-white {{ $row['accent'] ? 'bg-chillo-orange text-white' : 'bg-chillo-blue text-white' }}">
                    <x-icon :name="$row['icon']" class="h-5 w-5" />
                </span>
                <p class="text-[11px] font-bold leading-tight text-chillo-blue">{{ $row['label'] }}</p>
                <p class="text-base font-extrabold leading-tight {{ $row['accent'] ? 'text-chillo-orange' : 'text-ink' }}">{{ $row['value'] }}</p>
            </x-info-card>
        @endforeach
    </div>

    {{-- Discount ticket: fixed 5-baht off (perforated ticket style, no code / QR) --}}
    @if ($session->coupon)
        <div class="relative mt-4 rounded-[28px] bg-chillo-orange px-5 py-6 text-center text-white shadow-button"
             style="-webkit-mask-image: radial-gradient(circle 14px at 2.25rem 0, transparent 12px, #000 13px), radial-gradient(circle 14px at 2.25rem 100%, transparent 12px, #000 13px); -webkit-mask-composite: source-in; mask-image: radial-gradient(circle 14px at 2.25rem 0, transparent 12px, #000 13px), radial-gradient(circle 14px at 2.25rem 100%, transparent 12px, #000 13px); mask-composite: intersect;">
            {{-- left tear-off perforation line (notches are cut out via the mask above so they blend with any background) --}}
            <span aria-hidden="true" class="pointer-events-none absolute inset-y-5 left-9 border-l-2 border-dashed border-white/55"></span>

            <p class="inline-flex items-center justify-center gap-2 text-sm font-bold opacity-95"><x-icon name="ticket-percent" class="h-5 w-5" /> คูปองส่วนลดของคุณ</p>
            <p class="mt-1 text-4xl font-extrabold">ส่วนลด 5 บาท</p>
            <p class="mt-2 text-xs opacity-95">ใช้ได้ถึง {{ optional($session->coupon->expired_at)->format('d/m/Y H:i') }} น. — แสดงหน้านี้ที่ร้าน</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="mt-4 grid grid-cols-2 gap-3">
        @if ($avatarUrl)
            <button type="button" x-on:click="download()" x-bind:disabled="saving"
               class="inline-flex min-h-[56px] items-center justify-center gap-2 rounded-full bg-chillo-blue px-4 text-base font-extrabold text-white shadow-soft transition active:scale-[0.98] disabled:opacity-60">
                <x-icon name="download" class="h-5 w-5" />
                <span x-show="!saving">บันทึกรูป</span>
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

    @if (! empty($analysis['short_caption']))
        <p class="mt-4 text-center text-sm italic text-ink-soft">“{{ $analysis['short_caption'] }}”</p>
    @endif
    <p class="mt-2 text-center text-[11px] text-ink-faint">คูปองใช้ได้เฉพาะเมนูและสาขาที่ร่วมรายการ</p>

    {{-- Save overlay for in-app webviews (LINE etc.) where direct download is blocked.
         Teleported to <body> so it escapes the animate-pop transform and covers the full screen. --}}
    @if ($avatarUrl)
        <template x-teleport="body">
            <div x-show="showSave" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[60] flex flex-col items-center justify-center gap-5 bg-black/85 p-6"
                 x-on:click="showSave = false">
                <p class="text-center text-base font-bold text-white">📥 กดรูปค้างไว้ แล้วเลือก “บันทึกรูปภาพ”</p>
                <img src="{{ $avatarUrl }}" alt="avatar" x-on:click.stop draggable="false"
                     class="max-h-[68vh] w-auto max-w-full rounded-2xl shadow-2xl">
                <button type="button" x-on:click="showSave = false"
                        class="rounded-full bg-white px-8 py-2.5 text-sm font-extrabold text-ink shadow-lg">ปิด</button>
            </div>
        </template>
    @endif
</div>
