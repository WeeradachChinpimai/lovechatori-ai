<?php

namespace App\Services;

use App\Support\SlushAvatarPoster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Wraps the AI calls for the Slush Avatar game.
 *
 * Both the analysis and the avatar generation degrade gracefully: if no API
 * key is configured, or the provider errors out, we fall back to a fun random
 * analysis and a generated SVG poster so the player still gets a result and a
 * coupon within the 20 second budget.
 */
class AvatarAiService
{
    /**
     * Fun, kid-safe character archetypes used for the offline fallback and as
     * the example set the AI is asked to riff on. Never references age,
     * gender, race or health — only playful "slush vibes".
     */
    protected const ARCHETYPES = [
        [
            'character_name' => 'Captain Berry Frost',
            'future_role' => 'ผู้พิทักษ์เมืองน้ำแข็งสายสดใส',
            'personality' => 'ร่าเริง ชอบผจญภัย และมีพลังบวก',
            'slush_flavor' => 'Strawberry Frost',
            'slush_color' => 'pink',
            'special_power' => 'เปลี่ยนรอยยิ้มให้กลายเป็นพลังน้ำแข็งสีชมพู',
            'coupon' => 'ลด 10%',
            'short_caption' => 'วันนี้คุณคือฮีโร่สเลอปี้สายสดใส!',
        ],
        [
            'character_name' => 'Sky Blue Ranger',
            'future_role' => 'นักสำรวจท้องฟ้าแห่งเมืองน้ำแข็ง',
            'personality' => 'ใจเย็น มั่นใจ และชอบช่วยเหลือเพื่อน',
            'slush_flavor' => 'Blue Soda Splash',
            'slush_color' => 'blue',
            'special_power' => 'เรียกสายลมเย็นสดชื่นได้ทุกเมื่อ',
            'coupon' => 'ลด 15%',
            'short_caption' => 'พลังความเย็นสุดคูลอยู่ในมือคุณแล้ว!',
        ],
        [
            'character_name' => 'Lime Spark Hero',
            'future_role' => 'จอมพลังสวนสนุกสายกรีน',
            'personality' => 'สดใส กระฉับกระเฉง และมองโลกในแง่ดี',
            'slush_flavor' => 'Green Apple Pop',
            'slush_color' => 'green',
            'special_power' => 'ปลุกพลังสดชื่นให้ทุกคนรอบตัวยิ้มได้',
            'coupon' => 'ฟรีท็อปปิ้ง',
            'short_caption' => 'สดชื่นสุดพลังในเวอร์ชันอนาคตของคุณ!',
        ],
        [
            'character_name' => 'Sunny Mango Star',
            'future_role' => 'ดาวเด่นแห่งเทศกาลแสงอาทิตย์',
            'personality' => 'อบอุ่น เป็นมิตร และมีพลังบวกล้นเหลือ',
            'slush_flavor' => 'Mango Sunshine',
            'slush_color' => 'yellow',
            'special_power' => 'ส่องแสงอุ่น ๆ ให้ทุกวันสดใส',
            'coupon' => 'ลด 10%',
            'short_caption' => 'แสงอาทิตย์สดใสในแบบฉบับของคุณ!',
        ],
        [
            'character_name' => 'Grape Galaxy Guardian',
            'future_role' => 'ผู้พิทักษ์กาแล็กซีองุ่นม่วง',
            'personality' => 'ช่างฝัน สร้างสรรค์ และมีจินตนาการล้นฟ้า',
            'slush_flavor' => 'Grape Galaxy',
            'slush_color' => 'purple',
            'special_power' => 'เปิดประตูสู่จักรวาลแห่งความสนุก',
            'coupon' => 'ลด 15%',
            'short_caption' => 'จินตนาการของคุณคือพลังที่ยิ่งใหญ่!',
        ],
    ];

