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

{{-- ── KPI cá nhân (Dev / Tester) ──────────────────────────────────────────── --}}
@php
    $kpiClass = $myKpiScore >= 90 ? 'green' : ($myKpiScore >= 70 ? 'yellow' : 'red');
    $kpiLabel = $myKpiScore >= 90 ? 'Tốt' : ($myKpiScore >= 70 ? 'Cần cải thiện' : 'Cảnh báo');
@endphp
<div class="dash-section-title">KPI Tháng {{ \Carbon\Carbon::createFromFormat('Y-m', $currentMonth)->format('m/Y') }}</div>
<div class="dash-grid-4" style="margin-bottom:16px">
    <div class="dash-stat-card {{ $kpiClass }}">
        <div class="dash-stat-label">Điểm KPI của tôi</div>
        <div class="dash-stat-value">{{ number_format($myKpiScore, 1) }}</div>
        <div class="dash-stat-sub">{{ $kpiLabel }} · Cơ sở: 100 / tháng</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-label">Số lần bị trừ</div>
        <div class="dash-stat-value" style="color:var(--red)">{{ $myKpiTransactions->count() }}</div>
        <div class="dash-stat-sub">sự kiện tháng này</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-label">Tổng trừ</div>
        <div class="dash-stat-value" style="color:var(--red)">{{ number_format($myKpiTransactions->sum('points'), 1) }}</div>
        <div class="dash-stat-sub">điểm</div>
    </div>
    <div class="dash-stat-card blue">
        <div class="dash-stat-label">Cơ sở</div>
        <div class="dash-stat-value">100</div>
        <div class="dash-stat-sub">điểm / tháng</div>
    </div>
</div>

