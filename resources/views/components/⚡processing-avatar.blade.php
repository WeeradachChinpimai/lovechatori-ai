<?php

use App\Jobs\GenerateAvatar;
use App\Models\SlushSession;
use Livewire\Component;

new class extends Component
{
    public string $uuid;
    public bool $failed = false;

    public function mount(string $uuid)
    {
        $this->uuid = $uuid;

        $session = SlushSession::where('session_uuid', $uuid)->firstOrFail();

        if ($session->status === 'done') {
            return $this->redirect(route('play.result', $uuid), navigate: true);
        }

        if ($session->status === 'failed') {
            $this->failed = true;

            return;
        }

        // Kick off the avatar generation once, on a queue worker.
        if ($session->status === 'pending') {
            $session->update(['status' => 'queued']);
            GenerateAvatar::dispatch($uuid);
        }
    }

    /** Polled by the UI every couple of seconds. */
    public function poll()
    {
        $session = SlushSession::where('session_uuid', $this->uuid)->first();

        if (! $session) {
            return $this->redirect(route('play.landing'), navigate: true);
        }

        if ($session->status === 'done') {
            return $this->redirect(route('play.result', $this->uuid), navigate: true);
        }

        if ($session->status === 'failed') {
            $this->failed = true;
        }
    }
};
?>

