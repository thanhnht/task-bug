@extends('layouts.app')

@section('title', 'Dự án')

@section('breadcrumb')
    <span class="current">Dự án</span>
@endsection

@section('topbar-actions')
    @if(Auth::user()->isAdmin())
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
            Tạo dự án mới
        </a>
    @endif
@endsection

@section('content')
<div class="page-header">
    <h1>Dự <span class="accent">án</span></h1>
    <p>{{ Auth::user()->isAdmin() ? 'Tất cả dự án trong hệ thống.' : 'Các dự án bạn đang tham gia.' }}</p>
</div>

@if($projects->isEmpty())
<div class="empty-state">
    <div class="empty-icon">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h12v2H3v-2zm0 4h14v2H3v-2z"/></svg>
    </div>
    <div class="empty-title">Chưa có dự án nào</div>
    <div class="empty-desc">
        @if(Auth::user()->isAdmin())
            Tạo dự án đầu tiên để bắt đầu quản lý công việc.
        @else
            Bạn chưa được thêm vào dự án nào. Liên hệ Admin để được phân công.
        @endif
    </div>
    @if(Auth::user()->isAdmin())
        <a href="{{ route('projects.create') }}" class="btn btn-primary" style="margin-top:16px">Tạo dự án</a>
    @endif
</div>
@else

{{-- Project grid --}}
<div class="project-grid">
    @foreach($projects as $project)
    @php $role = $project->roleOf(Auth::user()); @endphp

    <a href="{{ route('projects.show', $project) }}" class="project-card">
        <div class="project-card-head">
            <div>
                <span class="project-code">{{ $project->code }}</span>
                <h3 class="project-name">{{ $project->name }}</h3>
            </div>
        </div>

        @if($project->description)
        <p class="project-desc">{{ Str::limit($project->description, 80) }}</p>
        @endif

        <div class="project-card-footer">
            <div class="project-meta">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2h12v1H2V2zm0 3h12v1H2V5zm0 3h8v1H2V8zm0 3h10v1H2v-1z"/></svg>
                {{ $project->root_tasks_count }} tasks
            </div>

            @if($role && $role !== 'admin')
            <span class="role-tag role-{{ $role }}">
                {{ \App\Models\Project::ROLE_LABELS[$role] ?? $role }}
            </span>
            @elseif($role === 'admin')
            <span class="role-tag role-admin">Admin</span>
            @endif
        </div>
    </a>
    @endforeach
</div>

{{-- Pagination --}}
@if($projects->hasPages())
<div style="margin-top:24px;display:flex;justify-content:center">
    {{ $projects->links() }}
</div>
@endif

@endif
@endsection

@push('styles')
<style>
    /* Project Grid */
    .project-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    .project-card {
        background: var(--bg-1);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 20px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 14px;
        position: relative;
        overflow: hidden;
        transition: border-color .15s, transform .12s, box-shadow .15s;
    }
    .project-card:hover {
        border-color: var(--border-lit);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,.08);
    }
.project-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-top: 4px;
    }
    .project-code {
        font-family: var(--font-mono);
        font-size: 11px;
        color: var(--text-3);
        letter-spacing: .06em;
        display: block;
        margin-bottom: 4px;
    }
    .project-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-1);
        line-height: 1.3;
    }
    .project-desc {
        font-size: 13px;
        color: var(--text-2);
        line-height: 1.5;
    }

.project-card-footer {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    .project-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: var(--text-3);
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 80px 24px;
        color: var(--text-3);
    }
    .empty-icon {
        width: 64px; height: 64px;
        background: var(--bg-2);
        border: 1px solid var(--border);
        border-radius: 50%;
        display: grid;
        place-items: center;
        margin: 0 auto 20px;
    }
    .empty-icon svg { width: 28px; height: 28px; opacity: .4; }
    .empty-title { font-family: var(--font-mono); font-size: 16px; color: var(--text-2); margin-bottom: 8px; }
    .empty-desc  { font-size: 13.5px; line-height: 1.6; }
</style>
@endpush
