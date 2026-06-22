<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#064AA8">
    <title>{{ $title ?? 'อุ๊ปส์' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --chillo-blue: #064AA8;
            --chillo-orange: #FF6A00;
            --chillo-cream: #FFFDF8;
        }
        * { box-sizing: border-box; margin: 0; }
        body {
            font-family: 'Noto Sans Thai', sans-serif; min-height: 100vh; display: flex;
            align-items: center; justify-content: center; padding: 24px; color: #475569;
            background:
                radial-gradient(circle at 12% 8%, rgba(82, 189, 235, 0.18) 0, transparent 40%),
                radial-gradient(circle at 88% 14%, rgba(255, 106, 0, 0.12) 0, transparent 42%),
                radial-gradient(circle at 50% 102%, rgba(6, 74, 168, 0.09) 0, transparent 45%),
                var(--chillo-cream);
        }
        .card {
            background: #fff; border-radius: 32px; padding: 40px 28px; max-width: 380px; width: 100%;
            text-align: center; box-shadow: 0 20px 50px -20px rgba(6, 50, 122, 0.35);
            border: 1px solid #E8EDF5;
            animation: pop .35s ease-out both;
        }
        @keyframes pop { from { transform: scale(.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .emoji { font-size: 72px; animation: floaty 3s ease-in-out infinite; }
        h1 { font-size: 26px; font-weight: 800; color: var(--chillo-blue); margin-top: 14px; }
        p { font-size: 16px; color: #64748b; margin-top: 8px; }
        .btn {
            display: inline-block; margin-top: 24px; background: var(--chillo-orange); color: #fff;
            font-size: 18px; font-weight: 800; text-decoration: none;
            padding: 16px 32px; border-radius: 999px; box-shadow: 0 10px 20px rgba(255, 106, 0, 0.30);
        }
        .hint { font-size: 13px; color: #94a3b8; margin-top: 14px; }
        .code { font-size: 13px; color: #cbd5e1; margin-top: 18px; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="emoji">{{ $emoji ?? '🥤' }}</div>
        <h1>{{ $title ?? 'อุ๊ปส์ มีบางอย่างผิดพลาด' }}</h1>
        <p>{{ $message ?? 'ระบบงอแงนิดหน่อย ลองใหม่อีกครั้งนะ' }}</p>
        <a class="btn" href="{{ url('/play') }}">🔁 กลับไปเล่นใหม่</a>
        <p class="hint">กำลังพากลับไปหน้าแรกอัตโนมัติ…</p>
        <p class="code">{{ $code ?? '' }}</p>
    </div>
    <script>setTimeout(function () { window.location.href = @json(url('/play')); }, 6000);</script>
</body>
</html>
