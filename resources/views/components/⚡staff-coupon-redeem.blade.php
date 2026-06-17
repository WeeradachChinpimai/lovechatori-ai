<?php

use App\Models\Coupon;
use App\Models\SlushSession;
use Livewire\Component;

new class extends Component
{
    public string $code = '';
    public ?Coupon $coupon = null;
    public string $message = '';
    public string $messageType = ''; // info | success | error

    public function lookup(): void
    {
        $this->reset('coupon', 'message', 'messageType');
        $code = strtoupper(trim($this->code));

        if ($code === '') {
            $this->setMessage('กรุณากรอกรหัสคูปอง', 'error');

            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            $this->setMessage('ไม่พบคูปองนี้ในระบบ', 'error');

            return;
        }

        $this->coupon = $coupon;

        if ($coupon->status === 'used') {
            $this->setMessage('คูปองนี้ถูกใช้ไปแล้ว เมื่อ '.optional($coupon->used_at)->format('d/m/Y H:i'), 'error');
        } elseif ($coupon->isExpired()) {
            $this->setMessage('คูปองนี้หมดอายุแล้ว', 'error');
        } else {
            $this->setMessage('พบคูปองที่ใช้ได้ — กดยืนยันเพื่อใช้สิทธิ์', 'info');
        }
    }

    public function redeem(): void
    {
        if (! $this->coupon) {
            return;
        }

        $coupon = $this->coupon->fresh();

        if (! $coupon || ! $coupon->isRedeemable()) {
            $this->setMessage('ใช้คูปองนี้ไม่ได้ (อาจถูกใช้ไปแล้วหรือหมดอายุ)', 'error');
            $this->coupon = $coupon;

            return;
        }

        $coupon->update(['status' => 'used', 'used_at' => now()]);

        SlushSession::where('coupon_code', $coupon->code)
            ->update(['coupon_status' => 'used']);

        $this->coupon = $coupon->fresh();
        $this->setMessage('✅ ใช้คูปองสำเร็จ! มอบส่วนลดให้ลูกค้าได้เลย', 'success');
    }

    public function resetForm(): void
    {
        $this->reset('code', 'coupon', 'message', 'messageType');
    }

    protected function setMessage(string $message, string $type): void
    {
        $this->message = $message;
        $this->messageType = $type;
    }
};
?>

<div class="flex flex-1 flex-col">
    <div class="rounded-2xl bg-white/80 p-4 text-center shadow-sm">
        <h1 class="text-xl font-bold text-slate-800">🎟️ ระบบใช้คูปอง (เจ้าหน้าที่)</h1>
        <p class="mt-1 text-sm text-slate-500">กรอกรหัสคูปองของลูกค้าเพื่อใช้สิทธิ์</p>
    </div>

    <form wire:submit="lookup" class="mt-5">
        <input type="text" wire:model="code" autocomplete="off" autocapitalize="characters"
               placeholder="SLUSH-AB12CD"
               class="w-full rounded-2xl border-2 border-candy-pink/30 bg-white px-4 py-4 text-center text-2xl font-bold uppercase tracking-widest text-slate-700 focus:border-candy-pink focus:outline-none">
        <button type="submit"
                class="mt-3 w-full rounded-full bg-candy-blue px-6 py-4 text-lg font-bold text-white shadow active:scale-95">
            🔍 ตรวจสอบคูปอง
        </button>
    </form>

    @if ($message)
        <div class="mt-4 rounded-2xl p-4 text-center font-semibold
            @class([
                'bg-green-100 text-green-700' => $messageType === 'success',
                'bg-red-100 text-red-600' => $messageType === 'error',
                'bg-blue-100 text-blue-700' => $messageType === 'info',
            ])">
            {{ $message }}
        </div>
    @endif

    @if ($coupon)
        <div class="mt-4 rounded-2xl bg-white p-5 shadow">
            <div class="flex items-center justify-between">
                <span class="font-mono text-lg font-bold tracking-wider text-slate-700">{{ $coupon->code }}</span>
                <span @class([
                    'rounded-full px-3 py-1 text-sm font-bold',
                    'bg-green-100 text-green-700' => $coupon->status === 'unused' && ! $coupon->isExpired(),
                    'bg-slate-200 text-slate-500' => $coupon->status === 'used' || $coupon->isExpired(),
                ])>{{ $coupon->status === 'used' ? 'ใช้แล้ว' : ($coupon->isExpired() ? 'หมดอายุ' : 'พร้อมใช้') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-candy-pink">{{ $coupon->discount_label }}</p>
            <p class="mt-1 text-sm text-slate-400">หมดอายุ {{ optional($coupon->expired_at)->format('d/m/Y H:i') }} น.</p>

            @if ($coupon->isRedeemable())
                <button type="button" wire:click="redeem"
                        class="mt-4 w-full rounded-full bg-candy-pink px-6 py-4 text-lg font-bold text-white shadow active:scale-95">
                    ✅ ยืนยันใช้สิทธิ์
                </button>
            @endif
        </div>
    @endif

    <button type="button" wire:click="resetForm"
            class="mt-4 w-full rounded-full bg-white px-6 py-3 font-bold text-slate-500 shadow active:scale-95">
        ล้างและเริ่มใหม่
    </button>
</div>
