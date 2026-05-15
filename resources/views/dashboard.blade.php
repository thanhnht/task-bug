@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="current">Dashboard</span>
@endsection

@section('content')

{{-- ── Chào ────────────────────────────────────────────────────────────────── --}}
<div class="page-header" style="margin-bottom:24px">
    <h1>Xin chào, <span class="accent">{{ $user->full_name }}</span></h1>
    <p style="color:var(--text-3);font-size:13px">{{ now()->format('l, d/m/Y') }}</p>
</div>

{{-- ── My tasks ─────────────────────────────────────────────────────────────── --}}
<div class="dash-section-title">Công việc của tôi</div>
<div class="dash-grid-4" style="margin-bottom:28px">
    <div class="dash-stat-card">
        <div class="dash-stat-label">To Do</div>
        <div class="dash-stat-value" style="color:var(--text-2)">{{ $myTasks['todo'] }}</div>
    </div>
    <div class="dash-stat-card orange">
        <div class="dash-stat-label">In Progress</div>
        <div class="dash-stat-value">{{ $myTasks['in_progress'] }}</div>
    </div>
    <div class="dash-stat-card yellow">
        <div class="dash-stat-label">Ready to Test</div>
        <div class="dash-stat-value">{{ $myTasks['ready_to_test'] }}</div>
    </div>
    <div class="dash-stat-card green">
        <div class="dash-stat-label">Done</div>
        <div class="dash-stat-value">{{ $myTasks['done'] }}</div>
    </div>
</div>

<div class="dash-layout">
<div class="dash-main">

{{-- ── Quality Evaluation ───────────────────────────────────────────────────── --}}
<div class="dash-section-title">Quality Evaluation</div>
<div class="dash-grid-3" style="margin-bottom:20px">
    <div class="dash-stat-card red">
        <div class="dash-stat-label">Tổng Bug</div>
        <div class="dash-stat-value">{{ $bugStats['total'] }}</div>
        <div class="dash-stat-sub">{{ $bugStats['open'] }} đang mở · {{ $bugStats['closed'] }} đã đóng</div>
    </div>
    <div class="dash-stat-card yellow">
        <div class="dash-stat-label">Số lần Retest</div>
        <div class="dash-stat-value">{{ $retestCount }}</div>
        <div class="dash-stat-sub">Ready to Test → In Progress</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-label">Reject từ Done</div>
        <div class="dash-stat-value" style="color:var(--red)">{{ $rejectFromDone }}</div>
        <div class="dash-stat-sub">Done → In Progress</div>
    </div>
</div>

