<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ff7fb6">
    <title>{{ $title ?? 'AI Future Slush Avatar' }}</title>

    {{-- Tailwind via Play CDN keeps the MVP build-free (works on old Node).
         Swap to the Vite/Tailwind plugin before production. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Itim', 'Mali', 'sans-serif'] },
                    colors: {
                        candy: {
                            pink:   '#ff7fb6',
                            blue:   '#4fc3f7',
                            green:  '#5fd6a0',
                            yellow: '#ffd152',
                            cream:  '#fff7fb',
                        },
                    },
                    keyframes: {
                        floaty: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
                        spinslow: { to: { transform: 'rotate(360deg)' } },
                        pop: { '0%': { transform: 'scale(0.85)', opacity: 0 }, '100%': { transform: 'scale(1)', opacity: 1 } },
                    },
                    animation: {
                        floaty: 'floaty 3s ease-in-out infinite',
                        spinslow: 'spinslow 2.5s linear infinite',
                        pop: 'pop .35s ease-out both',
                    },
                },
            },
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Mali:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body { -webkit-tap-highlight-color: transparent; }
        .candy-bg {
            background:
                radial-gradient(circle at 15% 12%, #ffe3f1 0, transparent 38%),
                radial-gradient(circle at 85% 18%, #d9f3ff 0, transparent 40%),
                radial-gradient(circle at 50% 100%, #def8ec 0, transparent 45%),
                #fff7fb;
        }
        /* loading skeleton shimmer */
        .skeleton-shimmer {
            background: linear-gradient(100deg, #e7edf3 28%, #f4f8fc 50%, #e7edf3 72%);
            background-size: 200% 100%;
            animation: shimmer 1.3s ease-in-out infinite;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="h-full candy-bg text-slate-700 antialiased font-sans">
    <main class="mx-auto flex min-h-full w-full max-w-md flex-col px-5 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
