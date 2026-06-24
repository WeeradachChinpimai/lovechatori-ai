<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | AI providers used by App\Services\AvatarAiService.
    |
    | Analysis (photo -> fun JSON profile): Claude vision if ANTHROPIC_API_KEY
    | is set, else OpenAI vision if OPENAI_API_KEY is set, else offline fallback.
    |
    | Avatar image: OpenAI gpt-image-1 if OPENAI_API_KEY is set (uses the photo
    | as a reference so the avatar resembles the player), else a generated SVG
    | poster. Any error at any step degrades gracefully to the fallback.
    */
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    ],

    // Master switch for the avatar AI back-end:
    //   AI_USE_GEMINI=true  -> Google Gemini (analysis + image)
    //   AI_USE_GEMINI=false -> OpenAI/Anthropic (analysis) + OpenAI (image)
    // Either way, any error degrades gracefully to the offline fallback.
    'ai' => [
        'use_gemini' => (bool) env('AI_USE_GEMINI', false),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),

        // Vision model for the fun analysis step (image -> JSON).
        'text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.5-flash'),

        // Image model for avatar generation (Gemini 2.5 Flash Image / "Nano Banana").
        'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-2.5-flash-image'),
        // Pass the uploaded photo as a reference image for likeness.
        'use_reference' => (bool) env('GEMINI_IMAGE_USE_REFERENCE', true),

        // Network timeouts (seconds).
        'analyze_timeout' => (int) env('GEMINI_ANALYZE_TIMEOUT', 20),
        'image_timeout' => (int) env('GEMINI_IMAGE_TIMEOUT', 60),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),

        // Vision model for the fun analysis step.
        'text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4o-mini'),

        // Image model + options for avatar generation.
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
        'image_size' => env('OPENAI_IMAGE_SIZE', '1024x1024'), // square 1:1
        'image_quality' => env('OPENAI_IMAGE_QUALITY', 'low'), // low|medium|high (speed vs detail)
        // Use the uploaded photo as a reference (images/edits) for likeness.
        'use_reference' => (bool) env('OPENAI_IMAGE_USE_REFERENCE', true),

        // Network timeouts (seconds).
        'analyze_timeout' => (int) env('OPENAI_ANALYZE_TIMEOUT', 20),
        'image_timeout' => (int) env('OPENAI_IMAGE_TIMEOUT', 60),
    ],

];
