<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'used_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function slushSession(): BelongsTo
    {
        return $this->belongsTo(SlushSession::class);
    }

    /**
     * Generate a fun, readable, unique coupon code e.g. SLUSH-AB12CD.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'SLUSH-'.Str::upper(Str::random(2)).random_int(10, 99).Str::upper(Str::random(2));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Build a coupon row from the AI "coupon" label, linked to a session.
     * Expires at the end of today, matching the front-of-store flow.
     */
    public static function createFromAnalysis(array $analysis, ?int $slushSessionId = null): self
    {
        $label = (string) ($analysis['coupon'] ?? 'ลด 10%');

        [$type, $value] = match (true) {
            str_contains($label, 'ฟรี') => ['free_topping', null],
            preg_match('/(\d+)\s*%/u', $label, $m) === 1 => ['percent', (int) $m[1]],
            preg_match('/(\d+)/u', $label, $m) === 1 => ['fixed', (int) $m[1]],
            default => ['percent', 10],
        };

        return static::create([
            'code' => static::generateCode(),
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_label' => $label,
            'status' => 'unused',
            'expired_at' => now()->endOfDay(),
            'slush_session_id' => $slushSessionId,
        ]);
    }

    public function isRedeemable(): bool
    {
        if ($this->status === 'used') {
            return false;
        }

        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }
}
