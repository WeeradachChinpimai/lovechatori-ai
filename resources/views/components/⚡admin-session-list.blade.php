<?php

use App\Models\SlushSession;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $sessions = SlushSession::query()
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where('coupon_code', 'like', $term)
                    ->orWhere('character_name', 'like', $term)
                    ->orWhere('slush_flavor', 'like', $term);
            })
            ->latest()
            ->paginate(15);

        return ['sessions' => $sessions];
    }
};
?>

<div class="flex flex-1 flex-col">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-sm text-slate-400">‹ แดชบอร์ด</a>
        <a href="{{ route('admin.sessions.export') }}" class="rounded-full bg-candy-green px-3 py-1 text-xs font-bold text-white">⬇ Export CSV</a>
    </div>

    <h1 class="mt-2 text-2xl font-bold text-slate-800">📁 รายการเซสชันทั้งหมด</h1>

    <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหา รหัสคูปอง / ชื่อ / รสชาติ"
           class="mt-3 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm focus:border-candy-pink focus:outline-none">

    <div class="mt-4 space-y-2">
        @forelse ($sessions as $s)
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="font-bold text-slate-700">{{ $s->character_name ?? '—' }}</p>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                        'bg-green-100 text-green-700' => $s->status === 'done',
                        'bg-amber-100 text-amber-700' => in_array($s->status, ['pending', 'processing']),
                        'bg-red-100 text-red-600' => $s->status === 'failed',
                    ])>{{ $s->status }}</span>
                </div>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-400">
                    <span>🥤 {{ $s->slush_flavor ?? '-' }}</span>
                    <span>🎟️ {{ $s->coupon_code ?? '-' }} ({{ $s->coupon_status }})</span>
                    @if ($s->used_fallback) <span class="text-amber-500">fallback</span> @endif
                    <span>{{ $s->created_at->format('d/m H:i') }}</span>
                </div>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-6 text-center text-sm text-slate-400 shadow-sm">ยังไม่มีข้อมูล</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $sessions->links() }}
    </div>
</div>
