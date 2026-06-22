<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores the (already client-resized) photo straight onto the media disk,
 * bypassing Livewire's temporary-upload mechanism. This avoids the
 * S3 metadata issues with Livewire temp uploads and works across instances.
 */
class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required', 'image',
                'mimes:'.implode(',', config('slush.accepted_mimes')),
                'max:'.config('slush.max_upload_kb'),
            ],
        ], [
            'image.required' => 'กรุณาเลือกรูปก่อนนะ',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพเท่านั้น',
            'image.mimes' => 'รองรับเฉพาะ jpg, png, webp',
            'image.max' => 'รูปใหญ่เกินไป',
        ]);

        // Uploaded photos stay private; only the generated avatar is public.
        $path = $request->file('image')->store('uploads', config('slush.media_disk'));

        return response()->json(['path' => $path]);
    }
}
