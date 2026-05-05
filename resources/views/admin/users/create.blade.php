@extends('layouts.app')

@section('title', 'Cấp tài khoản mới')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.users.index') }}">Tài khoản</a>
    <span class="sep">/</span>
    <span class="current">Cấp mới</span>
@endsection

@section('content')
<div class="page-header">
    <h1>
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" style="color:var(--accent)">
            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2 1H6a4 4 0 0 0-4 4v1h12v-1a4 4 0 0 0-4-4zm4-4h-2V3h-2V1h2V-1h2v2h2v2h-2v2z"/>
        </svg>
        Cấp tài khoản <span class="accent">mới</span>
    </h1>
    <p>Tạo tài khoản cho nhân viên mới. Hệ thống sẽ tự tạo mật khẩu tạm thời.</p>
</div>

<div style="max-width:520px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Thông tin tài khoản</span>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="full_name">
                    Họ và tên <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    class="form-control {{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                    value="{{ old('full_name') }}"
                    placeholder="Nguyễn Văn A"
                    autofocus
                >
                @error('full_name')
                    <div class="form-error">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">
                    Email <span class="required">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                    value="{{ old('email') }}"
                    placeholder="nvana@company.com"
                    autocomplete="off"
                >
                @error('email')
                    <div class="form-error">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Note about temp password --}}
            <div class="alert alert-info" style="margin:20px 0 24px">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v2H7V5zm0 4h2v4H7V9z"/></svg>
                <div style="font-size:13px">
                    Hệ thống sẽ <strong>tự tạo mật khẩu tạm thời.</strong><br>
                    Nhân viên sẽ bắt buộc đổi mật khẩu ngay khi đăng nhập lần đầu.
                </div>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>
                    Tạo tài khoản
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Huỷ</a>
            </div>
        </form>
    </div>

    {{-- Show temp password after creation --}}
    @if(session('temp_password'))
    <div class="card" style="margin-top:16px;border-color:var(--accent)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
            <div style="width:32px;height:32px;background:var(--accent-dim);border-radius:50%;display:grid;place-items:center">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="color:var(--accent)"><path d="M8 1a4 4 0 0 1 4 4v1h1v7H3V6h1V5a4 4 0 0 1 4-4zm0 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
            </div>
            <div>
                <div style="font-family:var(--font-mono);font-size:13px;font-weight:700;color:var(--accent)">Mật khẩu tạm thời</div>
                <div style="font-size:12px;color:var(--text-3)">Thông báo cho nhân viên ngay</div>
            </div>
        </div>
        <div style="background:var(--bg-0);border:1px solid var(--border);border-radius:6px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between">
            <code style="font-family:var(--font-mono);font-size:18px;letter-spacing:.1em;color:var(--text-1)">
                {{ session('temp_password') }}
            </code>
        </div>
        <div style="margin-top:8px;font-size:12px;color:var(--text-3)">
            ⚠ Mật khẩu này đã được gửi tới email nhân viên.
        </div>
    </div>
    @endif
</div>
@endsection
