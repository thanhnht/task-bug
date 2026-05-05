{{-- ============================================================
     resources/views/employee/dashboard.blade.php
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="current">Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <h1>
        Xin chào, <span class="accent">{{ Auth::user()->full_name }}</span> 👋
    </h1>
    <p>Hôm nay là {{ now()->translatedFormat('l, d/m/Y') }}</p>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Stories của tôi</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">đang được giao</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Bugs mở</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">cần xử lý</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Hoàn thành</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">trong tuần này</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Hoạt động gần đây</span>
    </div>
    <div style="padding:48px;text-align:center;color:var(--text-3)">
        <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" style="display:block;margin:0 auto 12px;opacity:.3">
            <path d="M2 2h12v1H2V2zm0 3h12v1H2V5zm0 3h8v1H2V8zm0 3h10v1H2v-1z"/>
        </svg>
        Chưa có dữ liệu. Module Story &amp; Bug đang phát triển.
    </div>
</div>
@endsection
