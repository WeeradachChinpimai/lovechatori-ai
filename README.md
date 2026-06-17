# 🧊🥤 AI Future Slush Avatar

MVP เกม AI หน้าร้านสำหรับเด็กและครอบครัว: ลูกค้าสแกน QR → ถ่าย/อัปโหลดรูป → AI สร้าง Avatar
อนาคตแนว 3D/Anime พร้อมจับคู่รสสเลอปี้และแจกคูปองส่วนลด จบ flow ภายใน ~20 วินาที

Stack: **Laravel 12 · Livewire 4 (single-file components) · Alpine (bundled) · Tailwind (Play CDN) · endroid/qr-code**

> หมายเหตุ: ขอ "Laravel 13" แต่เวอร์ชัน stable ล่าสุดคือ Laravel 12 จึงใช้ 12 (ใกล้เคียงที่สุด)

## เริ่มใช้งาน

```bash
composer install
php artisan migrate
php artisan storage:link
php artisan serve
```

เปิด `http://localhost:8000` → redirect ไป `/play`

## หน้า / เส้นทาง

| Route | คอมโพเนนต์ | คำอธิบาย |
|---|---|---|
| `/play` | `play-landing` | หน้าแรก + QR หน้าร้าน |
| `/play/upload` | `upload-photo` | ถ่าย/อัปโหลดรูป + consent + preview |
| `/play/processing/{uuid}` | `processing-avatar` | Loading + เรียก AI แล้ว redirect |
| `/play/result/{uuid}` | `result-avatar` | Avatar + คูปอง + QR + บันทึก/แชร์ |
| `/staff/coupon` | `staff-coupon-redeem` | เจ้าหน้าที่กรอกรหัสคูปองเพื่อใช้สิทธิ์ |
| `/admin/dashboard` | `admin-dashboard` | สถิติ + รสยอดนิยม + Export CSV |
| `/admin/sessions` | `admin-session-list` | รายการทั้งหมด + ค้นหา + แบ่งหน้า |

Livewire 4 SFC อยู่ที่ `resources/views/components/⚡*.blade.php`

## การตั้งค่า AI (`.env`)

```env
ANTHROPIC_API_KEY=        # ว่าง = รันแบบ offline (fallback สนุก + SVG avatar)
ANTHROPIC_MODEL=claude-haiku-4-5-20251001
AVATAR_IMAGE_ENDPOINT=    # endpoint text-to-image (คืน PNG bytes); ว่าง = ใช้ SVG poster
AVATAR_IMAGE_KEY=
SLUSH_STAFF_USER=staff    # HTTP Basic auth ของหน้า staff/admin
SLUSH_STAFF_PASSWORD=     # ว่าง = ไม่ล็อก (สำหรับ dev)
SLUSH_IMAGE_RETENTION_HOURS=24
```

**Graceful fallback:** `App\Services\AvatarAiService` จะวิเคราะห์ด้วย Claude vision เมื่อมี API key
ถ้าไม่มี key หรือเรียกไม่สำเร็จ จะใช้ persona สุ่มแบบสนุก (kid-safe) และสร้าง Avatar เป็น SVG poster
(`App\Support\SlushAvatarPoster`) เสมอ — ผู้เล่นจึงได้ผลลัพธ์และคูปองทุกครั้ง

## ความเป็นส่วนตัว / ความปลอดภัย

- ไม่ต้องสมัครสมาชิก ไม่เก็บชื่อ/เบอร์/อีเมล
- Validate รูป: jpg/png/webp, ≤ 5MB
- มีข้อความยินยอมก่อนอัปโหลด
- AI ถูกสั่งห้ามวิเคราะห์ข้อมูลอ่อนไหว (อายุจริง/เพศ/เชื้อชาติ/สุขภาพ) — เน้นคำอธิบายเชิงสนุกเท่านั้น
- ลบรูปต้นฉบับอัตโนมัติ: `php artisan slush:purge-photos` (ตั้ง schedule รายชั่วโมงไว้แล้ว — รัน `php artisan schedule:work` หรือ cron)

## ฐานข้อมูล

- `slush_sessions` — session_uuid, status, uploaded/generated path, ai_response_json, character_name, slush_flavor, coupon_code/status
- `coupons` — code (`SLUSH-XXXX`), discount_type (percent/fixed/free_topping), discount_value, status, used_at, expired_at, slush_session_id

## หมายเหตุ Production

- Tailwind ใช้ Play CDN เพื่อให้ MVP รันได้โดยไม่ต้อง build (Node เก่าก็ได้) — ก่อนขึ้น production ควรเปลี่ยนไปใช้ Tailwind ผ่าน Vite
- ตั้ง `APP_URL` ให้เป็นโดเมนจริง เพื่อให้ QR หน้าร้านชี้ถูก
- ตั้ง `SLUSH_STAFF_PASSWORD` ก่อนเปิดใช้งานจริง
