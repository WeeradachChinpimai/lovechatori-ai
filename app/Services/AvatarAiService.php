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
            'future_role' => 'คุณหมอแห่งอนาคต',
            'outfit_en' => 'a friendly doctor in a clean white medical coat with a stethoscope and a digital health scanner',
            'personality' => 'ร่าเริง ชอบผจญภัย และมีพลังบวก',
            'slush_flavor' => 'Slushy Thai Tea',
            'slush_color' => 'orange',
            'special_power' => 'เปลี่ยนรอยยิ้มให้กลายเป็นพลังน้ำแข็งสีชมพู',
            'coupon' => 'ส่วนลด 5 บาท',
            'short_caption' => 'วันนี้คุณคือฮีโร่สเลอปี้สายสดใส!',
        ],
        [
            'character_name' => 'Sky Blue Ranger',
            'future_role' => 'นักบินอวกาศ',
            'outfit_en' => 'an astronaut in a white-and-blue space suit with mission patches, holding the helmet under one arm',
            'personality' => 'ใจเย็น มั่นใจ และชอบช่วยเหลือเพื่อน',
            'slush_flavor' => 'Rainbow Party',
            'slush_color' => 'green',
            'special_power' => 'เรียกสายลมเย็นสดชื่นได้ทุกเมื่อ',
            'coupon' => 'ส่วนลด 5 บาท',
            'short_caption' => 'พลังความเย็นสุดคูลอยู่ในมือคุณแล้ว!',
        ],
        [
            'character_name' => 'Lime Spark Hero',
            'future_role' => 'เชฟมืออาชีพ',
            'outfit_en' => 'a professional chef in a crisp white chef jacket and toque with an apron, holding a whisk',
            'personality' => 'สดใส กระฉับกระเฉง และมองโลกในแง่ดี',
            'slush_flavor' => 'Choco Boom',
            'slush_color' => 'brown',
            'special_power' => 'ปลุกพลังสดชื่นให้ทุกคนรอบตัวยิ้มได้',
            'coupon' => 'ส่วนลด 5 บาท',
            'short_caption' => 'สดชื่นสุดพลังในเวอร์ชันอนาคตของคุณ!',
        ],
        [
            'character_name' => 'Sunny Mango Star',
            'future_role' => 'นักวิทยาศาสตร์',
            'outfit_en' => 'a scientist in a white lab coat with safety goggles and a tablet, surrounded by holographic gadgets',
            'personality' => 'อบอุ่น เป็นมิตร และมีพลังบวกล้นเหลือ',
            'slush_flavor' => 'Slushy Green Tea',
            'slush_color' => 'green',
            'special_power' => 'ส่องแสงอุ่น ๆ ให้ทุกวันสดใส',
            'coupon' => 'ส่วนลด 5 บาท',
            'short_caption' => 'แสงอาทิตย์สดใสในแบบฉบับของคุณ!',
        ],
        [
            'character_name' => 'Grape Galaxy Guardian',
            'future_role' => 'นักบิน',
            'outfit_en' => 'an airline pilot in a smart navy uniform with a captain hat, epaulettes and a wings badge',
            'personality' => 'ช่างฝัน สร้างสรรค์ และมีจินตนาการล้นฟ้า',
            'slush_flavor' => 'Sunset Crunch',
            'slush_color' => 'orange',
            'special_power' => 'เปิดประตูสู่จักรวาลแห่งความสนุก',
            'coupon' => 'ส่วนลด 5 บาท',
            'short_caption' => 'จินตนาการของคุณคือพลังที่ยิ่งใหญ่!',
        ],
    ];

    /**
     * Analyse the uploaded face photo and return the fun JSON profile.
     *
     * @param  string|null  $imageBytes  Raw image contents (disk-agnostic), or null.
     * @param  string  $mime  Image mime type, e.g. image/jpeg.
     * @param  string  $seed  Stable seed (session uuid) for deterministic fallback.
     */
    public function analyze(?string $imageBytes, string $mime, string $seed): array
    {
        if ($imageBytes === null || $imageBytes === '') {
            return $this->fallbackAnalysis($seed);
        }

        try {
            if (config('services.ai.use_gemini')) {
                if ($key = config('services.gemini.key')) {
                    return $this->normalizeAnalysis($this->analyzeWithGemini($imageBytes, $mime, $key));
                }
            } else {
                if ($key = config('services.anthropic.key')) {
                    return $this->normalizeAnalysis($this->analyzeWithAnthropic($imageBytes, $mime, $key));
                }

                if ($key = config('services.openai.key')) {
                    return $this->normalizeAnalysis($this->analyzeWithOpenAi($imageBytes, $mime, $key));
                }
            }
        } catch (Throwable $e) {
            Log::warning('AvatarAiService analyze failed, using fallback.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $this->fallbackAnalysis($seed);
    }

    /**
     * Generate the avatar image and store it on the configured media disk.
     * Returns the disk-relative path plus whether the fallback poster was used.
     *
     * @return array{path:string, fallback:bool}
     */
    public function generateAvatar(?string $imageBytes, string $mime, array $analysis): array
    {
        $useGemini = config('services.ai.use_gemini');

        try {
            if ($useGemini && ($key = config('services.gemini.key'))) {
                $path = $this->generateWithGemini($imageBytes, $mime, $analysis, $key);

                if ($path) {
                    return ['path' => $path, 'fallback' => false];
                }
            } elseif (! $useGemini && ($key = config('services.openai.key'))) {
                $path = $this->generateWithOpenAi($imageBytes, $mime, $analysis, $key);

                if ($path) {
                    return ['path' => $path, 'fallback' => false];
                }
            }
        } catch (Throwable $e) {
            Log::warning('AI image generation failed, using SVG poster.', [
                'provider' => $useGemini ? 'gemini' : 'openai',
                'message' => $e->getMessage(),
            ]);
        }

        // Always-available offline poster.
        $svg = SlushAvatarPoster::make($analysis);
        $path = 'avatars/'.Str::uuid()->toString().'.svg';
        $this->store($path, $svg);

        return ['path' => $path, 'fallback' => true];
    }

    /** Store generated media on the configured disk, public-readable. */
    protected function store(string $path, string $contents): void
    {
        Storage::disk(config('slush.media_disk'))->put($path, $contents, 'public');
    }

    /** The prompt used for real image generation back-ends. */
    public function imagePrompt(array $analysis): string
    {
        $flavor = $analysis['slush_flavor'] ?? 'Slushy Thai Tea';
        $color = $analysis['slush_color'] ?? 'orange';
        $outfit = $analysis['outfit_en'] ?? 'a detailed, colorful future-career uniform with themed accessories and props';

        // One of the 6 CHILLO menu drinks, described so the cup looks correct.
        $drink = match ($flavor) {
            'Slushy Thai Tea'  => 'an orange Thai-tea slushie in a clear CHILLO cup',
            'Slushy Green Tea' => 'a green matcha green-tea slushie in a clear CHILLO cup',
            'Slushy Choco'     => 'a brown chocolate slushie in a clear CHILLO cup',
            'Sunset Crunch'    => 'an orange Thai-tea slushie topped with whipped cream, crunchy crumble and a Biscoff biscuit, in a clear CHILLO cup',
            'Rainbow Party'    => 'a green-tea slushie topped with whipped cream and colorful rainbow sprinkles, in a clear CHILLO cup',
            'Choco Boom'       => 'a brown chocolate slushie topped with whipped cream, crumble and a Biscoff biscuit, in a clear CHILLO cup',
            default            => "a {$color} {$flavor} slushie in a clear CHILLO cup",
        };

        return "Transform the real person in the provided photo into a cute, bright 3D animated-style avatar. "
            ."STRONGLY preserve their real identity and likeness: keep the SAME face shape, facial features, eyes, "
            ."nose, mouth, eyebrows, skin tone and exact hairstyle as in the photo, so it is clearly and "
            ."recognisably the same person — only restyled, NOT a different character. "
            ."Keep natural, realistic facial proportions; do NOT exaggerate, enlarge or anime-fy the eyes or head. "
            ."Render in a polished, glossy Disney/Pixar-style 3D look with smooth shading and bright cheerful "
            ."colors — cute and friendly, but always true to the person's real face. NOT photoreal, NOT a flat 2D cartoon. "
            ."The character has a clear future profession: dress them in {$outfit}. "
            ."Show profession-specific clothing, accessories and props so the career is obvious at a glance. "
            ."The character is holding {$drink}. "
            ."Place them in a bright, colorful, slightly futuristic CHILLO-themed background scene that clearly "
            ."matches their future profession (for example a science lab for a scientist, a kitchen for a chef, "
            ."a space station for an astronaut, a cockpit for a pilot), with soft glowing ice-city and slush vibes. "
            ."Tasteful blue and orange brand accents. "
            ."Match the SAME facial expression, emotion and mood as in the photo — if the person is frowning, "
            ."pouting, sticking out their tongue, winking, surprised or smiling, reflect that exact expression. "
            ."Upper-body framing so the outfit is clearly visible, high quality, square 1:1 composition with the character centered.";
    }

    // --- Providers -------------------------------------------------------

    protected function analyzeWithAnthropic(string $bytes, string $mime, string $apiKey): array
    {
        $base = rtrim(config('services.anthropic.base_url'), '/');
        $media = $this->mediaType($mime);
        $data = base64_encode($bytes);

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

    protected function analyzeWithOpenAi(string $bytes, string $mime, string $apiKey): array
    {
        $base = rtrim(config('services.openai.base_url'), '/');
        $media = $this->mediaType($mime);
        $data = base64_encode($bytes);

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

    /** Absolute path to the CHILLO cup image for the chosen flavor, or null. */
    protected function drinkImagePath(array $analysis): ?string
    {
        $file = match ($analysis['slush_flavor'] ?? null) {
            'Slushy Thai Tea'  => 'thai-tea.png',
            'Slushy Green Tea' => 'green-tea.png',
            'Slushy Choco'     => 'choco.png',
            'Sunset Crunch'    => 'sunset-crunch.png',
            'Rainbow Party'    => 'rainbow-party.png',
            'Choco Boom'       => 'choco-boom.png',
            default            => null,
        };

        if (! $file) {
            return null;
        }

        $path = public_path('drink/'.$file);

        return is_file($path) ? $path : null;
    }

    /** Extra instruction appended when a real drink-cup reference image is attached. */
    protected function drinkReferenceInstruction(): string
    {
        return 'Use the exact slush drink cup shown in the additional reference image as the cup the '
            .'character is holding — match its color, contents, toppings and CHILLO logo precisely; '
            .'do not invent a different cup.';
    }

    /**
     * Generate the avatar with OpenAI gpt-image-1. When a reference photo is
     * available we use the images/edits endpoint with high input fidelity so
     * the character resembles the player; otherwise plain text-to-image.
     */
    protected function generateWithOpenAi(?string $bytes, string $mime, array $analysis, string $apiKey): ?string
    {
        $base = rtrim(config('services.openai.base_url'), '/');
        $model = config('services.openai.image_model');
        $size = config('services.openai.image_size');
        $quality = config('services.openai.image_quality');
        $prompt = $this->imagePrompt($analysis);
        $drink = $this->drinkImagePath($analysis);
        if ($drink) {
            $prompt .= ' '.$this->drinkReferenceInstruction();
        }

        $http = Http::timeout(config('services.openai.image_timeout'))
            ->connectTimeout(15)
            ->retry(2, 1000, throw: false)
            ->withToken($apiKey);

        if (config('services.openai.use_reference') && $bytes !== null && $bytes !== '') {
            $ext = str_contains($mime, 'png') ? 'png' : (str_contains($mime, 'webp') ? 'webp' : 'jpg');
            $payload = [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'input_fidelity' => 'high',
                'n' => 1,
            ];

            if ($drink) {
                // Two reference images: the player's face + the exact drink cup.
                $response = $http
                    ->attach('image[]', $bytes, 'reference.'.$ext)
                    ->attach('image[]', file_get_contents($drink), 'drink.png')
                    ->post($base.'/v1/images/edits', $payload);
            } else {
                $response = $http
                    ->attach('image', $bytes, 'reference.'.$ext)
                    ->post($base.'/v1/images/edits', $payload);
            }
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
        $this->store($storedPath, base64_decode($b64));

        return $storedPath;
    }

    /** Analyse the face photo with Google Gemini (vision -> strict JSON). */
    protected function analyzeWithGemini(string $bytes, string $mime, string $apiKey): array
    {
        $base = rtrim(config('services.gemini.base_url'), '/');
        $model = config('services.gemini.text_model');
        $media = $this->mediaType($mime);
        $data = base64_encode($bytes);

        $response = Http::timeout(config('services.gemini.analyze_timeout'))
            ->connectTimeout(15)
            ->retry(2, 800, throw: false)
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->post($base.'/v1beta/models/'.$model.':generateContent', [
                'system_instruction' => ['parts' => [['text' => $this->analysisSystemPrompt()]]],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['inline_data' => ['mime_type' => $media, 'data' => $data]],
                        ['text' => 'สร้างฮีโร่สเลอปี้ในอนาคตของฉันเป็น JSON'],
                    ],
                ]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ])
            ->throw()
            ->json();

        return $this->extractJson($response['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    /**
     * Generate the avatar with Gemini 2.5 Flash Image ("Nano Banana"). When a
     * reference photo is available it is sent alongside the prompt so the
     * character resembles the player; otherwise plain text-to-image.
     */
    protected function generateWithGemini(?string $bytes, string $mime, array $analysis, string $apiKey): ?string
    {
        $base = rtrim(config('services.gemini.base_url'), '/');
        $model = config('services.gemini.image_model');
        $prompt = $this->imagePrompt($analysis);
        $drink = $this->drinkImagePath($analysis);
        if ($drink) {
            $prompt .= ' '.$this->drinkReferenceInstruction();
        }

        $parts = [['text' => $prompt]];

        if (config('services.gemini.use_reference') && $bytes !== null && $bytes !== '') {
            $parts[] = ['inline_data' => ['mime_type' => $this->mediaType($mime), 'data' => base64_encode($bytes)]];
        }

        // Attach the exact CHILLO drink cup so the avatar holds the right flavor.
        if ($drink) {
            $parts[] = ['inline_data' => ['mime_type' => 'image/png', 'data' => base64_encode(file_get_contents($drink))]];
        }

        $payload = Http::timeout(config('services.gemini.image_timeout'))
            ->connectTimeout(15)
            ->retry(2, 1000, throw: false)
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->post($base.'/v1beta/models/'.$model.':generateContent', [
                'contents' => [['role' => 'user', 'parts' => $parts]],
            ])
            ->throw()
            ->json();

        // Gemini returns the image inline among the response parts.
        $b64 = null;
        foreach ($payload['candidates'][0]['content']['parts'] ?? [] as $part) {
            $b64 = $part['inlineData']['data'] ?? $part['inline_data']['data'] ?? null;
            if ($b64) {
                break;
            }
        }

        if (! $b64) {
            return null;
        }

        $storedPath = 'avatars/'.Str::uuid()->toString().'.png';
        $this->store($storedPath, base64_decode($b64));

        return $storedPath;
    }

    protected function analysisSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a playful avatar game engine for a kids & family slush drink shop.
Look at the photo ONLY to invent a fun, inspiring "future career self" persona.
NEVER describe or guess sensitive attributes: no age, gender, race, ethnicity,
health, body or appearance judgements. Keep everything cheerful and kid-safe.
Respond in Thai for the text fields. Return ONLY a strict JSON object with keys:
character_name (string, can be English-style hero name),
future_role (Thai — a REAL, recognizable and inspiring career, e.g. doctor,
astronaut, chef, scientist, engineer, pilot, teacher, veterinarian, architect,
firefighter, nurse, athlete, photographer. Pick ONE. Do NOT use fantasy,
superhero, guardian or magical roles),
outfit_en (a short ENGLISH description of the REAL professional uniform/attire
for that exact career, with authentic, recognizable clothing, tools and props
so the job is obvious at a glance, given only a light modern futuristic touch —
e.g. "a doctor in a clean white medical coat with a stethoscope and a digital
health scanner" or "an astronaut in a white-and-blue space suit with mission
patches, holding the helmet"),
personality (Thai),
slush_flavor (pick EXACTLY one of these 6 CHILLO menu flavors, English, verbatim:
"Slushy Thai Tea", "Slushy Green Tea", "Slushy Choco", "Sunset Crunch",
"Rainbow Party", "Choco Boom"),
slush_color (must match the chosen flavor: orange for "Slushy Thai Tea" and
"Sunset Crunch"; green for "Slushy Green Tea" and "Rainbow Party"; brown for
"Slushy Choco" and "Choco Boom"),
special_power (Thai), coupon (always "ส่วนลด 5 บาท"),
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

        $allowedColors = ['orange', 'green', 'brown'];
        $analysis['slush_color'] = strtolower((string) $analysis['slush_color']);
        if (! in_array($analysis['slush_color'], $allowedColors, true)) {
            $analysis['slush_color'] = 'orange';
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

    /** Normalise to a media type the AI providers accept. */
    protected function mediaType(string $mime): string
    {
        $mime = strtolower($mime);

        return match (true) {
            str_contains($mime, 'png') => 'image/png',
            str_contains($mime, 'webp') => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
