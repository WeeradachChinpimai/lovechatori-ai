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
    {{-- Hero --}}
    <div class="text-center animate-pop">
        <div class="mx-auto mb-3 flex h-24 w-24 animate-floaty items-center justify-center rounded-3xl bg-white text-5xl shadow-lg shadow-candy-pink/20">
            🧊🥤
        </div>
        <p class="text-sm font-semibold tracking-wide text-candy-pink">เกม AI สุดสนุก</p>
        <h1 class="mt-1 text-4xl font-bold leading-tight text-slate-800">
            AI Future <span class="text-candy-pink">Slush</span> Avatar
        </h1>
        <p class="mt-2 text-base text-slate-500">
            ถ่ายรูป แล้วให้ AI สร้าง “ตัวตนในอนาคต” พร้อมรสสเลอปี้และคูปองส่วนลด!
        </p>
    </div>

    {{-- 3 steps --}}
    <div class="mt-7 grid grid-cols-3 gap-3">
        @foreach ([['📸','ถ่ายรูป'], ['✨','AI สร้าง Avatar'], ['🎟️','รับคูปอง']] as $i => $step)
            <div class="flex flex-col items-center rounded-2xl bg-white/80 p-3 text-center shadow-sm">
                <div class="text-3xl">{{ $step[0] }}</div>
                <div class="mt-1 text-xs font-semibold text-slate-600">{{ $i + 1 }}. {{ $step[1] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Start button --}}
    <a href="{{ route('play.upload') }}" wire:navigate
       class="mt-8 flex items-center justify-center gap-2 rounded-full bg-candy-pink px-6 py-5 text-xl font-bold text-white shadow-lg shadow-candy-pink/40 transition active:scale-95">
        🚀 เริ่มสร้าง Avatar ของฉัน
    </a>

    {{-- Store QR --}}
    <div class="mt-auto pt-10 text-center">
        <p class="mb-3 text-sm text-slate-400">สแกนเพื่อเล่นบนมือถือ</p>
        <div class="mx-auto inline-flex rounded-2xl bg-white p-3 shadow-md">
            <img src="{{ \App\Support\Qr::dataUri($storeUrl, 150) }}" alt="QR หน้าร้าน" width="150" height="150">
        </div>
    </div>
</div>
