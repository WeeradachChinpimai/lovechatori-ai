<?php

use App\Models\SlushSession;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $photo;
    public bool $consent = false;

    public function rules(): array
    {
        return [
            'photo' => [
                'required', 'image',
                'mimes:'.implode(',', config('slush.accepted_mimes')),
                'max:'.config('slush.max_upload_kb'),
            ],
            'consent' => ['accepted'],
        ];
    }

    protected array $messages = [
        'photo.required' => 'กรุณาถ่ายหรือเลือกรูปก่อนนะ',
        'photo.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
        'photo.mimes' => 'รองรับเฉพาะไฟล์ jpg, png, webp',
        'photo.max' => 'ขนาดรูปต้องไม่เกิน 5MB',
        'consent.accepted' => 'กรุณายอมรับเงื่อนไขก่อนเริ่มเล่น',
    ];

    public function submit()
    {
        $this->validate();

        $path = $this->photo->store('uploads', 'public');

        $session = SlushSession::create([
            'session_uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'uploaded_image_path' => $path,
        ]);

        return $this->redirect(route('play.processing', $session->session_uuid), navigate: true);
    }
};
?>

<div class="flex flex-1 flex-col">
    <a href="{{ route('play.landing') }}" wire:navigate class="mb-2 text-sm text-slate-400">‹ กลับ</a>

    <h1 class="text-2xl font-bold text-slate-800">ถ่ายรูปใบหน้าของคุณ 📸</h1>
    <p class="mt-1 text-sm text-slate-500">ถ่ายหน้าตรง แสงสว่าง ยิ้มได้เลย!</p>

    <form wire:submit="submit" class="mt-5 flex flex-1 flex-col"
          x-data="{
              cam: false, stream: null, facing: 'user', camError: '',
              async start() {
                  this.camError = '';
                  if (!navigator.mediaDevices?.getUserMedia) {
                      this.camError = 'อุปกรณ์นี้เปิดกล้องในเบราว์เซอร์ไม่ได้ — กรุณากดอัปโหลดรูป';
                      return;
                  }
                  try {
                      this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: this.facing }, audio: false });
                      this.cam = true;
                      await this.$nextTick();
                      this.$refs.video.srcObject = this.stream;
                      await this.$refs.video.play();
                  } catch (e) {
                      this.cam = false;
                      this.camError = 'เปิดกล้องไม่ได้ — กรุณาอนุญาตการใช้กล้อง หรือกดอัปโหลดรูปแทน';
                  }
              },
              stop() {
                  if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                  this.cam = false;
              },
              async flip() {
                  this.facing = this.facing === 'user' ? 'environment' : 'user';
                  if (this.cam) { this.stop(); await this.start(); }
              },
              // Downscale any source (camera frame or picked file) to a small
              // JPEG so huge phone photos upload fast and never hit size limits.
              async toSmallBlob(src, w, h) {
                  const MAX = 1024;
                  let dw = w, dh = h;
                  if (Math.max(w, h) > MAX) { const r = MAX / Math.max(w, h); dw = Math.round(w * r); dh = Math.round(h * r); }
                  const c = document.createElement('canvas');
                  c.width = dw; c.height = dh;
                  c.getContext('2d').drawImage(src, 0, 0, dw, dh);
                  return await new Promise(r => c.toBlob(r, 'image/jpeg', 0.85));
              },
              async shoot() {
                  const v = this.$refs.video;
                  if (!v || !v.videoWidth) return;
                  const blob = await this.toSmallBlob(v, v.videoWidth, v.videoHeight);
                  this.stop();
                  this.$wire.upload('photo', new File([blob], 'camera.jpg', { type: 'image/jpeg' }));
              },
              async pick(e) {
                  const f = e.target.files[0];
                  if (!f) return;
                  this.camError = '';
                  try {
                      const img = await new Promise((res, rej) => {
                          const i = new Image();
                          i.onload = () => res(i);
                          i.onerror = () => rej();
                          i.src = URL.createObjectURL(f);
                      });
                      const blob = await this.toSmallBlob(img, img.naturalWidth, img.naturalHeight);
                      URL.revokeObjectURL(img.src);
                      this.$wire.upload('photo', new File([blob], 'upload.jpg', { type: 'image/jpeg' }));
                  } catch (err) {
                      this.camError = 'เปิดรูปนี้ไม่ได้ ลองรูปอื่นหรือถ่ายใหม่นะ';
                  }
                  e.target.value = '';
              },
          }"
          x-on:livewire:navigating.window="stop()">
        {{-- Preview / live camera --}}
        <div class="relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed border-candy-pink/40 bg-white/70">
            {{-- live camera feed --}}
            <video x-ref="video" x-show="cam" autoplay playsinline muted
                   class="absolute inset-0 h-full w-full object-cover" :class="facing === 'user' && 'scale-x-[-1]'"></video>
            <button type="button" x-show="cam" x-on:click="flip()"
                    class="absolute right-3 top-3 z-10 rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-slate-500 shadow">
                🔄 สลับกล้อง
            </button>

            {{-- captured photo / empty placeholder (hidden while camera is live) --}}
            <div x-show="!cam" class="absolute inset-0 flex items-center justify-center">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="preview" class="absolute inset-0 h-full w-full object-cover">
                    <button type="button" wire:click="$set('photo', null)"
                            class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-slate-500 shadow">
                        เปลี่ยนรูป
                    </button>
                @else
                    <div class="px-6 text-center text-slate-400">
                        <div class="text-5xl">🙂</div>
                        <p class="mt-2 text-sm">ยังไม่มีรูป — ถ่ายหรืออัปโหลดได้เลย</p>
                    </div>
                @endif
            </div>

            <div wire:loading wire:target="photo" class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 text-candy-pink">
                <span class="animate-pulse text-sm font-semibold">กำลังอัปโหลด…</span>
            </div>
        </div>

        @error('photo') <p class="mt-2 text-center text-sm text-red-500">{{ $message }}</p> @enderror
        <p x-show="camError" x-text="camError" class="mt-2 text-center text-sm text-red-500" style="display:none"></p>

        {{-- Capture & upload buttons (idle) --}}
        <div x-show="!cam" class="mt-4 grid grid-cols-2 gap-3">
            <button type="button" x-on:click="start()"
                    class="flex items-center justify-center gap-2 rounded-2xl bg-candy-blue px-4 py-4 font-bold text-white shadow active:scale-95">
                📷 ถ่ายรูป
            </button>
            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-white px-4 py-4 font-bold text-candy-pink shadow active:scale-95">
                🖼️ อัปโหลด
                <input type="file" accept="image/*" class="hidden" x-on:change="pick($event)">
            </label>
        </div>

        {{-- Shutter & cancel (camera live) --}}
        <div x-show="cam" class="mt-4 grid grid-cols-2 gap-3" style="display:none">
            <button type="button" x-on:click="shoot()"
                    class="flex items-center justify-center gap-2 rounded-2xl bg-candy-pink px-4 py-4 font-bold text-white shadow active:scale-95">
                📸 ถ่าย!
            </button>
            <button type="button" x-on:click="stop()"
                    class="flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-4 font-bold text-slate-500 shadow active:scale-95">
                ยกเลิก
            </button>
        </div>

        {{-- Consent --}}
        <label class="mt-5 flex items-start gap-3 rounded-2xl bg-white/70 p-4 text-sm text-slate-600">
            <input type="checkbox" wire:model="consent" class="mt-0.5 h-5 w-5 shrink-0 rounded accent-candy-pink">
            <span>รูปของคุณจะใช้เพื่อสร้าง Avatar เท่านั้น ไม่เก็บข้อมูลส่วนตัว และจะถูกลบอัตโนมัติภายใน {{ config('slush.image_retention_hours') }} ชั่วโมง</span>
        </label>
        @error('consent') <p class="mt-2 text-center text-sm text-red-500">{{ $message }}</p> @enderror

        {{-- Submit --}}
        <button type="submit" wire:loading.attr="disabled" wire:target="submit,photo"
                class="mt-6 flex items-center justify-center gap-2 rounded-full bg-candy-pink px-6 py-5 text-xl font-bold text-white shadow-lg shadow-candy-pink/40 transition active:scale-95 disabled:opacity-50">
            <span wire:loading.remove wire:target="submit">✨ สร้าง Avatar เลย</span>
            <span wire:loading wire:target="submit">กำลังเริ่ม…</span>
        </button>
    </form>
</div>
