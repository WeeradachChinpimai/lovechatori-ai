<?php

use Livewire\Component;

new class extends Component
{
    public string $storeUrl = '';

    public function mount(): void
    {
        $this->storeUrl = route('play.landing');
    }
};
?>

<div class="flex flex-1 flex-col">
    <x-app-header :menu="true">
        <button type="button" aria-label="ของรางวัล"
                class="relative inline-flex h-11 w-11 items-center justify-center rounded-full bg-chillo-blue text-white shadow-soft transition active:scale-95">
            <x-icon name="gift" class="h-5 w-5" />
            <span class="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-chillo-orange ring-2 ring-white"></span>
        </button>
    </x-app-header>

    {{-- Hero --}}
    <div class="mt-2 text-center animate-pop">
        <h1 class="sr-only">AI Future Slush Avatar — เกม AI สุดสนุก</h1>
        {{-- Brand banner: logo + title + slushy, all text baked into the image --}}
        <img src="{{ asset('banner.png') }}" alt="AI Future Slush Avatar — เกม AI สุดสนุก จาก CHILLO"
             width="340" height="340" class="mx-auto w-full max-w-[340px]">
        <p class="mt-2 text-[15px] leading-relaxed text-ink-soft">
            ถ่ายรูป แล้วให้ AI สร้าง “ตัวตนในอนาคต” พร้อมรสสเลอปี้และคูปองส่วนลด!
        </p>
    </div>

    {{-- 3 steps --}}
    <div class="mt-7 grid grid-cols-3 gap-2.5">
        @php
            $steps = [
                ['img' => '1.png', 'label' => 'ถ่ายรูป',        'desc' => 'ถ่ายรูปใบหน้าของคุณ ให้ชัดและสว่าง'],
                ['img' => '2.png', 'label' => 'AI สร้าง Avatar', 'desc' => 'AI วิเคราะห์และสร้าง ตัวตนในอนาคตให้คุณ'],
                ['img' => '3.png', 'label' => 'รับคูปอง',        'desc' => 'รับคูปองส่วนลด CHILLO และแชร์ได้ทันที'],
            ];
        @endphp
        @foreach ($steps as $i => $step)
            <div class="relative rounded-3xl border border-soft bg-white px-2 pb-3 pt-5 text-center shadow-soft">
                <span class="absolute -left-2 -top-2 flex h-8 w-8 items-center justify-center rounded-full bg-chillo-orange text-sm font-extrabold text-white shadow ring-2 ring-white">{{ $i + 1 }}</span>
                <img src="{{ asset($step['img']) }}" alt="{{ $step['label'] }}" width="96" height="96" class="mx-auto h-20 w-20 object-contain">
                <p class="mt-1.5 text-[13px] font-extrabold leading-tight text-ink">{{ $step['label'] }}</p>
                <p class="mt-1 text-[10px] leading-snug text-ink-soft">{{ $step['desc'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Start button --}}
    <x-btn variant="primary" :href="route('play.upload')" wire:navigate icon="sparkles" trailing="arrow-right" class="mt-8">
        เริ่มสร้าง Avatar ของฉัน
    </x-btn>

    {{-- Trust strip --}}
    <div class="mt-5 flex items-center justify-center gap-2.5 text-[11px] font-semibold text-ink-soft">
        <span class="inline-flex items-center gap-1"><x-icon name="shield-check" class="h-4 w-4 text-chillo-blue" /> ปลอดภัย</span>
        <span class="text-ink-faint">|</span>
        <span class="inline-flex items-center gap-1"><x-icon name="lock" class="h-4 w-4 text-chillo-blue" /> ไม่เก็บข้อมูล</span>
        <span class="text-ink-faint">|</span>
        <span class="inline-flex items-center gap-1"><x-icon name="clock-3" class="h-4 w-4 text-chillo-blue" /> ใช้เวลาไม่นาน</span>
    </div>

    {{-- Store QR --}}
    <div class="mt-auto pt-9 text-center">
        <p class="mb-3 text-sm text-ink-faint">สแกนเพื่อเล่นบนมือถือ</p>
        <div class="mx-auto inline-flex rounded-2xl border border-soft bg-white p-3 shadow-soft">
            <img src="{{ \App\Support\Qr::dataUri($storeUrl, 150) }}" alt="QR หน้าร้าน" width="150" height="150">
        </div>
    </div>
</div>
