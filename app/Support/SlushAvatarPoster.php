<?php

namespace App\Support;

/**
 * Generates a cute, candy themed vertical avatar poster as an SVG string.
 *
 * This is used as the offline / fallback avatar so the game always returns a
 * friendly, kid-safe result even when no image generation API is configured
 * or the AI call fails.
 */
class SlushAvatarPoster
{
    /** Named slush colors -> [main, dark, light] hex. */
    protected const PALETTE = [
        'pink' => ['#ff7fb6', '#e85a98', '#ffd6e8'],
        'blue' => ['#4fc3f7', '#2a9fd6', '#cdeffd'],
        'green' => ['#5fd6a0', '#34b87f', '#d2f6e6'],
        'yellow' => ['#ffd152', '#f4b400', '#fff3c9'],
        'purple' => ['#b98cff', '#9a63f0', '#ece0ff'],
        'orange' => ['#ff9f54', '#f57c20', '#ffe2c7'],
        'red' => ['#ff6b7d', '#e8485c', '#ffd5da'],
    ];

    public static function make(array $analysis): string
    {
        $colorName = strtolower((string) ($analysis['slush_color'] ?? 'pink'));
        [$main, $dark, $light] = self::PALETTE[$colorName] ?? self::PALETTE['pink'];

        $name = self::esc($analysis['character_name'] ?? 'Slush Hero');
        $flavor = self::esc($analysis['slush_flavor'] ?? 'Mystery Frost');
        $caption = self::esc($analysis['short_caption'] ?? 'วันนี้คุณคือฮีโร่สเลอปี้!');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 720" font-family="'Itim','Baloo 2',sans-serif">
  <defs>
    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="{$light}"/>
      <stop offset="1" stop-color="#ffffff"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.5" cy="0.32" r="0.7">
      <stop offset="0" stop-color="#ffffff" stop-opacity="0.9"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
  </defs>

  <rect width="720" height="720" fill="url(#sky)"/>

  <!-- candy ice city silhouette -->
  <g opacity="0.5" fill="{$light}">
    <rect x="60" y="360" width="80" height="200" rx="14"/>
    <rect x="160" y="320" width="70" height="240" rx="14"/>
    <rect x="500" y="340" width="74" height="220" rx="14"/>
    <rect x="590" y="300" width="80" height="260" rx="14"/>
  </g>
  <circle cx="600" cy="120" r="64" fill="#ffffff" opacity="0.7"/>
  <circle cx="130" cy="100" r="42" fill="#ffffff" opacity="0.7"/>
  <rect width="720" height="520" fill="url(#glow)"/>

  <!-- floating sparkles -->
  <g fill="{$main}" opacity="0.8">
    <circle cx="120" cy="220" r="7"/>
    <circle cx="610" cy="250" r="9"/>
    <circle cx="150" cy="400" r="7"/>
  </g>

  <!-- character -->
  <g transform="translate(360 300)">
    <!-- body -->
    <path d="M-100 240 Q-115 80 0 70 Q115 80 100 240 Z" fill="{$main}"/>
    <path d="M-100 240 Q-115 80 0 70 Q115 80 100 240 Z" fill="#ffffff" opacity="0.12"/>
    <!-- arm holding cup -->
    <path d="M72 145 Q155 145 155 205" stroke="{$main}" stroke-width="34" fill="none" stroke-linecap="round"/>

    <!-- head -->
    <circle cx="0" cy="0" r="115" fill="#ffe9d6"/>
    <!-- hair tuft -->
    <path d="M-115 -28 Q-86 -145 0 -125 Q86 -145 115 -28 Q58 -86 0 -82 Q-58 -86 -115 -28 Z" fill="{$dark}"/>
    <!-- cheeks -->
    <circle cx="-56" cy="32" r="19" fill="{$main}" opacity="0.55"/>
    <circle cx="56" cy="32" r="19" fill="{$main}" opacity="0.55"/>
    <!-- eyes -->
    <circle cx="-40" cy="-6" r="19" fill="#3a2b3a"/>
    <circle cx="40" cy="-6" r="19" fill="#3a2b3a"/>
    <circle cx="-33" cy="-13" r="7" fill="#ffffff"/>
    <circle cx="47" cy="-13" r="7" fill="#ffffff"/>
    <!-- smile -->
    <path d="M-32 42 Q0 74 32 42" stroke="#3a2b3a" stroke-width="8" fill="none" stroke-linecap="round"/>
  </g>

  <!-- slush cup -->
  <g transform="translate(515 470)">
    <path d="M-44 0 H44 L34 140 H-34 Z" fill="#ffffff" opacity="0.55"/>
    <path d="M-44 0 H44 L38 86 H-38 Z" fill="{$main}"/>
    <path d="M-48 -6 Q0 -50 48 -6 Q0 -26 -48 -6 Z" fill="{$dark}"/>
    <rect x="13" y="-74" width="9" height="86" rx="4" fill="{$dark}"/>
  </g>

  <!-- text card -->
  <g transform="translate(360 588)" text-anchor="middle">
    <text y="0" font-size="42" font-weight="700" fill="{$dark}">{$name}</text>
    <text y="44" font-size="25" fill="#6b6b6b">รสชาติของคุณ: {$flavor}</text>
    <text y="92" font-size="21" fill="#8a8a8a">{$caption}</text>
  </g>
</svg>
SVG;
    }

    protected static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
