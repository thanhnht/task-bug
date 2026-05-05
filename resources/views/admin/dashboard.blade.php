@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('breadcrumb')
    <span class="current">Admin Dashboard</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:13px;height:13px"><path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"/></svg>
        Cấp tài khoản
    </a>
@endsection

@section('content')
<div class="page-header">
    <h1>Admin <span class="accent">Dashboard</span></h1>
    <p>Tổng quan hệ thống BugTrack</p>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Tổng nhân viên</div>
        <div class="stat-value">—</div>
        <div class="stat-sub">tài khoản</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Stories đang chạy</div>
        <div class="stat-value">—</div>
        <div class="stat-sub">in progress</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Bugs chưa đóng</div>
        <div class="stat-value">—</div>
        <div class="stat-sub">open bugs</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Done tuần này</div>
        <div class="stat-value">—</div>
        <div class="stat-sub">stories</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Tài khoản nhân viên</span>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Xem tất cả</a>
        </div>
        <div style="padding:48px;text-align:center;color:var(--text-3)">
            Dữ liệu sẽ hiển thị khi có nhân viên trong hệ thống.
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Thao tác nhanh</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px">
            <a href="{{ route('admin.users.create') }}" class="btn btn-ghost" style="justify-content:flex-start">
                <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2 1H6a4 4 0 0 0-4 4v1h12v-1a4 4 0 0 0-4-4zm4-4h-2V3h-2V1h2v-1h2v2h2v2h-2v2z"/></svg>
                Cấp tài khoản mới
            </a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost" style="justify-content:flex-start">
                <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/></svg>
                Quản lý tài khoản
            </a>
            <a href="{{ route('auth.change-password') }}" class="btn btn-ghost" style="justify-content:flex-start">
                <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px"><path d="M8 1a4 4 0 0 1 4 4v1h1v7H3V6h1V5a4 4 0 0 1 4-4zm0 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                Đổi mật khẩu của tôi
            </a>
        </div>
    </div>
</div>
@endsection
