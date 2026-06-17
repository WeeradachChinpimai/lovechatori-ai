<?php

namespace App\Console\Commands;

use App\Models\SlushSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeUploadedPhotos extends Command
{
    protected $signature = 'slush:purge-photos';

    protected $description = 'Delete original uploaded photos older than the configured retention window (privacy).';

    public function handle(): int
    {
        $hours = (int) config('slush.image_retention_hours', 24);
        $cutoff = now()->subHours($hours);

        $sessions = SlushSession::whereNotNull('uploaded_image_path')
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($sessions as $session) {
            if (Storage::disk('public')->exists($session->uploaded_image_path)) {
                Storage::disk('public')->delete($session->uploaded_image_path);
            }

            $session->update([
                'uploaded_image_path' => null,
                'image_deleted_at' => now(),
            ]);

            $deleted++;
        }

        $this->info("Purged {$deleted} uploaded photo(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
