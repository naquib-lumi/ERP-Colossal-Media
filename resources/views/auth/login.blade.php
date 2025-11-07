<!DOCTYPE html>
<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-assets-path="/assets/" data-template="vertical-menu-template" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1" />
    <title>Colossal Xceed — Login</title>
    <meta name="description" content="Login to your Colossal Xceed account" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- your core css (kept) -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}" /> <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" /> <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />

    <style>
        :root {
            --bg: #f4fbff;
            --card: #ffffff80;
            /* glass */
            --card-border: #ffffffaa;
            --ink: #0f172a;
            --muted: #6b7280;
            --brand: #111;
            /* button + accent */
            --brand-pressed: #2a2a2a;
            --ring: #94a3b8;
            /* focus ring */
            --error: #ef4444;
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            color: var(--ink);
            background: var(--bg);
            overflow: hidden;
            /* hide blobs overflow */
        }

        /* ===== Canvas / blobs (soft, subtle) ===== */
        .bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(600px 300px at 10% 10%, #b3e5ff88 0%, transparent 60%),
                radial-gradient(700px 400px at 90% 80%, #ffe0f388 0%, transparent 60%),
                radial-gradient(500px 350px at 70% 10%, #e0ffe688 0%, transparent 55%),
                var(--bg);
            filter: saturate(110%);
        }

        .bg::after {
            /* gentle noise for depth */
            content: "";
            position: absolute;
            inset: -50%;
            mix-blend-mode: soft-light;
            opacity: .35;
            animation: drift 60s linear infinite;
        }
            @keyframes drift{to{transform:translate3d(-10%, -10%, 0)}}


        /* ===== Layout ===== */
        .wrap {
            position: relative;
            z-index: 1;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 32px;
        }

        .card {
            width: min(460px, 92vw);
            background: var(--card);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px) saturate(110%);
            border-radius: 16px;
            box-shadow: 0 20px 70px -25px rgba(15, 23, 42, .25);
            padding: 32px 28px;
            animation: pop .35s ease-out;
        }
        @keyframes pop{from{transform:translateY(8px); opacity:0} to{transform:none; opacity:1}}


        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .brand img {
            height: 44px
        }

        .title {
            font-weight: 700;
            letter-spacing: .02em;
            text-align: center;
            margin: 6px 0 2px
        }

        .subtitle {
            color: var(--muted);
            text-align: center;
            margin-bottom: 18px
        }

        /* ===== Form ===== */
        .field {
            margin-bottom: 16px
        }

        .label {
            display: block;
            font-size: .9rem;
            margin-bottom: 6px;
            color: #374151
        }

        .control {
            position: relative;
        }

        .input {
            width: 100%;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: var(--ink);
            padding: 10px 14px;
            outline: 0;
            transition: box-shadow .15s, border-color .15s, background .2s;
        }

        .input::placeholder {
            color: #9ca3af
        }

        .input:focus {
            border-color: var(--ring);
            box-shadow: 0 0 0 4px #93c5fd55;
        }

        .input[aria-invalid="true"] {
            border-color: var(--error)
        }

        .err {
            color: var(--error);
            font-size: .82rem;
            margin-top: 6px
        }

        /* password toggle */
        .toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            cursor: pointer;
            padding: 6px;
            line-height: 0;
            color: #6b7280;
        }

        .toggle:focus {
            outline: none;
            box-shadow: 0 0 0 3px #93c5fd55;
            border-radius: 8px
        }

        /* Remember + forgot */
        .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0 18px
        }

        .link {
            color: #6b7280;
            text-decoration: none
        }

        .link:hover {
            color: #111
        }

        /* Button */
        .btn {
            appearance: none;
            border: 0;
            width: 100%;
            height: 44px;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 600;
            letter-spacing: .02em;
            transition: transform .04s ease, background .2s, box-shadow .2s;
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, .35);
        }

        .btn:hover {
            background: var(--brand-pressed)
        }

        .btn:active {
            transform: translateY(1px)
        }

        .btn[disabled] {
            opacity: .7;
            cursor: not-allowed
        }

        /* small “card footer” area */
        .foot-note {
            margin-top: 14px;
            text-align: center;
            color: #6b7280;
            font-size: .9rem
        }

        /* error shake (when laravel returns errors) */
        .shake {
            animation: shake .3s ease-in-out
        }

        @keyframes shake{ 10%,90%{transform:translateX(-1px)} 20%,80%{transform:translateX(2px)} 30%,50%,70%{transform:translateX(-4px)} 40%,60%{transform:translateX(4px)}}

        @media (max-width:480px){
        .card{padding:24px 18px}
        .brand img{height:40px}
        }

    </style>
</head>

<body>
    <div class="bg" aria-hidden="true"></div>

    <div class="wrap">
        <div id="login-card" class="card">
            <div class="brand">
                <img src="{{ asset('assets/img/branding/login-logo.png') }}" alt="Colossal Xceed" onerror="this.style.display='none'">
            </div>
            <h4 class="title">LOGIN</h4>
            <p class="subtitle">Sign in to your account</p>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (session('status'))
            <div class="alert alert-success mb-4" role="alert">
                {{ session('status') }}
            </div>
            @endif

            <form id="formAuthentication" method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="email" class="label">Email</label>
                    <div class="control">
                        <input id="email" name="email" type="email" class="input"
                            placeholder="you@company.com" value="{{ old('email') }}"
                            required autocomplete="email" autofocus />
                    </div>
                </div>

                <div class="field">
                    <label for="password" class="label">Password</label>
                    <div class="control">
                        <input id="password" name="password" type="password" class="input"
                            placeholder="••••••••" required autocomplete="current-password" />
                        <button type="button" class="toggle" aria-label="Show password" id="togglePwd">
                            <!-- eye icon -->
                            <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="1.5" />
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                            <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" style="display:none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5" />
                                <path d="M4.5 6.5C6.5 4.5 9 3 12 3c6 0 10 7 10 7a17 17 0 0 1-4.3 4.9M14 14a3 3 0 0 1-4-4" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <div class="input-error">{{ $message }}</div>
                    @enderror
                </div>



                <button id="submitBtn" class="btn" type="submit">Sign In</button>
            </form>

            <div class="foot-note">© {{ date('Y') }} Colossal Xceed</div>
        </div>
    </div>
    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script>
        document.getElementById('formAuthentication').addEventListener('submit', function(event) {
            console.log('Form submission triggered with data:', new FormData(this));
        });
        // password show/hide
        (function() {
            const pwd = document.getElementById('password');
            const tgl = document.getElementById('togglePwd');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            tgl?.addEventListener('click', () => {
                const isHidden = pwd.type === 'password';
                pwd.type = isHidden ? 'text' : 'password';
                eyeOpen.style.display = isHidden ? 'none' : 'block';
                eyeClosed.style.display = isHidden ? 'block' : 'none';
                tgl.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                pwd.focus({
                    preventScroll: true
                });
            });
        })();

        // loading state + minor guard
        (function() {
            const form = document.getElementById('formAuthentication');
            const btn = document.getElementById('submitBtn');
            form?.addEventListener('submit', () => {
                btn.disabled = true;
                btn.textContent = 'Signing in…';
            });
        })();
    </script>
</body>

</html>