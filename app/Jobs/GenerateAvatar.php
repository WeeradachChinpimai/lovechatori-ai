<?php

namespace App\Jobs;

use App\Models\Coupon;
use App\Models\SlushSession;
use App\Services\AvatarAiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Generates the avatar off the web request (queue) so the player's processing
 * screen can poll for status instead of holding a long, timeout-prone request.
 * The job is bulletproof: any failure still yields a fallback avatar + coupon.
 */
class GenerateAvatar implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 1;

    public function __construct(public string $uuid) {}

    public function handle(AvatarAiService $ai): void
    {
        $session = SlushSession::where('session_uuid', $this->uuid)->first();

        if (! $session || $session->status === 'done') {
            return;
        }

        $session->update(['status' => 'processing']);

        // Read the uploaded photo bytes from the configured disk (local or S3).
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
        } catch (Throwable $e) {
            // Never block the player — always produce a fallback result + coupon.
            Log::error('GenerateAvatar failed, using full fallback.', ['error' => $e->getMessage()]);
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
    }

    /** Backstop: if handle() itself dies (DB/storage), mark failed so the UI can react. */
    public function failed(?Throwable $e): void
    {
        SlushSession::where('session_uuid', $this->uuid)
            ->where('status', '!=', 'done')
            ->update(['status' => 'failed']);
    }
}
