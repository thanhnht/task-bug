<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BugTrack') — BugTrack</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        /* ── Reset & Variables ─────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-0: #0d0f12;
            --bg-1: #13161b;
            --bg-2: #1c2028;
            --bg-3: #252b36;
            --border: #2e3440;
            --border-lit: #404858;

            --accent: #f97316;
            /* orange */
            --accent-dim: #7c3a12;
            --accent-glow: rgba(249, 115, 22, .18);

            --green: #22c55e;
            --red: #ef4444;
            --yellow: #eab308;
            --blue: #3b82f6;

            --text-1: #f1f5f9;
            --text-2: #94a3b8;
            --text-3: #475569;

            --sidebar-w: 240px;
            --header-h: 56px;
            --radius: 6px;
            --font-mono: 'Space Mono', monospace;
            --font-body: 'DM Sans', sans-serif;
        }

        html,
        body {
            height: 100%;
            background: var(--bg-0);
            color: var(--text-1);
            font-family: var(--font-body);
            font-size: 14px;
        }

        /* ── Scrollbar ─────────────────────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-lit);
            border-radius: 3px;
        }

        /* ── Layout Shell ──────────────────────────────────────────────────── */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Sidebar ───────────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-1);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: relative;
            z-index: 50;
        }

        .sidebar-logo {
            height: var(--header-h);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 18px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }

        .sidebar-logo-icon {
            width: 28px;
            height: 28px;
            background: var(--accent);
            border-radius: 4px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .sidebar-logo-icon svg {
            width: 16px;
            height: 16px;
            fill: #fff;
        }

        .sidebar-logo-text {
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 700;
            color: var(--text-1);
            letter-spacing: .04em;
        }

        .sidebar-logo-text span {
            color: var(--accent);
        }

        .sidebar-section {
            padding: 14px 10px 6px;
            font-size: 10px;
            font-family: var(--font-mono);
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--text-3);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 6px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 18px;
            color: var(--text-2);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 400;
            border-left: 2px solid transparent;
            transition: all .15s;
            cursor: pointer;
            position: relative;
        }

        .nav-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            opacity: .7;
        }

        .nav-item:hover {
            background: var(--bg-2);
            color: var(--text-1);
            border-left-color: var(--border-lit);
        }

        .nav-item:hover svg {
            opacity: 1;
        }

        .nav-item.active {
            background: var(--accent-glow);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 500;
        }

        .nav-item.active svg {
            opacity: 1;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--accent);
            color: #fff;
            font-size: 10px;
            font-family: var(--font-mono);
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .nav-badge.green {
            background: var(--green);
        }

        .nav-badge.yellow {
            background: var(--yellow);
            color: #000;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 8px 0;
        }

        .sidebar-user {
            padding: 12px 18px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--bg-3);
            border: 1px solid var(--border-lit);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            color: var(--accent);
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 10px;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .user-role.admin {
            color: var(--accent);
        }

        .user-role.employee {
            color: var(--blue);
        }

        /* ── Main area ─────────────────────────────────────────────────────── */
        .main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: var(--header-h);
            background: var(--bg-1);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            flex-shrink: 0;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-2);
        }

        .topbar-breadcrumb a {
            color: var(--text-2);
            text-decoration: none;
        }

        .topbar-breadcrumb a:hover {
            color: var(--text-1);
        }

        .topbar-breadcrumb .sep {
            color: var(--text-3);
        }

        .topbar-breadcrumb .current {
            color: var(--text-1);
            font-weight: 500;
        }

        .topbar-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-content {
            flex: 1;
            overflow-y: auto;
            padding: 28px 32px;
        }

        /* ── Page header ───────────────────────────────────────────────────── */
        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-family: var(--font-mono);
            font-size: 20px;
            font-weight: 700;
            color: var(--text-1);
            letter-spacing: -.01em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 .accent {
            color: var(--accent);
        }

        .page-header p {
            margin-top: 4px;
            font-size: 13.5px;
            color: var(--text-2);
        }

        /* ── Cards ─────────────────────────────────────────────────────────── */
        .card {
            background: var(--bg-1);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-2);
        }

        /* ── Buttons ───────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            border-radius: var(--radius);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            font-family: var(--font-body);
            white-space: nowrap;
        }

        .btn svg {
            width: 14px;
            height: 14px;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .btn-primary:hover {
            background: #ea6a0a;
            border-color: #ea6a0a;
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-2);
            border-color: var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg-2);
            color: var(--text-1);
            border-color: var(--border-lit);
        }

        .btn-danger {
            background: transparent;
            color: var(--red);
            border-color: var(--red);
        }

        .btn-danger:hover {
            background: var(--red);
            color: #fff;
        }

        .btn-success {
            background: transparent;
            color: var(--green);
            border-color: var(--green);
        }

        .btn-success:hover {
            background: var(--green);
            color: #fff;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        /* ── Form controls ─────────────────────────────────────────────────── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-2);
            margin-bottom: 7px;
        }

        .form-label .required {
            color: var(--red);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            background: var(--bg-0);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 9px 12px;
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

        .form-control::placeholder {
            color: var(--text-3);
        }

        .form-control.is-invalid {
            border-color: var(--red);
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .15);
        }

        .form-hint {
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-3);
        }

        .form-error {
            margin-top: 5px;
            font-size: 12px;
            color: var(--red);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Alerts ────────────────────────────────────────────────────────── */
        .alert {
            padding: 11px 14px;
            border-radius: var(--radius);
            font-size: 13.5px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 18px;
            border: 1px solid;
        }

        .alert svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert-danger {
            background: rgba(239, 68, 68, .08);
            border-color: rgba(239, 68, 68, .3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, .08);
            border-color: rgba(34, 197, 94, .3);
            color: #86efac;
        }

        .alert-warning {
            background: rgba(234, 179, 8, .08);
            border-color: rgba(234, 179, 8, .3);
            color: #fde047;
        }

        .alert-info {
            background: rgba(59, 130, 246, .08);
            border-color: rgba(59, 130, 246, .3);
            color: #93c5fd;
        }

        /* ── Badges ────────────────────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-family: var(--font-mono);
            font-weight: 700;
            letter-spacing: .04em;
        }

        .badge-admin {
            background: var(--accent-dim);
            color: var(--accent);
        }

        .badge-employee {
            background: rgba(59, 130, 246, .15);
            color: var(--blue);
        }

        .badge-active {
            background: rgba(34, 197, 94, .15);
            color: var(--green);
        }

        .badge-inactive {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
        }

        .badge-locked {
            background: rgba(234, 179, 8, .12);
            color: var(--yellow);
        }

        /* ── Table ─────────────────────────────────────────────────────────── */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 10px 14px;
            text-align: left;
            font-family: var(--font-mono);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-3);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .1s;
        }

        tbody tr:hover {
            background: var(--bg-2);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody td {
            padding: 11px 14px;
            font-size: 13.5px;
            color: var(--text-1);
            vertical-align: middle;
        }

        /* ── Stat cards ────────────────────────────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-1);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
        }

        .stat-card.orange::before {
            background: var(--accent);
        }

        .stat-card.green::before {
            background: var(--green);
        }

        .stat-card.red::before {
            background: var(--red);
        }

        .stat-card.blue::before {
            background: var(--blue);
        }

        .stat-label {
            font-size: 11px;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-3);
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: var(--font-mono);
            font-size: 28px;
            font-weight: 700;
            color: var(--text-1);
        }

        .stat-sub {
            font-size: 12px;
            color: var(--text-2);
            margin-top: 4px;
        }

        /* ── Toast (flash) ─────────────────────────────────────────────────── */
        .toast-area {
            position: fixed;
            top: 70px;
            right: 24px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: var(--bg-2);
            border: 1px solid var(--border-lit);
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 13.5px;
            max-width: 380px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .5);
            animation: slideIn .2s ease;
        }

        .toast svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .toast.success {
            border-left: 3px solid var(--green);
        }

        .toast.error {
            border-left: 3px solid var(--red);
        }

        .toast.warning {
            border-left: 3px solid var(--yellow);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ── Dropdown ──────────────────────────────────────────────────────── */
        .dropdown {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            background: var(--bg-2);
            border: 1px solid var(--border-lit);
            border-radius: var(--radius);
            min-width: 160px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .5);
            z-index: 200;
            overflow: hidden;
        }

        .dropdown:hover .dropdown-menu,
        .dropdown.open .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            font-size: 13px;
            color: var(--text-2);
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            transition: background .1s;
        }

        .dropdown-item svg {
            width: 14px;
            height: 14px;
        }

        .dropdown-item:hover {
            background: var(--bg-3);
            color: var(--text-1);
        }

        .dropdown-item.danger {
            color: var(--red);
        }

        .dropdown-item.danger:hover {
            background: rgba(239, 68, 68, .1);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--border);
        }

        /* ── Utilities ─────────────────────────────────────────────────────── */
        .flex {
            display: flex;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .ml-auto {
            margin-left: auto;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .text-sm {
            font-size: 12px;
        }

        .text-muted {
            color: var(--text-3);
        }

        /* ── Responsive ────────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .page-content {
                padding: 20px 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="app-shell">

        {{-- ── Sidebar ────────────────────────────────────────────────────────── --}}
        <aside class="sidebar">
            <a href="{{ route('employee.dashboard') }}" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <svg viewBox="0 0 16 16">
                        <path d="M2 3h12v2H2V3zm0 4h8v2H2V7zm0 4h10v2H2v-2z" />
                    </svg>
                </div>
                <span class="sidebar-logo-text">Bug<span>Track</span></span>
            </a>

            <nav class="sidebar-nav">

                @if (Auth::user()->isAdmin())
                    <div class="sidebar-section">Admin</div>

                    <a href="{{ route('admin.dashboard') }}"
                        class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg viewBox="0 0 16 16" fill="currentColor">
                            <path d="M1 1h6v6H1V1zm8 0h6v6H9V1zM1 9h6v6H1V9zm8 0h6v6H9V9z" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                        class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                        </svg>
                        Tài khoản
                    </a>

                    <div class="sidebar-divider"></div>
                    <div class="sidebar-section">Dự án</div>
                @endif

                <a href="{{ route('employee.dashboard') }}"
                    class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path d="M1 1h6v6H1V1zm8 0h6v6H9V1zM1 9h6v6H1V9zm8 0h6v6H9V9z" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('projects.index') }}"
                    class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path
                            d="M1.5 3A1.5 1.5 0 0 1 3 1.5h3l1 1h6A1.5 1.5 0 0 1 14.5 4v8A1.5 1.5 0 0 1 13 13.5H3A1.5 1.5 0 0 1 1.5 12V3z" />
                    </svg>
                    Projects
                </a>

                <div class="sidebar-divider"></div>

                <a href="{{ route('auth.change-password') }}"
                    class="nav-item {{ request()->routeIs('auth.change-password') ? 'active' : '' }}">
                    <svg viewBox="0 0 16 16" fill="currentColor">
                        <path
                            d="M8 1a4 4 0 0 1 4 4v1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1V5a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v1h4V5a2 2 0 0 0-2-2z" />
                    </svg>
                    Đổi mật khẩu
                </a>
            </nav>

            {{-- User card at bottom --}}
            <div class="sidebar-user">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->full_name, 0, 2)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->full_name }}</div>
                    <div class="user-role {{ Auth::user()->isAdmin() ? 'admin' : 'employee' }}">
                        {{ Auth::user()->isAdmin() ? 'Admin' : 'Employee' }}
                    </div>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" title="Đăng xuất"
                        style="background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px;">
                        <svg viewBox="0 0 16 16" fill="currentColor" style="width:16px;height:16px;">
                            <path
                                d="M6 2h4v2H6V2zm-3 1h2v10H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm10 0a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2V3h2zM9 7.5l-3 3V9H3V7h3V5.5l3 2z" />
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        {{-- ── Main area ────────────────────────────────────────────────────────── --}}
        <div class="main-area">

            {{-- Topbar --}}
            <header class="topbar">
                <div class="topbar-breadcrumb">
                    @yield('breadcrumb')
                </div>
                <div class="topbar-actions">
                    @yield('topbar-actions')
                </div>
            </header>

            {{-- Flash toasts --}}
            <div class="toast-area" id="toastArea">
                @if (session('success'))
                    <div class="toast success">
                        <svg viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM6.5 10.8 3.7 8l1-1 1.8 1.8 4-4 1 1-5 5z" />
                        </svg>
                        <span>{!! session('success') !!}</span>
                    </div>
                @endif
                @if (session('warning'))
                    <div class="toast warning">
                        <svg viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1L1 14h14L8 1zm0 3 5 9H3l5-9zm-1 3v3h2V7H7zm0 4v2h2v-2H7z" />
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="toast error">
                        <svg viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Auto-dismiss toasts after 4s
        setTimeout(() => {
            document.querySelectorAll('.toast').forEach(t => {
                t.style.transition = 'opacity .4s';
                t.style.opacity = '0';
                setTimeout(() => t.remove(), 400);
            });
        }, 4000);

        // Dropdown toggle
        document.querySelectorAll('[data-dropdown]').forEach(el => {
            el.addEventListener('click', e => {
                e.stopPropagation();
                el.closest('.dropdown').classList.toggle('open');
            });
        });
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        });
    </script>

    @stack('scripts')
</body>

</html>
