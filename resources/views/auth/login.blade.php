<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — BugTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-0: #0d0f12; --bg-1: #13161b; --bg-2: #1c2028;
            --border: #2e3440; --border-lit: #404858;
            --accent: #3b82f6; --accent-dim: #1e3a8a; --accent-glow: rgba(59,130,246,.18);
            --red: #ef4444; --text-1: #f1f5f9; --text-2: #94a3b8; --text-3: #475569;
            --font-mono: 'Space Mono', monospace; --font-body: 'DM Sans', sans-serif;
        }

        html, body {
            height: 100%;
            background: var(--bg-0);
            color: var(--text-1);
            font-family: var(--font-body);
            font-size: 14px;
        }

        /* ── Grid background pattern ─────────────────────────────────────── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* ── Glow orb ─────────────────────────────────────────────────────── */
        body::after {
            content: '';
            position: fixed;
            top: -200px; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,.08) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ── Layout ───────────────────────────────────────────────────────── */
        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 440px 1fr;
            grid-template-rows: auto 1fr auto;
            align-items: center;
        }

        .auth-center { grid-column: 2; padding: 40px 0; }

        /* ── Logo ─────────────────────────────────────────────────────────── */
        .auth-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
        }
        .logo-icon {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 6px;
            display: grid; place-items: center;
        }
        .logo-icon svg { width: 20px; height: 20px; fill: #fff; }
        .logo-text {
            font-family: var(--font-mono);
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .logo-text span { color: var(--accent); }

        /* ── Card ─────────────────────────────────────────────────────────── */
        .auth-card {
            background: var(--bg-1);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 36px 36px 32px;
            position: relative;
        }
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 32px; right: 32px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: .6;
        }

        .auth-title {
            font-family: var(--font-mono);
            font-size: 18px;
            font-weight: 700;
            color: var(--text-1);
            margin-bottom: 4px;
        }
        .auth-subtitle {
            font-size: 13.5px;
            color: var(--text-3);
            margin-bottom: 28px;
        }

        /* ── Form elements ────────────────────────────────────────────────── */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-2);
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 12px; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--text-3);
            pointer-events: none;
        }
        .input-icon svg { width: 16px; height: 16px; }

        .form-control {
            width: 100%;
            background: var(--bg-0);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px 10px 38px;
            color: var(--text-1);
            font-size: 14px;
            font-family: var(--font-body);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .form-control::placeholder { color: var(--text-3); }
        .form-control.is-invalid { border-color: var(--red); }
        .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239,68,68,.15); }

        /* Toggle password visibility */
        .toggle-pass {
            position: absolute;
            right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-3); padding: 0;
        }
        .toggle-pass:hover { color: var(--text-2); }
        .toggle-pass svg { width: 16px; height: 16px; display: block; }

        .field-error {
            margin-top: 5px;
            font-size: 12px;
            color: var(--red);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .field-error svg { width: 12px; height: 12px; flex-shrink: 0; }

        /* ── Alert box ────────────────────────────────────────────────────── */
        .alert-block {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 6px;
            padding: 11px 14px;
            margin-bottom: 22px;
            font-size: 13px;
            color: #fca5a5;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .alert-block svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }
        .alert-block.warning {
            background: rgba(234,179,8,.08);
            border-color: rgba(234,179,8,.3);
            color: #fde047;
        }

        /* ── Checkbox ─────────────────────────────────────────────────────── */
        .check-row { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; }
        .check-row input[type=checkbox] {
            width: 15px; height: 15px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .check-row label { font-size: 13px; color: var(--text-2); cursor: pointer; }

        /* ── Submit ───────────────────────────────────────────────────────── */
        .btn-login {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            font-family: var(--font-body);
            cursor: pointer;
            transition: background .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover { background: #ea6a0a; }
        .btn-login:active { transform: scale(.98); }
        .btn-login:disabled { opacity: .5; cursor: not-allowed; }
        .btn-login svg { width: 16px; height: 16px; }

        /* ── Locked state ─────────────────────────────────────────────────── */
        .lock-overlay {
            text-align: center;
            padding: 16px 0 8px;
        }
        .lock-icon {
            width: 48px; height: 48px;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 50%;
            display: grid; place-items: center;
            margin: 0 auto 14px;
        }
        .lock-icon svg { width: 22px; height: 22px; fill: var(--red); }
        .lock-title { font-family: var(--font-mono); font-size: 14px; color: var(--red); font-weight: 700; }
        .lock-desc { font-size: 13px; color: var(--text-2); margin-top: 6px; line-height: 1.6; }

        /* ── Footer hint ──────────────────────────────────────────────────── */
        .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 12px;
            color: var(--text-3);
            line-height: 1.6;
        }

        /* ── Attempts counter ─────────────────────────────────────────────── */
        .attempts-bar {
            height: 3px;
            background: var(--border);
            border-radius: 2px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .attempts-fill {
            height: 100%;
            border-radius: 2px;
            transition: width .3s;
        }
        .attempts-fill.low  { background: var(--green); }
        .attempts-fill.mid  { background: var(--yellow); }
        .attempts-fill.high { background: var(--red); }

        @media (max-width: 600px) {
            .auth-shell { grid-template-columns: 16px 1fr 16px; }
            .auth-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>

<div class="auth-shell">
    <div class="auth-center">

        <div class="auth-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 20 20"><path d="M3 4h14v2H3V4zm0 5h9v2H3V9zm0 5h11v2H3v-2z"/></svg>
            </div>
            <div class="logo-text">Bug<span>Track</span></div>
        </div>

        <div class="auth-card">
            <h1 class="auth-title">Đăng nhập</h1>
            <p class="auth-subtitle">Nhập thông tin tài khoản được cấp bởi Admin</p>

            {{-- ── Flash warning (first login redirect v.v.) ── --}}
            @if(session('warning'))
            <div class="alert-block warning">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 14h14L8 1zm-1 8V6h2v3H7zm0 2h2v2H7v-2z"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
            @endif

            {{-- ── Global error (bị khoá / vô hiệu hoá) ── --}}
            @if($errors->has('username') && Str::contains($errors->first('username'), ['khoá', 'vô hiệu']))
            <div class="alert-block">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a4 4 0 0 1 4 4v1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1V5a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v1h4V5a2 2 0 0 0-2-2zm0 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                <div>
                    <strong>Tài khoản bị hạn chế</strong><br>
                    {{ $errors->first('username') }}
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('auth.login') }}" id="loginForm">
                @csrf

                {{-- Username --}}
                <div class="form-group">
                    <label class="form-label" for="username">
                        Tên đăng nhập
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/></svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                            value="{{ old('username') }}"
                            placeholder="username"
                            autocomplete="username"
                            autofocus
                        >
                    </div>
                    @error('username')
                        <div class="field-error">
                            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a4 4 0 0 1 4 4v1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1V5a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v1h4V5a2 2 0 0 0-2-2zm0 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pass" onclick="togglePass()" title="Hiện/ẩn mật khẩu">
                            <svg id="eyeIcon" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 3C4 3 1 8 1 8s3 5 7 5 7-5 7-5-3-5-7-5zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0-5a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">
                            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="check-row">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M3 2h8l3 3v9H3V2zm5 8l3-3-1-1-2 2V6H7v3L5 7 4 8l4 2z"/>
                    </svg>
                    Đăng nhập
                </button>
            </form>

            <div class="auth-footer">
                Quên mật khẩu? Vui lòng liên hệ <strong style="color:var(--text-2)">Admin</strong> để được cấp lại.<br>
                <span style="color:var(--border-lit)">──────────────────</span>
            </div>
        </div>

    </div>
</div>

<script>
    function togglePass() {
        const inp = document.getElementById('password');
        const ico = document.getElementById('eyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.innerHTML = '<path d="M2 2l12 12m-2.5-3A7 7 0 0 1 1 8s1.4-2.5 4-4M6.5 4.5A4 4 0 0 1 12 8c0 .6-.1 1.1-.3 1.6"/>';
        } else {
            inp.type = 'password';
            ico.innerHTML = '<path d="M8 3C4 3 1 8 1 8s3 5 7 5 7-5 7-5-3-5-7-5zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0-5a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>';
        }
    }
</script>
</body>
</html>
