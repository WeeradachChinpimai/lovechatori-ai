<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SlushSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ai_response_json' => 'array',
        'used_fallback' => 'boolean',
        'image_deleted_at' => 'datetime',
    ];

    public function coupon(): HasOne
    {
        return $this->hasOne(Coupon::class);
    }

    public function getRouteKeyName(): string
    {
        return 'session_uuid';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