{{-- Quality theo từng dự án ──────────────────────────────────────────────────── --}}
@if ($qualityByProject->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">Chất lượng theo dự án</span></div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Dự án</th>
                <th style="width:80px;text-align:center">Bug</th>
                <th style="width:80px;text-align:center">Đã đóng</th>
                <th style="width:80px;text-align:center">Còn mở</th>
                <th style="width:80px;text-align:center">Retest</th>
                <th style="width:80px;text-align:center">Reject</th>
                <th style="width:110px">Tỉ lệ đóng</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($qualityByProject as $q)
                @php $rate = $q->bug_total > 0 ? round($q->bug_closed / $q->bug_total * 100) : 0; @endphp
                <tr>
                    <td>
                        <a href="{{ route('projects.show', $q->project) }}" style="color:var(--accent);text-decoration:none">
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)">{{ $q->project->code }}</span>
                            {{ $q->project->name }}
                        </a>
                    </td>
                    <td style="text-align:center;font-family:var(--font-mono);font-weight:700">{{ $q->bug_total }}</td>
                    <td style="text-align:center;color:var(--green);font-family:var(--font-mono)">{{ $q->bug_closed }}</td>
                    <td style="text-align:center;font-family:var(--font-mono);color:{{ $q->bug_open > 0 ? 'var(--red)' : 'var(--text-3)' }}">{{ $q->bug_open }}</td>
                    <td style="text-align:center;font-family:var(--font-mono);color:{{ $q->retest > 0 ? 'var(--yellow)' : 'var(--text-3)' }}">{{ $q->retest }}</td>
                    <td style="text-align:center;font-family:var(--font-mono);color:{{ $q->reject > 0 ? 'var(--red)' : 'var(--text-3)' }}">{{ $q->reject }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;height:4px;background:var(--bg-3);border-radius:2px;overflow:hidden">
                                <div style="height:100%;width:{{ $rate }}%;background:{{ $rate === 100 ? 'var(--green)' : 'var(--accent)' }};border-radius:2px"></div>
                            </div>
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-3);min-width:30px">{{ $rate }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Hiệu suất xử lý bug theo người ─────────────────────────────────────────── --}}
@if ($bugPerformance->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">Hiệu suất xử lý Bug</span></div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Thành viên</th>
                <th style="width:90px;text-align:center">Được giao</th>
                <th style="width:90px;text-align:center">Đã xử lý</th>
                <th style="width:120px">Tỉ lệ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bugPerformance->sortByDesc('total') as $bp)
                @php $rate = $bp->total > 0 ? round($bp->resolved / $bp->total * 100) : 0; @endphp
                <tr>
                    <td>{{ $bp->assignee?->full_name ?? '—' }}</td>
                    <td style="text-align:center;font-family:var(--font-mono);font-weight:700">{{ $bp->total }}</td>
                    <td style="text-align:center;color:var(--green);font-family:var(--font-mono)">{{ $bp->resolved }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;height:4px;background:var(--bg-3);border-radius:2px;overflow:hidden">
                                <div style="height:100%;width:{{ $rate }}%;background:{{ $rate === 100 ? 'var(--green)' : ($rate >= 50 ? 'var(--accent)' : 'var(--red)') }};border-radius:2px"></div>
                            </div>
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)">{{ $rate }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</div>{{-- /dash-main --}}