@if ($myKpiTransactions->isNotEmpty())
<div class="card" style="margin-bottom:28px">
    <div class="card-header"><span class="card-title">Lịch sử biến động KPI tháng này</span></div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Lý do</th>
                <th style="width:80px;text-align:right">Điểm</th>
                <th style="width:100px">Dự án</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($myKpiTransactions as $tx)
            <tr>
                <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3);white-space:nowrap">
                    {{ $tx->created_at->format('d/m H:i') }}
                </td>
                <td style="font-size:13px">
                    @if($tx->task)
                        <a href="{{ route('projects.tasks.show', [$tx->project_id ?? 0, $tx->task_id]) }}"
                           style="color:var(--accent);font-family:var(--font-mono);font-size:11px;margin-right:6px">
                            {{ $tx->task->code }}
                        </a>
                    @endif
                    {{ $tx->reason }}
                </td>
                <td style="text-align:right;font-family:var(--font-mono);font-weight:700;
                    color:{{ $tx->points < 0 ? 'var(--red)' : 'var(--green)' }}">
                    {{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points, 2) }}
                </td>
                <td style="font-family:var(--font-mono);font-size:11px;color:var(--text-3)">
                    {{ $tx->project?->code ?? '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

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

{{-- ── KPI Section ──────────────────────────────────────────────────────────── --}}
@php
    $kpiMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $currentMonth)->format('m/Y');
    $pmProjects = $projects->filter(fn($p) => $p->roleOf($user) === 'pm' || $user->isAdmin());
@endphp

{{-- Project selector (chỉ hiện nếu tham gia nhiều hơn 1 project) --}}
@if ($projects->count() > 1)
<div class="kpi-project-filter">
    <form method="GET" action="{{ route('employee.dashboard') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:13px;color:var(--text-2);font-weight:500">KPI theo dự án:</span>
        <select name="kpi_project" class="form-control" style="width:220px;font-size:13px"
                onchange="this.form.submit()">
            <option value="">— Tất cả dự án —</option>
            @foreach ($projects as $p)
            <option value="{{ $p->id }}" {{ $kpiProject?->id == $p->id ? 'selected' : '' }}>
                [{{ $p->code }}] {{ $p->name }}
            </option>
            @endforeach
        </select>
        @if ($kpiProject)
        @php $myRoleLabel = match($roleInKpiProject) { 'pm'=>'PM', 'developer'=>'Developer', 'tester'=>'Tester', 'admin'=>'Admin', default=>'Thành viên' }; @endphp
        <span class="role-tag role-{{ $roleInKpiProject === 'admin' ? 'pm' : $roleInKpiProject }}"
              style="font-size:11px">
            Vai trò của bạn: {{ $myRoleLabel }}
        </span>
        <a href="{{ route('employee.dashboard') }}" class="btn btn-ghost btn-sm" style="font-size:12px">✕ Bỏ lọc</a>
        @endif
    </form>
</div>
@endif

{{-- KPI Team (PM/Admin trong project được chọn) --}}
@if ($isPmOrAdmin && $teamKpiData->isNotEmpty())
<div class="dash-section-title" style="margin-top:8px">
    Cảnh báo KPI Team — tháng {{ $kpiMonthLabel }}
    @if ($kpiProject) <span style="font-weight:400;font-size:12px;color:var(--text-3)"> · {{ $kpiProject->name }}</span> @endif
</div>
<div class="card" style="margin-bottom:20px">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Thành viên</th>
                <th style="width:80px">Vai trò</th>
                <th style="width:110px">Điểm KPI</th>
                <th style="width:80px;text-align:right">Trừ</th>
                <th style="width:130px">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teamKpiData as $kd)
            @php
                $sc = $kd->score;
                $barColor = $sc >= 90 ? 'var(--green)' : ($sc >= 70 ? 'var(--yellow)' : 'var(--red)');
                $badge    = $sc >= 90 ? 'Tốt' : ($sc >= 70 ? 'Cần cải thiện' : 'Cảnh báo');
                $deducted = round($sc - \App\Services\KpiService::BASE_SCORE, 2);
            @endphp
            <tr>
                <td style="font-weight:500">{{ $kd->user->full_name }}</td>
                <td>
                    @if($kd->role)
                        <span class="role-tag role-{{ $kd->role }}">{{ \App\Models\Project::ROLE_LABELS[$kd->role] ?? $kd->role }}</span>
                    @else —
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="flex:1;height:5px;background:var(--bg-3);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $sc }}%;background:{{ $barColor }};border-radius:3px"></div>
                        </div>
                        <span style="font-family:var(--font-mono);font-size:12px;font-weight:700;color:{{ $barColor }};min-width:34px">
                            {{ number_format($sc, 1) }}
                        </span>
                    </div>
                </td>
                <td style="text-align:right;font-family:var(--font-mono);font-size:12px;
                    color:{{ $deducted < 0 ? 'var(--red)' : 'var(--text-3)' }}">
                    {{ $deducted < 0 ? number_format($deducted, 1) : '—' }}
                </td>
                <td>
                    <span style="font-size:11px;font-family:var(--font-mono);font-weight:700;
                        color:{{ $sc >= 90 ? 'var(--green)' : ($sc >= 70 ? 'var(--yellow)' : 'var(--red)') }}">
                        {{ $badge }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@elseif ($kpiProject && !$isPmOrAdmin)
{{-- Dev/Tester đã chọn project nhưng không phải PM → chỉ thấy KPI cá nhân --}}
<div class="auto-status-info" style="margin-bottom:16px;background:rgba(37,99,235,.06);border-color:var(--border-lit);color:var(--text-2)">
    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-.75 3.5h1.5v5h-1.5v-5zm0 6.5h1.5v1.5h-1.5V11z"/>
    </svg>
    Bạn là <strong>{{ $myRoleLabel ?? 'thành viên' }}</strong> trong dự án này — chỉ xem được KPI cá nhân.
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
    .status-pill-xs.status-in_progress     { background: rgba(37,99,235,.12); color: var(--accent); }
    .status-pill-xs.status-ready_to_test   { background: rgba(180,83,9,.10);   color: var(--yellow); }
    .status-pill-xs.status-done            { background: rgba(22,163,74,.10);  color: var(--green); }

    @media (max-width: 1100px) { .dash-grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 900px)  { .dash-layout { grid-template-columns: 1fr; } .dash-grid-3 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .dash-grid-4, .dash-grid-3 { grid-template-columns: 1fr 1fr; } }

    .kpi-project-filter {
        background: var(--bg-1);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
        margin-top: 8px;
    }
</style>
@endpush
