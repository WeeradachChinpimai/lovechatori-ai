<?php

use App\Models\Coupon;
use App\Models\SlushSession;
use App\Services\AvatarAiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public string $uuid;

    public function mount(string $uuid)
    {
        $this->uuid = $uuid;

        $session = SlushSession::where('session_uuid', $uuid)->firstOrFail();

        // Already processed (e.g. page revisit) -> jump straight to result.
        if ($session->status === 'done') {
            return $this->redirect(route('play.result', $uuid), navigate: true);
        }
    }

    public function process(AvatarAiService $ai)
    {
        $session = SlushSession::where('session_uuid', $this->uuid)->firstOrFail();

        if ($session->status === 'done') {
            return $this->redirect(route('play.result', $this->uuid), navigate: true);
        }

        $session->update(['status' => 'processing']);

        // Read the photo bytes from the configured disk (local or S3).
        $disk = Storage::disk(config('slush.media_disk'));
        $bytes = null;
        $mime = 'image/jpeg';
        if ($session->uploaded_image_path && $disk->exists($session->uploaded_image_path)) {
            $bytes = $disk->get($session->uploaded_image_path);
            $mime = $disk->mimeType($session->uploaded_image_path) ?: 'image/jpeg';
        }

        try {
            $analysis = $ai->analyze($bytes, $mime, $this->uuid);
            $avatar = $ai->generateAvatar($bytes, $mime, $analysis);
            $usedFallback = $avatar['fallback'];
        } catch (\Throwable $e) {
            // Last-resort safety net: never block the player, still hand out a coupon.
            Log::error('Slush processing failed, using full fallback.', ['error' => $e->getMessage()]);
            $analysis = $ai->analyze(null, $mime, $this->uuid);
            $avatar = $ai->generateAvatar(null, $mime, $analysis);
            $usedFallback = true;
        }

        $coupon = Coupon::createFromAnalysis($analysis, $session->id);

        $session->update([
            'status' => 'done',
            'generated_avatar_path' => $avatar['path'],
            'used_fallback' => $usedFallback,
            'ai_response_json' => $analysis,
            'character_name' => $analysis['character_name'] ?? null,
            'slush_flavor' => $analysis['slush_flavor'] ?? null,
            'coupon_code' => $coupon->code,
            'coupon_status' => 'unused',
        ]);

        return $this->redirect(route('play.result', $this->uuid), navigate: true);
    }
};
?>

<div class="flex flex-1 flex-col items-center justify-center text-center"
     x-data="{
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
            this.elapsed += 0.1;
            // Asymptotic ease toward 95% — always moving, never stuck, never
            // hits 100% until the real redirect fires.
            this.progress = Math.min(95, 95 * (1 - Math.exp(-this.elapsed / 12)));
            this.i = Math.min(this.messages.length - 1, Math.floor(this.elapsed / 4));
        },
     }"
     x-init="
        setInterval(() => tick(), 100);
        setTimeout(() => $wire.process(), 2600);
     ">

    {{-- Spinning slush cup --}}
    <div class="relative flex h-40 w-40 items-center justify-center">
        <div class="absolute inset-0 animate-spinslow rounded-full border-4 border-dashed border-candy-pink/40"></div>
        <div class="animate-floaty text-7xl">🥤</div>
        <div class="absolute -right-1 top-2 text-3xl animate-pop">✨</div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-800">กำลังสร้าง Avatar ของคุณ</h2>
    <p class="mt-3 h-6 text-base text-candy-pink transition" x-text="messages[i]"></p>

    {{-- live progress bar --}}
    <div class="mt-6 h-3 w-64 overflow-hidden rounded-full bg-white shadow-inner">
        <div class="h-full rounded-full bg-gradient-to-r from-candy-blue via-candy-pink to-candy-yellow transition-all duration-100 ease-out"
             x-bind:style="`width: ${progress}%`"></div>
    </div>

    {{-- elapsed timer --}}
    <p class="mt-3 text-lg font-bold text-slate-600">
        ⏱️ <span x-text="Math.floor(elapsed)"></span> วินาที
    </p>

    <p class="mt-4 px-6 text-xs text-slate-400">
        การสร้างภาพด้วย AI ใช้เวลาสักครู่ (ปกติ 15–30 วินาที) — ยังทำงานอยู่ อย่าเพิ่งปิดหน้านี้นะ 💛
    </p>
</div>
