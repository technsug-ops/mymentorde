<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MentorDE Study Buddy — Sana Özel Almanya Üniversite Rehberi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(135deg, #e9e7e2 0%, #f4f2ee 100%);
            color: #1a1a1a;
            min-height: 100vh;
            line-height: 1.6;
        }
        .sb-container { max-width: 720px; margin: 0 auto; padding: 32px 20px; }

        /* Header */
        .sb-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .sb-logo {
            font-size: 22px; font-weight: 700; color: #7e58bf;
            text-decoration: none; letter-spacing: -0.5px;
        }
        .sb-logo-mark { display: inline-block; width: 32px; height: 32px; background: #7e58bf; border-radius: 8px; vertical-align: middle; margin-right: 8px; position: relative; }
        .sb-logo-mark::after { content: '🎓'; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 16px; }
        .sb-back { color: #7e58bf; text-decoration: none; font-size: 14px; font-weight: 500; }
        .sb-back:hover { text-decoration: underline; }

        /* Progress bar */
        .sb-progress-wrap { margin-bottom: 28px; }
        .sb-progress-meta { display: flex; justify-content: space-between; font-size: 12px; color: #6b5894; margin-bottom: 6px; font-weight: 600; }
        .sb-progress-bar { height: 6px; background: #d8d2e8; border-radius: 999px; overflow: hidden; }
        .sb-progress-fill { height: 100%; background: linear-gradient(90deg, #7e58bf, #a07ed9); border-radius: 999px; transition: width .4s ease; }

        /* Card */
        .sb-card {
            background: #fff;
            border-radius: 18px;
            padding: 36px 28px;
            box-shadow: 0 8px 32px rgba(126, 88, 191, 0.08);
            border: 1px solid rgba(126, 88, 191, 0.1);
        }
        .sb-title {
            font-size: 26px; font-weight: 700; color: #1a1a1a;
            margin-bottom: 8px; letter-spacing: -0.5px; line-height: 1.3;
        }
        .sb-subtitle {
            font-size: 14.5px; color: #6b5894; margin-bottom: 28px; line-height: 1.55;
        }

        /* Option cards (radio-as-cards) */
        .sb-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .sb-options.cols-2 { grid-template-columns: repeat(2, 1fr); }
        .sb-options.cols-1 { grid-template-columns: 1fr; }
        .sb-option {
            position: relative;
            background: #fff;
            border: 2px solid #e9e7e2;
            border-radius: 12px;
            padding: 18px 16px;
            cursor: pointer;
            transition: all .15s;
            text-align: left;
            display: flex; align-items: flex-start; gap: 12px;
            font-family: inherit;
            width: 100%;
        }
        .sb-option:hover { border-color: #b79ae9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(126, 88, 191, 0.08); }
        .sb-option input[type="radio"] { position: absolute; opacity: 0; }
        .sb-option.selected { border-color: #7e58bf; background: linear-gradient(135deg, rgba(126, 88, 191, 0.08), rgba(167, 126, 217, 0.04)); }
        .sb-option.selected::before {
            content: '✓';
            position: absolute;
            top: 12px; right: 12px;
            background: #7e58bf; color: #fff;
            width: 22px; height: 22px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        .sb-option-icon { font-size: 26px; flex-shrink: 0; }
        .sb-option-text { flex: 1; }
        .sb-option-label { font-size: 14.5px; font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }
        .sb-option-desc { font-size: 12px; color: #6b5894; line-height: 1.4; }

        /* Footer / nav */
        .sb-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 28px; gap: 12px; }
        .sb-btn {
            padding: 12px 24px; border: none; border-radius: 10px;
            font-family: inherit; font-size: 14.5px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            transition: all .15s;
        }
        .sb-btn-primary {
            background: linear-gradient(135deg, #7e58bf, #6a47a8);
            color: #fff;
            box-shadow: 0 4px 14px rgba(126, 88, 191, 0.25);
        }
        .sb-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(126, 88, 191, 0.35); }
        .sb-btn-primary:disabled { background: #cdc7d8; cursor: not-allowed; box-shadow: none; transform: none; }
        .sb-btn-ghost { background: transparent; color: #6b5894; }
        .sb-btn-ghost:hover { background: rgba(126, 88, 191, 0.08); }

        /* Hero (landing) */
        .sb-hero { text-align: center; padding: 60px 20px 40px; }
        .sb-hero-badge {
            display: inline-block; padding: 6px 14px;
            background: rgba(126, 88, 191, 0.12); color: #7e58bf;
            border-radius: 999px; font-size: 12.5px; font-weight: 600;
            margin-bottom: 16px; letter-spacing: .3px;
        }
        .sb-hero-title { font-size: 38px; font-weight: 700; color: #1a1a1a; margin-bottom: 12px; line-height: 1.15; letter-spacing: -1px; }
        .sb-hero-subtitle { font-size: 17px; color: #6b5894; margin-bottom: 28px; max-width: 540px; margin-left: auto; margin-right: auto; line-height: 1.55; }
        .sb-hero-cta { display: inline-flex; padding: 16px 36px; font-size: 16px; font-weight: 700; }
        .sb-hero-meta { font-size: 13px; color: #8a7baf; margin-top: 16px; }

        /* Stats */
        .sb-stats { display: flex; justify-content: center; gap: 36px; margin-top: 48px; flex-wrap: wrap; }
        .sb-stat { text-align: center; }
        .sb-stat-num { font-size: 32px; font-weight: 700; color: #7e58bf; letter-spacing: -1px; }
        .sb-stat-label { font-size: 12.5px; color: #6b5894; margin-top: 4px; }

        @media (max-width: 600px) {
            .sb-hero-title { font-size: 28px; }
            .sb-card { padding: 24px 18px; }
            .sb-title { font-size: 22px; }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="sb-container">
        <header class="sb-header">
            <a href="/" class="sb-logo"><span class="sb-logo-mark"></span>MentorDE</a>
            @hasSection('back-link')
                @yield('back-link')
            @endif
        </header>

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
