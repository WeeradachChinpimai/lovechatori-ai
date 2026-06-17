<?php

return [

    // Back-of-house (staff & admin) HTTP Basic credentials. Leave the
    // password empty to disable the gate during local development.
    'staff_user' => env('SLUSH_STAFF_USER', 'staff'),
    'staff_password' => env('SLUSH_STAFF_PASSWORD', ''),

    // Disk for uploaded photos + generated avatars.
    // Use 'public' for local dev; set SLUSH_MEDIA_DISK=s3 on production
    // (Laravel Cloud object storage) so files persist and are shared across instances.
    'media_disk' => env('SLUSH_MEDIA_DISK', 'public'),

    // How long the original uploaded photo is kept before auto-deletion.
    'image_retention_hours' => (int) env('SLUSH_IMAGE_RETENTION_HOURS', 24),

    // Max upload size in kilobytes (5 MB) and accepted mime types.
    'max_upload_kb' => 5120,
    'accepted_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
];
