@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('breadcrumb')
    <a href="{{ route('employee.dashboard') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Đổi mật khẩu</span>
@endsection

@section('content')
<div class="page-header">
    <h1>
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" style="color:var(--accent)">
            <path d="M8 1a4 4 0 0 1 4 4v1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1V5a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v1h4V5a2 2 0 0 0-2-2zm0 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
        </svg>
        Đổi mật <span class="accent">khẩu</span>
    </h1>
    <p>{{ $is_first_login ? 'Bạn đang đăng nhập lần đầu — hãy đặt mật khẩu mới để tiếp tục.' : 'Cập nhật mật khẩu của bạn.' }}</p>
</div>

{{-- First-login warning --}}
@if($is_first_login)
<div class="alert alert-warning" style="margin-bottom:24px">
    <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 14h14L8 1zm-1 8V6h2v3H7zm0 2h2v2H7v-2z"/></svg>
    <div>
        <strong>Bắt buộc đổi mật khẩu</strong><br>
        <span style="font-size:13px">Đây là lần đầu đăng nhập. Bạn phải đặt mật khẩu mới trước khi sử dụng hệ thống.</span>
    </div>
</div>
@endif

<div style="max-width:480px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Thông tin mật khẩu</span>
        </div>

        <form method="POST" action="{{ route('auth.change-password') }}" id="changePassForm">
            @csrf

            {{-- Current password --}}
            <div class="form-group">
                <label class="form-label" for="current_password">
                    Mật khẩu hiện tại <span class="required">*</span>
                </label>
                <div style="position:relative">
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                        placeholder="Nhập mật khẩu hiện tại"
                        autocomplete="current-password"
                    >
                    <button type="button" onclick="toggleField('current_password')"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3C4 3 1 8 1 8s3 5 7 5 7-5 7-5-3-5-7-5zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0-5a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                    </button>
                </div>
                @error('current_password')
                    <div class="form-error">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:var(--border);margin:20px 0"></div>

            {{-- New password --}}
            <div class="form-group">
                <label class="form-label" for="new_password">
                    Mật khẩu mới <span class="required">*</span>
                </label>
                <div style="position:relative">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control {{ $errors->has('new_password') ? 'is-invalid' : '' }}"
                        placeholder="Tối thiểu 8 ký tự"
                        autocomplete="new-password"
                        oninput="checkStrength(this.value)"
                    >
                    <button type="button" onclick="toggleField('new_password')"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-3);padding:4px">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3C4 3 1 8 1 8s3 5 7 5 7-5 7-5-3-5-7-5zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0-5a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                    </button>
                </div>

                {{-- Strength meter --}}
                <div style="margin-top:8px">
                    <div style="height:3px;background:var(--border);border-radius:2px;overflow:hidden">
                        <div id="strengthBar" style="height:100%;width:0;border-radius:2px;transition:all .3s"></div>
                    </div>
                    <div id="strengthLabel" style="font-size:11px;font-family:var(--font-mono);margin-top:5px;color:var(--text-3)"></div>
                </div>

                {{-- Requirements checklist --}}
                <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:4px">
                    <div class="req-item" id="req-len">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink:0"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 9.4L4.6 8l1-1 1.4 1.4 4-4 1 1-5 4.4z"/></svg>
                        Ít nhất 8 ký tự
                    </div>
                    <div class="req-item" id="req-upper">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink:0"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 9.4L4.6 8l1-1 1.4 1.4 4-4 1 1-5 4.4z"/></svg>
                        Chữ hoa (A-Z)
                    </div>
                    <div class="req-item" id="req-num">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink:0"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 9.4L4.6 8l1-1 1.4 1.4 4-4 1 1-5 4.4z"/></svg>
                        Chữ số (0-9)
                    </div>
                    <div class="req-item" id="req-special">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink:0"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 9.4L4.6 8l1-1 1.4 1.4 4-4 1 1-5 4.4z"/></svg>
                        Ký tự đặc biệt
                    </div>
                </div>

                @error('new_password')
                    <div class="form-error" style="margin-top:8px">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Confirm --}}
            <div class="form-group">
                <label class="form-label" for="new_password_confirmation">
                    Xác nhận mật khẩu mới <span class="required">*</span>
                </label>
                <div style="position:relative">
                    <input
                        type="password"
                        id="new_password_confirmation"
                        name="new_password_confirmation"
                        class="form-control"
                        placeholder="Nhập lại mật khẩu mới"
                        autocomplete="new-password"
                        oninput="checkMatch()"
                    >
                </div>
                <div id="matchHint" class="form-hint"></div>
            </div>

            <div style="display:flex;gap:10px;margin-top:24px">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a4 4 0 0 1 4 4v1h1a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1V5a4 4 0 0 1 4-4zm0 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                    Lưu mật khẩu mới
                </button>
                @if(!$is_first_login)
                    <a href="{{ route('employee.dashboard') }}" class="btn btn-ghost">Huỷ</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .req-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-family: var(--font-mono);
        color: var(--text-3);
        transition: color .2s;
    }
    .req-item.ok { color: var(--green); }
    .req-item.ok svg { fill: var(--green); }
</style>
@endpush

@push('scripts')
<script>
    function toggleField(id) {
        const f = document.getElementById(id);
        f.type = f.type === 'password' ? 'text' : 'password';
    }

    function checkStrength(val) {
        const bar    = document.getElementById('strengthBar');
        const label  = document.getElementById('strengthLabel');
        const checks = {
            len:     val.length >= 8,
            upper:   /[A-Z]/.test(val),
            num:     /[0-9]/.test(val),
            special: /[@$!%*#?&]/.test(val),
        };
        Object.entries(checks).forEach(([k, ok]) => {
            document.getElementById('req-' + k).classList.toggle('ok', ok);
        });
        const score = Object.values(checks).filter(Boolean).length;
        const cfg = [
            { w:'0%',   color:'',                text:'' },
            { w:'25%',  color:'var(--red)',       text:'Rất yếu' },
            { w:'50%',  color:'var(--yellow)',    text:'Yếu' },
            { w:'75%',  color:'#f59e0b',          text:'Trung bình' },
            { w:'100%', color:'var(--green)',     text:'Mạnh' },
        ][score];
        bar.style.width       = cfg.w;
        bar.style.background  = cfg.color;
        label.textContent     = cfg.text;
        label.style.color     = cfg.color;
        checkMatch();
    }

    function checkMatch() {
        const np = document.getElementById('new_password').value;
        const nc = document.getElementById('new_password_confirmation').value;
        const hint = document.getElementById('matchHint');
        if (!nc) { hint.textContent = ''; return; }
        if (np === nc) {
            hint.textContent = '✓ Mật khẩu khớp';
            hint.style.color = 'var(--green)';
        } else {
            hint.textContent = '✗ Mật khẩu không khớp';
            hint.style.color = 'var(--red)';
        }
    }
</script>
@endpush