{{-- ── Sidebar ──────────────────────────────────────────────────────────────── --}}
<div class="dash-aside">

    {{-- Projects ──────────────────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title">Dự án của tôi</span></div>
        @forelse ($projects as $proj)
            <a href="{{ route('projects.show', $proj) }}" class="dash-proj-row">
                <div>
                    <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-3)">{{ $proj->code }}</div>
                    <div style="font-size:13px;font-weight:500;color:var(--text-1)">{{ $proj->name }}</div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:11px;color:var(--text-3)">{{ $proj->total_tasks }} tasks</div>
                    @if ($proj->open_bugs > 0)
                        <div style="font-size:11px;color:var(--red)">{{ $proj->open_bugs }} bugs</div>
                    @endif
                </div>
            </a>
        @empty
            <div style="padding:20px;text-align:center;color:var(--text-3);font-size:13px">Chưa có dự án.</div>
        @endforelse
    </div>

    {{-- Recent Activity ────────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Hoạt động gần đây</span></div>
        @forelse ($recentActivity as $h)
            <div class="dash-activity-row">
                <div class="dash-activity-dot"></div>
                <div style="min-width:0;flex:1">
                    <div style="font-size:12px;font-weight:500;color:var(--text-1)">
                        {{ $h->task?->code }} — {{ Str::limit($h->task?->title, 35) }}
                    </div>
                    <div style="display:flex;align-items:center;gap:5px;margin-top:3px;flex-wrap:wrap">
                        @if ($h->from_status)
                            <span class="status-pill-xs status-{{ $h->from_status }}">{{ \App\Models\Task::STATUS_LABELS[$h->from_status] ?? $h->from_status }}</span>
                            <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor" style="color:var(--text-3);flex-shrink:0"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                        @endif
                        <span class="status-pill-xs status-{{ $h->to_status }}">{{ \App\Models\Task::STATUS_LABELS[$h->to_status] ?? $h->to_status }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--text-3);margin-top:3px">
                        {{ $h->actor?->full_name }} · {{ $h->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
        @empty
            <div style="padding:20px;text-align:center;color:var(--text-3);font-size:13px">Chưa có hoạt động.</div>
        @endforelse
    </div>

</div>{{-- /dash-aside --}}
</div>{{-- /dash-layout --}}

@endsection

@push('styles')
<style>
    .dash-section-title {
        font-family: var(--font-mono); font-size: 11px; text-transform: uppercase;
        letter-spacing: .08em; color: var(--text-3); margin-bottom: 10px; font-weight: 600;
    }
    .dash-layout { display: grid; grid-template-columns: 1fr 280px; gap: 16px; align-items: start; }
    .dash-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .dash-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

    .dash-stat-card {
        background: var(--bg-1); border: 1px solid var(--border); border-radius: 8px;
        padding: 16px 18px; border-top: 3px solid var(--border);
        box-shadow: var(--shadow-sm);
    }
    .dash-stat-card.orange { border-top-color: var(--accent); }
    .dash-stat-card.yellow { border-top-color: var(--yellow); }
    .dash-stat-card.green  { border-top-color: var(--green); }
    .dash-stat-card.red    { border-top-color: var(--red); }
    .dash-stat-card.blue   { border-top-color: var(--blue); }

    .dash-stat-label { font-family: var(--font-mono); font-size: 11px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .dash-stat-value { font-family: var(--font-mono); font-size: 30px; font-weight: 700; color: var(--accent); line-height: 1; }
    .dash-stat-card.orange .dash-stat-value { color: var(--accent); }
    .dash-stat-card.yellow .dash-stat-value { color: var(--yellow); }
    .dash-stat-card.green  .dash-stat-value { color: var(--green); }
    .dash-stat-card.red    .dash-stat-value { color: var(--red); }
    .dash-stat-sub { font-size: 11px; color: var(--text-3); margin-top: 6px; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .dash-table th {
        font-family: var(--font-mono); font-size: 10px; text-transform: uppercase;
        letter-spacing: .05em; color: var(--text-3); font-weight: 600;
        padding: 8px 14px; border-bottom: 1px solid var(--border); text-align: left;
        background: var(--bg-2);
    }
    .dash-table td { padding: 10px 14px; border-bottom: 1px solid var(--border); }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-table tr:hover td { background: var(--bg-2); }

    .dash-proj-row {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 8px; padding: 10px 16px; text-decoration: none; color: inherit;
        border-bottom: 1px solid var(--border); transition: background .1s;
    }
    .dash-proj-row:last-child { border-bottom: none; }
    .dash-proj-row:hover { background: var(--bg-2); }

    .dash-activity-row {
        display: flex; gap: 10px; padding: 10px 16px;
        border-bottom: 1px solid var(--border);
    }
    .dash-activity-row:last-child { border-bottom: none; }
    .dash-activity-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--border-lit); flex-shrink: 0; margin-top: 5px;
    }

    .status-pill-xs {
        font-size: 9px; font-family: var(--font-mono); font-weight: 700;
        letter-spacing: .04em; padding: 2px 6px; border-radius: 3px; white-space: nowrap;
    }
    .status-pill-xs.status-todo            { background: var(--bg-3); color: var(--text-2); }
    .status-pill-xs.status-in_progress     { background: rgba(249,115,22,.12); color: var(--accent); }
    .status-pill-xs.status-ready_to_test   { background: rgba(180,83,9,.10);   color: var(--yellow); }
    .status-pill-xs.status-done            { background: rgba(22,163,74,.10);  color: var(--green); }

    @media (max-width: 1100px) { .dash-grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 900px)  { .dash-layout { grid-template-columns: 1fr; } .dash-grid-3 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .dash-grid-4, .dash-grid-3 { grid-template-columns: 1fr 1fr; } }
</style>
@endpush
