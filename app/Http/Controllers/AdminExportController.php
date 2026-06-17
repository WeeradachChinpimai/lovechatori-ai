<?php

namespace App\Http\Controllers;

use App\Models\SlushSession;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    /**
     * Stream all sessions (with their coupon) as a CSV download.
     */
    public function __invoke(): StreamedResponse
    {
        $filename = 'slush-sessions-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Thai text opens correctly in Excel.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'id', 'session_uuid', 'status', 'character_name', 'slush_flavor',
                'coupon_code', 'coupon_status', 'discount_label', 'used_fallback', 'created_at',
            ]);

            SlushSession::with('coupon')->orderByDesc('id')->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->id,
                        $s->session_uuid,
                        $s->status,
                        $s->character_name,
                        $s->slush_flavor,
                        $s->coupon_code,
                        $s->coupon_status,
                        optional($s->coupon)->discount_label,
                        $s->used_fallback ? 'yes' : 'no',
                        optional($s->created_at)->toDateTimeString(),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
