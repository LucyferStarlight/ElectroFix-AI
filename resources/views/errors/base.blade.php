<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error del sistema' }} | ElectroFix-AI</title>
    <style>
        :root {
            --bg: #0b1220;
            --bg-soft: #111827;
            --panel: rgba(17, 24, 39, 0.84);
            --line: rgba(56, 189, 248, 0.26);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --accent: #22d3ee;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Montserrat", "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 16% 18%, rgba(34, 211, 238, 0.16), transparent 33%),
                radial-gradient(circle at 84% 78%, rgba(6, 182, 212, 0.12), transparent 30%),
                linear-gradient(170deg, var(--bg), var(--bg-soft));
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        .scene {
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            border: 1px solid rgba(34, 211, 238, 0.18);
            border-radius: 1rem;
            backdrop-filter: blur(2px);
            transition: transform .25s ease-out;
        }

        .shape.one { width: 22rem; height: 22rem; top: -4rem; left: -3rem; }
        .shape.two { width: 16rem; height: 16rem; bottom: -2rem; right: 4rem; }
        .shape.three { width: 10rem; height: 10rem; top: 22%; right: 18%; }

        .card {
            width: min(92vw, 780px);
            border: 1px solid var(--line);
            border-radius: 1.4rem;
            background: var(--panel);
            box-shadow: 0 26px 64px rgba(2, 6, 23, 0.46);
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-weight: 700;
            letter-spacing: .03em;
            color: var(--text);
            text-decoration: none;
        }

        .mark {
            width: 2rem;
            height: 2rem;
            border-radius: .65rem;
            display: grid;
            place-items: center;
            font-size: .86rem;
            color: #032028;
            background: linear-gradient(140deg, #67e8f9, #22d3ee);
            box-shadow: inset 0 -6px 12px rgba(8, 47, 73, 0.22);
        }

        .status {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .38rem .8rem;
            font-size: .78rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .1em;
        }

        .code {
            margin: .25rem 0 .4rem;
            font-size: clamp(3.8rem, 13vw, 7.6rem);
            letter-spacing: .04em;
            line-height: .95;
            color: #f8fafc;
            text-shadow: 0 0 32px rgba(34, 211, 238, .28);
        }

        h1 {
            margin: 0;
            font-size: clamp(1.35rem, 3vw, 2rem);
        }

        p {
            margin: .85rem 0 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .actions {
            margin-top: 1.6rem;
            display: flex;
            flex-wrap: wrap;
            gap: .7rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .75rem;
            border: 1px solid var(--line);
            padding: .72rem 1.1rem;
            text-decoration: none;
            color: #cffafe;
            font-weight: 600;
            transition: .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: rgba(34, 211, 238, 0.6);
            background: rgba(8, 47, 73, 0.28);
        }

        .btn.primary {
            background: linear-gradient(140deg, #22d3ee, #0891b2);
            color: #06232a;
            border-color: transparent;
        }

        .btn.primary:hover {
            background: linear-gradient(140deg, #67e8f9, #22d3ee);
        }

        .meta {
            margin-top: 1rem;
            font-size: .82rem;
            color: #64748b;
        }

        @media (max-width: 600px) {
            .card { padding: 1.2rem; }
            .top { flex-direction: column; align-items: flex-start; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="scene" aria-hidden="true">
        <div class="shape one" data-depth="16"></div>
        <div class="shape two" data-depth="10"></div>
        <div class="shape three" data-depth="24"></div>
    </div>

    <main class="card" role="main" aria-live="polite">
        <div class="top">
            <a class="brand" href="{{ url('/') }}">
                <span class="mark">EF</span>
                <span>ElectroFix-AI</span>
            </a>
            <span class="status">Error {{ $code ?? 500 }}</span>
        </div>

        <div class="code">{{ $code ?? 500 }}</div>
        <h1>{{ $title ?? 'Algo salio mal' }}</h1>
        <p>{{ $message ?? 'Ocurrio un problema inesperado. Intenta de nuevo en unos minutos.' }}</p>
        @if(!empty($hint))
            <p>{{ $hint }}</p>
        @endif

        <div class="actions">
            <a class="btn primary" href="{{ url('/') }}">Ir al inicio</a>
            <a class="btn" href="javascript:history.back()">Regresar</a>
            <a class="btn" href="{{ url('/support') }}">Contactar soporte</a>
        </div>

        <div class="meta">ElectroFix-AI · {{ now()->format('Y') }}</div>
    </main>

    <script>
        (() => {
            const layers = Array.from(document.querySelectorAll('[data-depth]'));
            if (!layers.length) return;

            window.addEventListener('mousemove', (event) => {
                const x = event.clientX / window.innerWidth - 0.5;
                const y = event.clientY / window.innerHeight - 0.5;

                layers.forEach((layer) => {
                    const depth = Number(layer.dataset.depth || 10);
                    const tx = x * depth;
                    const ty = y * depth;
                    layer.style.transform = `translate(${tx}px, ${ty}px)`;
                });
            }, { passive: true });
        })();
    </script>
</body>
</html>
