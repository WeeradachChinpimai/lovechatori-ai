<?php

use App\Models\Coupon;
use App\Models\SlushSession;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'totalPlayers' => SlushSession::count(),
            'totalAvatars' => SlushSession::whereNotNull('generated_avatar_path')->count(),
            'couponsIssued' => Coupon::count(),
            'couponsUsed' => Coupon::where('status', 'used')->count(),
            'popularFlavors' => SlushSession::query()
                ->whereNotNull('slush_flavor')
                ->selectRaw('slush_flavor, COUNT(*) as total')
                ->groupBy('slush_flavor')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'recent' => SlushSession::latest()->limit(8)->get(),
        ];
    }
};
?>

<div class="flex flex-1 flex-col">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">📊 แดชบอร์ด</h1>
        <a href="{{ route('admin.sessions') }}" wire:navigate class="text-sm font-semibold text-candy-pink">ดูทั้งหมด ›</a>
    </div>

    {{-- Stat cards --}}
    <div class="mt-4 grid grid-cols-2 gap-3">
        @foreach ([
            ['👥', 'ผู้เล่นทั้งหมด', $totalPlayers, 'text-candy-blue'],
            ['🧑‍🎤', 'Avatar ที่สร้าง', $totalAvatars, 'text-candy-pink'],
            ['🎟️', 'คูปองที่แจก', $couponsIssued, 'text-candy-green'],
            ['✅', 'คูปองที่ใช้แล้ว', $couponsUsed, 'text-amber-500'],
        ] as $card)
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="text-2xl">{{ $card[0] }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $card[1] }}</div>
                <div class="text-3xl font-bold {{ $card[3] }}">{{ number_format($card[2]) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Popular flavors --}}
    <div class="mt-5 rounded-2xl bg-white p-4 shadow-sm">
        <h2 class="font-bold text-slate-700">🥤 รสสเลอปี้ยอดนิยม</h2>
        <div class="mt-3 space-y-2">
            @forelse ($popularFlavors as $flavor)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">{{ $flavor->slush_flavor }}</span>
                    <span class="rounded-full bg-candy-pink/10 px-3 py-0.5 font-semibold text-candy-pink">{{ $flavor->total }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">ยังไม่มีข้อมูล</p>
            @endforelse
        </div>
    </div>

    {{-- Recent --}}
    <div class="mt-5 rounded-2xl bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-700">🕒 รายการล่าสุด</h2>
            <a href="{{ route('admin.sessions.export') }}" class="rounded-full bg-candy-green px-3 py-1 text-xs font-bold text-white">⬇ Export CSV</a>
        </div>
        <div class="mt-3 divide-y divide-slate-100">
            @forelse ($recent as $s)
                <div class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <p class="font-semibold text-slate-700">{{ $s->character_name ?? '— กำลังประมวลผล —' }}</p>
                        <p class="text-xs text-slate-400">{{ $s->slush_flavor ?? '-' }} · {{ $s->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-mono text-xs text-slate-400">{{ $s->coupon_code ?? '-' }}</span>
                </div>
            @empty
                <p class="py-2 text-sm text-slate-400">ยังไม่มีผู้เล่น</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 pb-2">
        <a href="{{ route('staff.coupon') }}" wire:navigate class="rounded-full bg-white px-4 py-3 text-center font-bold text-candy-pink shadow active:scale-95">🎟️ ใช้คูปอง</a>
        <a href="{{ route('play.landing') }}" wire:navigate class="rounded-full bg-white px-4 py-3 text-center font-bold text-candy-blue shadow active:scale-95">🎮 หน้าเล่นเกม</a>
    </div>
</div>