<div class="flex flex-1 flex-col"
     @if (! $failed) wire:poll.2000ms="poll" @endif
     x-data="{
        failed: @js($failed),
        messages: [
            'AI กำลังดูพลังสเลอปี้ของคุณ…',
            'กำลังเลือกสีที่เหมาะกับคุณ…',
            'กำลังปั่นน้ำแข็งแห่งอนาคต…',
            'กำลังวาด Avatar ของคุณ…',
            'ใกล้เสร็จแล้ว แต่งสีให้สดใส…',
        ],
        i: 0,
        elapsed: 0,
        progress: 0,
        tick() {
            if (this.failed) return;
            this.elapsed += 0.1;
            this.progress = Math.min(95, 95 * (1 - Math.exp(-this.elapsed / 12)));
            this.i = Math.min(this.messages.length - 1, Math.floor(this.elapsed / 4));
            // Safety net: if no worker ever finishes (queue stuck), bail out softly.
            if (this.elapsed > 180) { this.failed = true; }
        },
     }"
     x-init="setInterval(() => tick(), 100)">

    <x-app-header :back="route('play.landing')" />

    {{-- Working state --}}
    <template x-if="!failed">
        <div class="flex flex-1 flex-col items-center justify-center text-center">
            {{-- Circular AI illustration with rotating progress ring --}}
            <div class="relative flex h-48 w-48 items-center justify-center">
                <div class="absolute inset-0 animate-spinslow rounded-full border-[6px] border-dashed border-chillo-blue/30"></div>
                <div class="absolute -inset-1 animate-sparkle text-chillo-orange" style="top:-4px;right:8px"><x-icon name="sparkles" class="h-7 w-7" /></div>
                {{-- IMAGE: CHILLO mascot + slushy cup + AI badge, generating an avatar --}}
                <x-image-placeholder
                    caption="Mascot กำลังสร้าง Avatar"
                    note="CHILLO mascot with slushy cup and AI badge, generating avatar, ice & sparkle"
                    class="h-40 w-40 rounded-full" />
            </div>

            <h2 class="mt-7 text-2xl font-extrabold text-chillo-blue">กำลังสร้าง Avatar ของคุณ</h2>
            <p class="mt-2 h-6 text-base font-semibold text-chillo-orange transition" x-text="messages[i]"></p>

            {{-- Progress bar (blue -> orange) --}}
            <div class="mt-6 flex w-72 max-w-full items-center gap-3">
                <div class="h-3 flex-1 overflow-hidden rounded-full bg-white shadow-inner ring-1 ring-soft">
                    <div class="h-full rounded-full bg-gradient-to-r from-chillo-blue via-chillo-sky to-chillo-orange transition-all duration-100 ease-out"
                         x-bind:style="`width: ${progress}%`"></div>
                </div>
                <span class="w-12 text-right text-lg font-extrabold text-chillo-blue" x-text="Math.round(progress) + '%'"></span>
            </div>

            {{-- Estimated time --}}
            <p class="mt-5 inline-flex items-center gap-2 text-lg font-bold text-ink">
                <x-icon name="clock-3" class="h-5 w-5 text-chillo-blue" /> <span x-text="Math.floor(elapsed)"></span> วินาที
            </p>
            <p class="mt-2 px-6 text-xs leading-relaxed text-ink-faint">
                การสร้างภาพด้วย AI ใช้เวลาสักครู่ (ปกติ 15–30 วินาที) — ยังทำงานอยู่ อย่าเพิ่งปิดหน้านี้นะ 💛
            </p>

            {{-- Process steps --}}
            <div class="mt-6 flex w-full max-w-xs items-start justify-between rounded-3xl border border-soft bg-white p-4 shadow-soft">
                @php
                    $steps = [
                        ['icon' => 'scan-face', 'label' => 'วิเคราะห์รูป', 'lo' => 0,  'hi' => 33],
                        ['icon' => 'sparkles',  'label' => 'สร้างตัวตน',  'lo' => 33, 'hi' => 70],
                        ['icon' => 'cup-soda',  'label' => 'จับคู่รสชาติ', 'lo' => 70, 'hi' => 95],
                    ];
                @endphp
                @foreach ($steps as $k => $step)
                    @if ($k > 0)
                        <div class="mt-5 h-0.5 flex-1 rounded-full"
                             x-bind:class="progress >= {{ $step['lo'] }} ? 'bg-chillo-blue' : 'bg-slate-200'"></div>
                    @endif
                    <div class="flex w-16 shrink-0 flex-col items-center gap-1.5 text-center">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full transition"
                              x-bind:class="progress >= {{ $step['hi'] }} ? 'bg-chillo-blue text-white' : (progress >= {{ $step['lo'] }} ? 'bg-chillo-orange text-white' : 'bg-slate-100 text-slate-400')">
                            <template x-if="progress >= {{ $step['hi'] }}"><x-icon name="circle-check" class="h-5 w-5" /></template>
                            <template x-if="progress < {{ $step['hi'] }}"><x-icon :name="$step['icon']" class="h-5 w-5" /></template>
                        </span>
                        <span class="text-[11px] font-bold leading-tight"
                              x-bind:class="progress >= {{ $step['lo'] }} ? 'text-ink' : 'text-ink-faint'">{{ $step['label'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Friendly status card --}}
            <div class="mt-5 flex w-full max-w-xs items-center gap-3 rounded-3xl border border-soft bg-chillo-blue-light/60 p-3 text-left">
                {{-- IMAGE: small CHILLO mascot head smiling / winking --}}
                <!-- IMAGE PLACEHOLDER: small CHILLO mascot head smiling or winking -->
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-chillo-blue/30 bg-white text-chillo-blue/60">
                    <x-icon name="image" class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold leading-snug text-chillo-blue">
                    AI กำลังครีเอทลุคที่ใช่ และรสชาติที่ชอบให้คุณอยู่!
                </p>
            </div>
        </div>
    </template>

    {{-- Soft failure state --}}
    <template x-if="failed">
        <div class="flex flex-1 flex-col items-center justify-center text-center animate-pop"
             x-init="setTimeout(() => window.location.href = @js(route('play.landing')), 5000)">
            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-chillo-orange-light text-5xl">🥲</div>
            <h2 class="mt-6 text-2xl font-extrabold text-chillo-blue">อุ๊ปส์ สร้างไม่สำเร็จ</h2>
            <p class="mt-2 text-base text-ink-soft">ไม่เป็นไรนะ ลองใหม่อีกครั้งได้เลย!</p>
            <a href="{{ route('play.landing') }}" wire:navigate
               class="mt-6 inline-flex min-h-[52px] items-center gap-2 rounded-full bg-chillo-orange px-8 text-lg font-extrabold text-white shadow-button active:scale-95">
                <x-icon name="rotate-ccw" class="h-5 w-5" /> เล่นใหม่
            </a>
            <p class="mt-3 text-xs text-ink-faint">กำลังพากลับไปหน้าแรก…</p>
        </div>
    </template>
</div>