    /**
     * Analyse the uploaded face photo and return the fun JSON profile.
     *
     * @param  string  $absoluteImagePath  Full filesystem path to the photo.
     */
    public function analyze(string $absoluteImagePath): array
    {
        if (! is_file($absoluteImagePath)) {
            return $this->fallbackAnalysis($absoluteImagePath);
        }

        try {
            if ($key = config('services.anthropic.key')) {
                return $this->normalizeAnalysis($this->analyzeWithAnthropic($absoluteImagePath, $key));
            }

            if ($key = config('services.openai.key')) {
                return $this->normalizeAnalysis($this->analyzeWithOpenAi($absoluteImagePath, $key));
            }
        } catch (Throwable $e) {
            Log::warning('AvatarAiService analyze failed, using fallback.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->fallbackAnalysis($absoluteImagePath);
    }

    /**
     * Generate the avatar image. Returns the relative path on the "public"
     * disk plus whether the fallback poster was used.
     *
     * @return array{path:string, fallback:bool}
     */
    public function generateAvatar(string $absoluteImagePath, array $analysis): array
    {
        if ($key = config('services.openai.key')) {
            try {
                $path = $this->generateWithOpenAi($absoluteImagePath, $analysis, $key);

                if ($path) {
                    return ['path' => $path, 'fallback' => false];
                }
            } catch (Throwable $e) {
                Log::warning('OpenAI image generation failed, using SVG poster.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Always-available offline poster.
        $svg = SlushAvatarPoster::make($analysis);
        $path = 'avatars/'.Str::uuid()->toString().'.svg';
        Storage::disk('public')->put($path, $svg);

        return ['path' => $path, 'fallback' => true];
    }

    /** The prompt used for real image generation back-ends. */
    public function imagePrompt(array $analysis): string
    {
        $flavor = $analysis['slush_flavor'] ?? 'colorful slush';
        $color = $analysis['slush_color'] ?? 'pink';

        return "Create a cute 3D anime-style future avatar based on the uploaded face reference. "
            ."Make the character playful, friendly, colorful, and suitable for kids and family. "
            ."The character is holding a {$color} {$flavor} slush drink. "
            ."Use bright candy colors, soft lighting, minimal background, futuristic ice city theme, "
            ."happy mood, high quality, square 1:1 composition with the character centered.";
    }

    // --- Providers -------------------------------------------------------

    protected function analyzeWithAnthropic(string $path, string $apiKey): array
    {
        $base = rtrim(config('services.anthropic.base_url'), '/');
        $media = $this->mediaType($path);
        $data = base64_encode((string) file_get_contents($path));

        $response = Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post($base.'/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 600,
                'system' => $this->analysisSystemPrompt(),
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => [
                            'type' => 'base64', 'media_type' => $media, 'data' => $data,
                        ]],
                        ['type' => 'text', 'text' => 'สร้างฮีโร่สเลอปี้ในอนาคตของฉัน!'],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        $text = $response['content'][0]['text'] ?? '';

        return $this->extractJson($text);
    }

    protected function analyzeWithOpenAi(string $path, string $apiKey): array
    {
        $base = rtrim(config('services.openai.base_url'), '/');
        $media = $this->mediaType($path);
        $data = base64_encode((string) file_get_contents($path));

        $response = Http::timeout(config('services.openai.analyze_timeout'))
            ->connectTimeout(15)
            ->retry(2, 800, throw: false)
            ->withToken($apiKey)
            ->post($base.'/v1/chat/completions', [
                'model' => config('services.openai.text_model'),
                'max_tokens' => 600,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->analysisSystemPrompt()],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'สร้างฮีโร่สเลอปี้ในอนาคตของฉันเป็น JSON'],
                        ['type' => 'image_url', 'image_url' => [
                            'url' => "data:{$media};base64,{$data}",
                        ]],
                    ]],
                ],
            ])
            ->throw()
            ->json();

        return $this->extractJson($response['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Generate the avatar with OpenAI gpt-image-1. When a reference photo is
     * available we use the images/edits endpoint with high input fidelity so
     * the character resembles the player; otherwise plain text-to-image.
     */
    protected function generateWithOpenAi(string $path, array $analysis, string $apiKey): ?string
    {
        $base = rtrim(config('services.openai.base_url'), '/');
        $model = config('services.openai.image_model');
        $size = config('services.openai.image_size');
        $quality = config('services.openai.image_quality');
        $prompt = $this->imagePrompt($analysis);

        $http = Http::timeout(config('services.openai.image_timeout'))
            ->connectTimeout(15)
            ->retry(2, 1000, throw: false)
            ->withToken($apiKey);

        if (config('services.openai.use_reference') && is_file($path)) {
            $response = $http
                ->attach('image', (string) file_get_contents($path), basename($path))
                ->post($base.'/v1/images/edits', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'size' => $size,
                    'quality' => $quality,
                    'input_fidelity' => 'high',
                    'n' => 1,
                ]);
        } else {
            $response = $http->post($base.'/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'n' => 1,
            ]);
        }

        $payload = $response->throw()->json();
        $b64 = $payload['data'][0]['b64_json'] ?? null;

        if (! $b64) {
            return null;
        }

        $storedPath = 'avatars/'.Str::uuid()->toString().'.png';
        Storage::disk('public')->put($storedPath, base64_decode($b64));

        return $storedPath;
    }

    protected function analysisSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a playful avatar game engine for a kids & family slush drink shop.
Look at the photo ONLY to invent a fun, imaginary "future hero" persona.
NEVER describe or guess sensitive attributes: no age, gender, race, ethnicity,
health, body or appearance judgements. Keep everything cheerful and kid-safe.
Respond in Thai for the text fields. Return ONLY a strict JSON object with keys:
character_name (string, can be English-style hero name),
future_role (Thai), personality (Thai), slush_flavor (English flavor name),
slush_color (one of: pink, blue, green, yellow, purple, orange, red),
special_power (Thai), coupon (one of: "ลด 10%", "ลด 15%", "ฟรีท็อปปิ้ง"),
short_caption (Thai, upbeat one-liner). No markdown, no extra text.
PROMPT;
    }

    // --- Fallback & helpers ---------------------------------------------

    protected function fallbackAnalysis(string $seed): array
    {
        // Deterministic pick per session so re-renders stay consistent.
        $index = abs(crc32($seed)) % count(self::ARCHETYPES);

        return self::ARCHETYPES[$index];
    }

    protected function normalizeAnalysis(array $analysis): array
    {
        $defaults = self::ARCHETYPES[0];
        $analysis = array_merge($defaults, array_filter($analysis, fn ($v) => $v !== null && $v !== ''));

        $allowedColors = ['pink', 'blue', 'green', 'yellow', 'purple', 'orange', 'red'];
        $analysis['slush_color'] = strtolower((string) $analysis['slush_color']);
        if (! in_array($analysis['slush_color'], $allowedColors, true)) {
            $analysis['slush_color'] = 'pink';
        }

        return $analysis;
    }

    protected function extractJson(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException('No JSON object found in AI response.');
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON in AI response.');
        }

        return $decoded;
    }

    protected function mediaType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
