<?php

use App\Models\SlushSession;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    // S3/disk path of the already-uploaded photo (set via direct upload, not Livewire temp).
    public ?string $photo = null;
    public bool $consent = true;

    public function rules(): array
    {
        return [
            'photo' => ['required', 'string'],
            'consent' => ['accepted'],
        ];
    }

    protected array $messages = [
        'photo.required' => 'กรุณาถ่ายหรือเลือกรูปก่อนนะ',
        'consent.accepted' => 'กรุณายอมรับเงื่อนไขก่อนเริ่มเล่น',
    ];

    public function submit()
    {
        $this->validate();

        $session = SlushSession::create([
            'session_uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'uploaded_image_path' => $this->photo,
        ]);

        return $this->redirect(route('play.processing', $session->session_uuid), navigate: true);
    }
};
?>

<div class="flex flex-1 flex-col">
    {{-- App bar: fixed back button on the left, title aligned on the same row --}}
    <div class="relative flex min-h-11 items-center justify-center">
        <a href="{{ route('play.landing') }}" wire:navigate aria-label="กลับ"
           class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-chillo-blue shadow-soft ring-1 ring-soft transition active:scale-95">
            <x-icon name="arrow-left" class="h-5 w-5" />
        </a>
        <h1 class="px-12 text-center text-xl font-extrabold leading-tight text-chillo-blue">ถ่ายรูป<span class="text-chillo-orange">ใบหน้า</span>ของคุณ</h1>
    </div>
    <p class="mt-1 text-center text-sm text-ink-soft">ถ่ายหน้าตรง แสงสว่าง ยิ้มได้เลย!</p>

    <form wire:submit="submit" class="mt-5 flex flex-1 flex-col"
          x-data="{
              cam: false, stream: null, facing: 'user', camError: '', busy: false, previewUrl: '',
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
              clear() {
                  if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                  this.previewUrl = '';
                  this.$wire.set('photo', null);
              },
              // Downscale any source to a small JPEG so huge phone photos upload fast.
              async toSmallBlob(src, w, h) {
                  const MAX = 1024;
                  let dw = w, dh = h;
                  if (Math.max(w, h) > MAX) { const r = MAX / Math.max(w, h); dw = Math.round(w * r); dh = Math.round(h * r); }
                  const c = document.createElement('canvas');
                  c.width = dw; c.height = dh;
                  c.getContext('2d').drawImage(src, 0, 0, dw, dh);
                  return await new Promise(r => c.toBlob(r, 'image/jpeg', 0.85));
              },
              // Upload straight to the server (-> S3), bypassing Livewire temp uploads.
              async upload(blob, name) {
                  if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                  this.previewUrl = URL.createObjectURL(blob);
                  this.busy = true;
                  try {
                      const fd = new FormData();
                      fd.append('image', new File([blob], name, { type: 'image/jpeg' }));
                      const res = await fetch(@js(route('play.upload-image')), {
                          method: 'POST',
                          headers: {
                              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                              'Accept': 'application/json',
                          },
                          body: fd,
                      });
                      if (!res.ok) throw new Error('upload failed');
                      const data = await res.json();
                      this.$wire.set('photo', data.path);
                  } catch (err) {
                      this.clear();
                      this.camError = 'อัปโหลดไม่สำเร็จ ลองใหม่อีกครั้งนะ';
                  } finally {
                      this.busy = false;
                  }
              },
              async shoot() {
                  const v = this.$refs.video;
                  if (!v || !v.videoWidth) return;
                  this.camError = '';
                  const blob = await this.toSmallBlob(v, v.videoWidth, v.videoHeight);
                  this.stop();
                  this.upload(blob, 'camera.jpg');
              },
              async pick(e) {
                  const f = e.target.files[0];
                  if (!f) return;
                  this.camError = '';
                  this.busy = true;
                  try {
                      const img = await new Promise((res, rej) => {
                          const i = new Image();
                          i.onload = () => res(i);
                          i.onerror = () => rej();
                          i.src = URL.createObjectURL(f);
                      });
                      const blob = await this.toSmallBlob(img, img.naturalWidth, img.naturalHeight);
                      URL.revokeObjectURL(img.src);
                      await this.upload(blob, 'upload.jpg');
                  } catch (err) {
                      this.busy = false;
                      this.camError = 'เปิดรูปนี้ไม่ได้ ลองรูปอื่นหรือถ่ายใหม่นะ';
                  }
                  e.target.value = '';
              },
          }"
          x-on:livewire:navigating.window="stop()">
        {{-- Preview / live camera --}}
        <div class="relative">
        {{-- decorative CHILLO slushy cup peeking from the bottom-left corner --}}
        <img src="{{ asset('element-1-camera.webp') }}" alt="" aria-hidden="true"
             class="pointer-events-none absolute -bottom-6 -left-6 z-10 w-24 select-none drop-shadow-xl">
        <div class="relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-[32px] border-2 border-dashed border-y-chillo-sky/40 border-l-chillo-blue border-r-chillo-orange bg-[#F7FBFF]">
            {{-- live camera feed --}}
            <video x-ref="video" x-show="cam" autoplay playsinline muted
                   class="absolute inset-0 h-full w-full object-cover" :class="facing === 'user' && 'scale-x-[-1]'"></video>
            <button type="button" x-show="cam" x-on:click="flip()"
                    class="absolute right-3 top-3 z-10 inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1.5 text-sm font-bold text-chillo-blue shadow ring-1 ring-soft">
                <x-icon name="rotate-ccw" class="h-4 w-4" /> สลับกล้อง
            </button>

            {{-- captured preview (client-side object URL) --}}
            <template x-if="!cam && previewUrl">
                <div class="absolute inset-0">
                    <img :src="previewUrl" alt="preview" class="h-full w-full object-cover">
                    <button type="button" x-on:click="clear()"
                            class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1.5 text-sm font-bold text-chillo-blue shadow ring-1 ring-soft">
                        <x-icon name="x" class="h-4 w-4" /> เปลี่ยนรูป
                    </button>
                </div>
            </template>

            {{-- empty placeholder --}}
            <div x-show="!cam && !previewUrl" class="flex flex-col items-center px-6 text-center text-ink-faint">
                <span class="flex h-28 w-28 items-center justify-center rounded-full bg-chillo-blue-light text-chillo-blue/50">
                    <x-icon name="scan-face" class="h-16 w-16" />
                </span>
                <button type="button" x-on:click="start()" aria-label="เปิดกล้อง"
                        class="-mt-5 inline-flex h-12 w-12 items-center justify-center rounded-full bg-chillo-blue text-white shadow-button ring-4 ring-white transition active:scale-95">
                    <x-icon name="camera" class="h-5 w-5" />
                </button>
                <p class="mt-3 text-sm">ยังไม่มีรูป — ถ่ายหรืออัปโหลดได้เลย</p>
            </div>

            {{-- preloader: covers resize + upload --}}
            <div x-show="busy" x-transition.opacity style="display:none"
                 class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-white/90 text-chillo-orange">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-chillo-orange/25 border-t-chillo-orange"></div>
                <span class="text-sm font-bold">กำลังเตรียมรูป…</span>
            </div>
        </div>
        </div>

        @error('photo') <p class="mt-2 text-center text-sm text-[color:var(--danger)]">{{ $message }}</p> @enderror
        <p x-show="camError" x-text="camError" class="mt-2 text-center text-sm text-[color:var(--danger)]" style="display:none"></p>

        {{-- Capture & upload buttons (idle) --}}
        <div x-show="!cam" class="mt-4 grid grid-cols-2 gap-3">
            <button type="button" x-on:click="start()"
                    class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-chillo-blue px-4 font-extrabold text-white shadow-soft transition active:scale-95">
                <x-icon name="camera" class="h-5 w-5" /> ถ่ายรูป
            </button>
            <label class="inline-flex min-h-[52px] cursor-pointer items-center justify-center gap-2 rounded-2xl bg-chillo-orange px-4 font-extrabold text-white shadow-button transition active:scale-95">
                <x-icon name="image-plus" class="h-5 w-5" /> อัปโหลด
                <input type="file" accept="image/*" class="hidden" x-on:change="pick($event)">
            </label>
        </div>

        {{-- Shutter & cancel (camera live) --}}
        <div x-show="cam" class="mt-4 grid grid-cols-2 gap-3" style="display:none">
            <button type="button" x-on:click="shoot()"
                    class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-chillo-orange px-4 font-extrabold text-white shadow-button transition active:scale-95">
                <x-icon name="camera" class="h-5 w-5" /> ถ่าย!
            </button>
            <button type="button" x-on:click="stop()"
                    class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-white px-4 font-extrabold text-ink-soft shadow-soft ring-1 ring-soft transition active:scale-95">
                <x-icon name="x" class="h-5 w-5" /> ยกเลิก
            </button>
        </div>

        {{-- Consent --}}
        <label class="mt-5 flex items-start gap-3 rounded-2xl border border-soft bg-white p-4 text-sm text-ink-soft shadow-soft">
            <span class="mt-0.5 text-chillo-blue"><x-icon name="shield-check" class="h-6 w-6" /></span>
            <input type="checkbox" wire:model="consent" class="mt-0.5 h-5 w-5 shrink-0 rounded accent-chillo-orange">
            <span>รูปของคุณจะใช้เพื่อสร้าง Avatar เท่านั้น ไม่เก็บข้อมูลส่วนตัว และจะถูกลบอัตโนมัติภายใน <span class="font-bold text-ink">{{ config('slush.image_retention_hours') }} ชั่วโมง</span></span>
        </label>
        @error('consent') <p class="mt-2 text-center text-sm text-[color:var(--danger)]">{{ $message }}</p> @enderror

        {{-- Submit --}}
        <button type="submit" x-bind:disabled="busy || !$wire.photo" wire:loading.attr="disabled" wire:target="submit"
                class="relative mt-6 inline-flex min-h-[56px] items-center justify-center gap-2 rounded-full bg-chillo-orange px-6 text-lg font-extrabold text-white shadow-button transition active:scale-[0.98] disabled:opacity-50 disabled:active:scale-100">
            <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5" /> สร้าง Avatar เลย</span>
            <span wire:loading wire:target="submit">กำลังเริ่ม…</span>
            <span wire:loading.remove wire:target="submit" class="absolute right-2 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/25">
                <x-icon name="chevron-right" class="h-5 w-5" />
            </span>
        </button>

        <p class="mt-3 text-center text-xs leading-snug text-ink-faint">
            การกดปุ่ม “สร้าง Avatar เลย” ถือว่าคุณยอมรับเงื่อนไขข้างต้น
        </p>
    </form>
</div>
