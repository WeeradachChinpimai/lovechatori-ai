<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#064AA8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AI Future Slush Avatar' }}</title>

    {{-- Tailwind via Play CDN keeps the MVP build-free (works on old Node).
         Swap to the Vite/Tailwind plugin before production. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Noto Sans Thai"', 'Prompt', 'sans-serif'] },
                    colors: {
                        chillo: {
                            blue:         '#064AA8',
                            'blue-dark':  '#06327A',
                            'blue-light': '#EAF3FF',
                            orange:        '#FF6A00',
                            'orange-dark': '#E95300',
                            'orange-light':'#FFF0E5',
                            cream: '#FFFDF8',
                            ice:   '#DDF4FF',
                            sky:   '#52BDEB',
                        },
                        ink: {
                            DEFAULT: '#132547',
                            soft:    '#64748B',
                            faint:   '#94A3B8',
                        },
                    },
                    boxShadow: {
                        soft:   '0 10px 30px rgba(6, 50, 122, 0.10)',
                        button: '0 10px 20px rgba(255, 106, 0, 0.25)',
                    },
                    borderColor: { soft: '#E8EDF5' },
                    keyframes: {
                        floaty:   { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
                        spinslow: { to: { transform: 'rotate(360deg)' } },
                        pop:      { '0%': { transform: 'scale(0.85)', opacity: 0 }, '100%': { transform: 'scale(1)', opacity: 1 } },
                        sparkle:  { '0%,100%': { transform: 'scale(1)', opacity: 0.9 }, '50%': { transform: 'scale(1.25)', opacity: 0.4 } },
                        ctabounce:{ '0%,100%': { transform: 'translateY(0) scale(1)' }, '50%': { transform: 'translateY(-6px) scale(1.015)' } },
                    },
                    animation: {
                        floaty:   'floaty 3s ease-in-out infinite',
                        spinslow: 'spinslow 2.5s linear infinite',
                        pop:      'pop .35s ease-out both',
                        sparkle:  'sparkle 2.4s ease-in-out infinite',
                        ctabounce:'ctabounce 2.2s ease-in-out infinite',
                    },
                },
            },
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700;800&family=Prompt:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Primary Brand */
            --chillo-blue: #064AA8;
            --chillo-blue-dark: #06327A;
            --chillo-blue-light: #EAF3FF;
            --chillo-orange: #FF6A00;
            --chillo-orange-dark: #E95300;
            --chillo-orange-light: #FFF0E5;
            /* Supporting */
            --chillo-cream: #FFFDF8;
            --chillo-white: #FFFFFF;
            --chillo-ice: #DDF4FF;
            --chillo-sky: #52BDEB;
            /* Text */
            --text-primary: #132547;
            --text-secondary: #64748B;
            --text-soft: #94A3B8;
            /* UI */
            --border-soft: #E8EDF5;
            --shadow-soft: 0 10px 30px rgba(6, 50, 122, 0.10);
            --shadow-button: 0 10px 20px rgba(255, 106, 0, 0.25);
            /* Status */
            --success: #20B26B;
            --warning: #FFB020;
            --danger: #E5484D;
        }

        body { -webkit-tap-highlight-color: transparent; }

        /* Cream page background with soft blue/orange brand splashes.
           Applied to <body> so it covers the full viewport edge-to-edge —
           the centered <main> is transparent, so the background stays one
           seamless surface with no white side rails at any width. */
        .chillo-bg {
            background:
                radial-gradient(circle at 12% 8%, rgba(82, 189, 235, 0.16) 0, transparent 40%),
                radial-gradient(circle at 88% 14%, rgba(255, 106, 0, 0.10) 0, transparent 42%),
                radial-gradient(circle at 50% 102%, rgba(6, 74, 168, 0.08) 0, transparent 45%),
                var(--chillo-cream);
        }

        /* loading skeleton shimmer */
        .skeleton-shimmer {
            background: linear-gradient(100deg, #e7edf3 28%, #f4f8fc 50%, #e7edf3 72%);
            background-size: 200% 100%;
            animation: shimmer 1.3s ease-in-out infinite;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* image-placeholder shimmer sweep */
        .placeholder-shimmer::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(100deg, transparent 30%, rgba(255,255,255,0.45) 50%, transparent 70%);
            background-size: 200% 100%;
            animation: shimmer 2.2s ease-in-out infinite;
            pointer-events: none;
        }

        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="h-full bg-chillo-cream text-ink antialiased font-sans">
    {{-- Branded background image: fixed full-viewport layer behind content so
         the decorative frame stays put while the page scrolls. Cream fallback
         (on <body>) shows through if the image is slow/unavailable. --}}
    <div aria-hidden="true"
         class="pointer-events-none fixed inset-0 -z-10 bg-cover bg-center bg-no-repeat"
         style="background-image: url('{{ asset('background.webp') }}');"></div>

    <main class="mx-auto flex min-h-full w-full max-w-md flex-col px-5 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
