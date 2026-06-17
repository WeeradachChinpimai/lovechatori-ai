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
            ? Storage::disk('public')->url($this->session->generated_avatar_path)
            : '';
        $this->shareUrl = route('play.result', $uuid);
    }
};
?>

<div class="flex flex-1 flex-col animate-pop">
    <h1 class="text-center text-2xl font-bold text-slate-800">นี่คือตัวตนในอนาคตของคุณ! 🎉</h1>

    {{-- Avatar poster --}}
    <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-xl">
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="avatar" class="w-full">
        @else
            <div class="flex aspect-[9/16] items-center justify-center text-6xl">🥤</div>
        @endif
    </div>

    {{-- Character info --}}
    <div class="mt-5 space-y-3">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-candy-pink">ชื่อตัวละคร</p>
            <p class="text-2xl font-bold text-slate-800">{{ $analysis['character_name'] ?? 'Slush Hero' }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3">
            @php
                $rows = [
                    ['🛡️', 'บทบาทในอนาคต', $analysis['future_role'] ?? '-'],
                    ['⚡', 'พลังพิเศษ', $analysis['special_power'] ?? '-'],
                    ['🥤', 'รสสเลอปี้ที่เหมาะกับคุณ', $analysis['slush_flavor'] ?? '-'],
                ];
            @endphp
            @foreach ($rows as $row)
                <div class="flex items-start gap-3 rounded-2xl bg-white/80 p-4 shadow-sm">
                    <span class="text-2xl">{{ $row[0] }}</span>
                    <div>
                        <p class="text-xs text-slate-400">{{ $row[1] }}</p>
                        <p class="font-semibold text-slate-700">{{ $row[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! empty($analysis['short_caption']))
            <p class="text-center text-sm italic text-slate-500">“{{ $analysis['short_caption'] }}”</p>
        @endif
    </div>

    {{-- Coupon --}}
    @if ($session->coupon)
        <div class="relative mt-6 overflow-hidden rounded-3xl bg-gradient-to-br from-candy-pink to-candy-yellow p-5 text-white shadow-lg">
            <p class="text-sm font-semibold opacity-90">🎟️ คูปองส่วนลดของคุณ</p>
            <p class="mt-1 text-3xl font-bold">{{ $session->coupon->discount_label }}</p>
            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="rounded-xl bg-white/25 px-3 py-2 font-mono text-lg font-bold tracking-wider">{{ $session->coupon->code }}</span>
                <div class="rounded-xl bg-white p-1.5">
                    <img src="{{ \App\Support\Qr::dataUri($shareUrl, 72, [60, 60, 60]) }}" alt="QR คูปอง" width="72" height="72">
                </div>
            </div>
            <p class="mt-3 text-xs opacity-90">ใช้ได้ถึง {{ optional($session->coupon->expired_at)->format('d/m/Y H:i') }} น. — แสดงรหัสนี้ที่หน้าร้าน</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="mt-6 grid grid-cols-1 gap-3"
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
        @if ($avatarUrl)
            <button type="button" x-on:click="download()" x-bind:disabled="saving"
               class="flex items-center justify-center gap-2 rounded-full bg-candy-pink px-6 py-4 text-lg font-bold text-white shadow-lg shadow-candy-pink/40 active:scale-95 disabled:opacity-60">
                <span x-show="!saving">💾 บันทึกรูป</span>
                <span x-show="saving" style="display:none">กำลังบันทึก…</span>
            </button>
        @endif
        <button type="button" @click="share()"
                class="flex items-center justify-center gap-2 rounded-full bg-candy-blue px-6 py-4 text-lg font-bold text-white shadow active:scale-95">
            📤 แชร์ให้เพื่อน
        </button>
        <a href="{{ route('play.upload') }}" wire:navigate
           class="flex items-center justify-center gap-2 rounded-full bg-white px-6 py-4 text-lg font-bold text-candy-pink shadow active:scale-95">
            🔁 เล่นอีกครั้ง
        </a>
    </div>
</div>
