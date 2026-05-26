@extends('layouts.app')

@section('title', 'Chất lượng — ' . $project->name)

@section('breadcrumb')
    <a href="{{ route('quality.index') }}">Báo cáo</a>
    <span class="sep">/</span>
    <span class="current">{{ $project->name }}</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('quality.index') }}" class="btn btn-ghost btn-sm">← Quay lại</a>
@endsection

@push('styles')
<style>
    .charts-row {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .chart-card {
        background: var(--bg-1);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow-sm);
    }
    .chart-card-title {
        font-family: var(--font-mono);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: var(--text-3);
        margin-bottom: 16px;
    }
    .empty-state {
        text-align: center;
        padding: 32px;
        color: var(--text-3);
        font-size: 14px;
    }
    .rate-bar {
        height: 6px;
        background: var(--bg-3);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 4px;
    }
    .rate-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width .3s;
    }
    @media (max-width: 768px) {
        .charts-row { grid-template-columns: 1fr; }
    }

</style>
@endpush

@section('content')
<div class="page-header">
    <h1>
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:20px;height:20px;color:var(--accent)">
            <path d="M1 11l3-3 3 3 5-6 2 2-7 8-3-3-3 3z"/>
        </svg>
        {{ $project->name }}
        @if ($role)
            <span class="role-tag role-{{ $role }}" style="font-size:11px;vertical-align:middle">
                {{ \App\Models\Project::ROLE_LABELS[$role] ?? $role }}
            </span>
        @endif
    </h1>
    <p>Đánh giá chất lượng · <span style="font-family:var(--font-mono)">{{ $project->code }}</span></p>
</div>

{{-- Date filter --}}
<form method="GET" action="{{ route('quality.show', $project) }}"
      style="display:flex;align-items:flex-end;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <div>
        <label class="form-label" style="margin-bottom:5px">Từ ngày</label>
        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="width:160px">
    </div>
    <div>
        <label class="form-label" style="margin-bottom:5px">Đến ngày</label>
        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="width:160px">
    </div>
    <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 16 16" fill="currentColor"><path d="M6.5 1a5.5 5.5 0 1 0 3.89 9.397l3.357 3.356.707-.707-3.356-3.357A5.5 5.5 0 0 0 6.5 1zM2 6.5a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0z"/></svg>
        Lọc
    </button>
    @if ($dateFrom || $dateTo)
        <a href="{{ route('quality.show', $project) }}" class="btn btn-ghost">Xoá lọc</a>
    @endif
</form>

{{-- Summary cards --}}
<div class="stats-grid" style="margin-bottom:12px;">
    <div class="stat-card blue">
        <div class="stat-label">Tổng Task</div>
        <div class="stat-value" style="color:var(--blue)">{{ $taskTotal }}</div>
        <div class="stat-sub">trong dự án</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Task Hoàn thành</div>
        <div class="stat-value" style="color:var(--green)">{{ $taskDone }}</div>
        <div class="stat-sub">đã done</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Task Còn lại</div>
        <div class="stat-value" style="color:var(--accent)">{{ $taskOpen }}</div>
        <div class="stat-sub">chưa hoàn thành</div>
    </div>
    <div class="stat-card" style="border-color:var(--border)">
        <div class="stat-label">Tiến độ Task</div>
        <div class="stat-value" style="color:{{ $taskTotal > 0 && ($taskDone/$taskTotal*100) >= 80 ? 'var(--green)' : ($taskTotal > 0 && ($taskDone/$taskTotal*100) >= 50 ? 'var(--yellow)' : 'var(--text-2)') }}">
            {{ $taskTotal > 0 ? round($taskDone / $taskTotal * 100) . '%' : '—' }}
        </div>
        <div class="stat-sub">{{ $taskDone }} / {{ $taskTotal }}</div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card red">
        <div class="stat-label">Tổng Bug</div>
        <div class="stat-value">{{ $bugTotal }}</div>
        <div class="stat-sub">trong dự án</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Bug Mở</div>
        <div class="stat-value" style="color:var(--accent)">{{ $bugOpen }}</div>
        <div class="stat-sub">chưa xử lý</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Bug Đóng</div>
        <div class="stat-value" style="color:var(--green)">{{ $bugClosed }}</div>
        <div class="stat-sub">đã xử lý xong</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">DRE</div>
        <div class="stat-value" style="color:{{ $dre !== null ? ($dre >= 80 ? 'var(--green)' : ($dre >= 50 ? 'var(--yellow)' : 'var(--red)')) : 'var(--text-3)' }}">
            {{ $dre !== null ? $dre . '%' : '—' }}
        </div>
        <div class="stat-sub">Defect Removal Efficiency</div>
    </div>
    <div class="stat-card" style="border-color:var(--border)">
        <div class="stat-label">Retest</div>
        <div class="stat-value" style="color:var(--yellow)">{{ $retestTotal }}</div>
        <div class="stat-sub">lần kiểm tra lại</div>
    </div>
</div>

{{-- Charts --}}
@if ($bugTotal > 0 || $devStats->isNotEmpty())
<div class="charts-row">
    {{-- Pie: bug status --}}
    <div class="chart-card">
        <div class="chart-card-title">Phân bổ trạng thái Bug</div>
        @if ($bugTotal > 0)
            <div style="position:relative;height:220px">
                <canvas id="pieChart"></canvas>
            </div>
        @else
            <div class="empty-state">Chưa có bug nào</div>
        @endif
    </div>

    {{-- Bar: retest per developer --}}
    <div class="chart-card">
        <div class="chart-card-title">Số lần Retest theo Developer</div>
        @if ($devStats->isNotEmpty() && $devStats->sum('retest_count') > 0)
            <div style="position:relative;height:220px">
                <canvas id="barChart"></canvas>
            </div>
        @elseif ($devStats->isEmpty())
            <div class="empty-state">Không có Developer trong dự án</div>
        @else
            <div class="empty-state">Chưa có retest nào</div>
        @endif
    </div>
</div>
@endif

{{-- Tester table --}}
@if ($testerStats->isNotEmpty())
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;">
    <div class="card-header" style="padding:16px 20px;margin:0;">
        <span class="card-title">
            <span class="role-tag role-tester" style="margin-right:6px">Tester</span>
            Hiệu suất Tester
        </span>
        <span style="font-size:12px;color:var(--text-3)">{{ $testerStats->count() }} người</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tester</th>
                    <th style="text-align:center" title="Số bug tester tìm và log lên">Bug Tìm Được</th>
                    <th style="text-align:center" title="Số bug đã được đóng (done)">Đã Đóng</th>
                    <th style="text-align:center" title="Số task/bug tester xác nhận xong (chuyển Review Approved hoặc Done)">Task Xác nhận</th>
                    <th style="text-align:center" title="Tỷ lệ bug đã đóng / tổng bug tìm được">Tỷ lệ đóng</th>
                    <th style="text-align:center" title="Bug tìm được / tổng bug toàn dự án × 100">DRE đóng góp</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($testerStats as $row)
                    <tr>
                        <td>
                            <div style="font-weight:600">{{ $row->user->full_name }}</div>
                            <div style="font-size:11px;color:var(--text-3)">{{ $row->user->username }}</div>
                        </td>
                        <td style="text-align:center;font-family:var(--font-mono);font-weight:700">
                            {{ $row->bugs_found ?: '—' }}
                        </td>
                        <td style="text-align:center;font-family:var(--font-mono);font-weight:700;color:var(--green)">
                            {{ $row->bugs_found > 0 ? $row->bugs_closed : '—' }}
                        </td>
                        <td style="text-align:center;font-family:var(--font-mono);font-weight:700;color:var(--blue)">
                            {{ $row->tasks_verified ?: '—' }}
                        </td>
                        <td style="text-align:center;min-width:120px">
                            @if ($row->close_rate !== null)
                                <span style="font-family:var(--font-mono);font-weight:700;
                                    color:{{ $row->close_rate >= 80 ? 'var(--green)' : ($row->close_rate >= 50 ? 'var(--yellow)' : 'var(--red)') }}">
                                    {{ $row->close_rate }}%
                                </span>
                                <div class="rate-bar" style="width:80px;margin:4px auto 0">
                                    <div class="rate-bar-fill" style="width:{{ $row->close_rate }}%;background:{{ $row->close_rate >= 80 ? 'var(--green)' : ($row->close_rate >= 50 ? 'var(--yellow)' : 'var(--red)') }}"></div>
                                </div>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if ($row->dre !== null)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--blue)">{{ $row->dre }}%</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Developer table --}}
@if ($devStats->isNotEmpty())
<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;">
    <div class="card-header" style="padding:16px 20px;margin:0;">
        <span class="card-title">
            <span class="role-tag role-developer" style="margin-right:6px">Developer</span>
            Hiệu suất Developer
        </span>
        <span style="font-size:12px;color:var(--text-3)">{{ $devStats->count() }} người</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Developer</th>
                    <th style="text-align:center" title="Task (không phải bug): số đã giao lên RTT / tổng">Task</th>
                    <th style="text-align:center" title="Bug đã fix lên RTT / tổng được giao">Bug Fix</th>
                    <th style="text-align:center" title="Tổng đã giao lên RTT hoặc Done / tổng được giao">Hoàn thành</th>
                    <th style="text-align:center" title="Số lần task trả về từ Ready to Test">Retest</th>
                    <th style="text-align:center" title="Trung bình estimated_hours của các task/bug dev đã hoàn thành">Avg Est.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($devStats as $row)
                    <tr>
                        <td>
                            <div style="font-weight:600">{{ $row->user->full_name }}</div>
                            <div style="font-size:11px;color:var(--text-3)">{{ $row->user->username }}</div>
                        </td>

                        {{-- Task column --}}
                        <td style="text-align:center">
                            @if ($row->tasks_total > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--green)">{{ $row->tasks_done }}</span>
                                <span style="font-family:var(--font-mono);color:var(--text-3);font-size:12px"> / {{ $row->tasks_total }}</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>

                        {{-- Bug Fix column --}}
                        <td style="text-align:center">
                            @if ($row->bugs_total > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--red)">{{ $row->bugs_resolved }}</span>
                                <span style="font-family:var(--font-mono);color:var(--text-3);font-size:12px"> / {{ $row->bugs_total }}</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>

                        {{-- Done rate (tasks + bugs combined) --}}
                        <td style="text-align:center;min-width:110px">
                            @if ($row->done_rate !== null)
                                @php $rc = $row->done_rate; @endphp
                                <span style="font-family:var(--font-mono);font-weight:700;
                                    color:{{ $rc >= 80 ? 'var(--green)' : ($rc >= 50 ? 'var(--yellow)' : 'var(--red)') }}">
                                    {{ $rc }}%
                                </span>
                                <div class="rate-bar" style="width:80px;margin:4px auto 0">
                                    <div class="rate-bar-fill" style="width:{{ $rc }}%;
                                        background:{{ $rc >= 80 ? 'var(--green)' : ($rc >= 50 ? 'var(--yellow)' : 'var(--red)') }}"></div>
                                </div>
                                <div style="font-size:10px;color:var(--text-3);margin-top:2px;font-family:var(--font-mono)">
                                    {{ $row->total_done }}/{{ $row->total_work }}
                                </div>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>

                        {{-- Retest --}}
                        <td style="text-align:center">
                            @if ($row->retest_count > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--yellow)">{{ $row->retest_count }}</span>
                            @else
                                <span style="color:var(--text-3)">0</span>
                            @endif
                        </td>

                        {{-- Avg fix time --}}
                        <td style="text-align:center">
                            @if ($row->avg_fix_hours !== null)
                                @php
                                    $h = (int) floor($row->avg_fix_hours);
                                    $m = (int) round(($row->avg_fix_hours - $h) * 60);
                                    if ($m === 60) { $h++; $m = 0; }
                                @endphp
                                <span style="font-family:var(--font-mono);font-size:13px;color:var(--text-2)">
                                    @if ($h > 0 && $m > 0)
                                        {{ $h }}h {{ $m }}m
                                    @elseif ($h > 0)
                                        {{ $h }}h
                                    @elseif ($m > 0)
                                        {{ $m }}m
                                    @else
                                        &lt; 1m
                                    @endif
                                </span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if ($testerStats->isEmpty() && $devStats->isEmpty())
    <div class="card" style="text-align:center;padding:48px;color:var(--text-3)">
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:32px;height:32px;opacity:.3;margin-bottom:12px;display:block;margin-inline:auto">
            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 2a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm-1 4h2v4H7V7zm0 5h2v2H7v-2z"/>
        </svg>
        Không có dữ liệu nhân viên trong khoảng thời gian này.
    </div>
@endif

{{-- Legend --}}
<div style="margin-top:16px;font-size:12px;color:var(--text-3);display:flex;gap:20px;flex-wrap:wrap;">
    <span><strong style="color:var(--text-2)">DRE</strong> = Bugs đóng / Tổng bugs × 100</span>
    <span><strong style="color:var(--text-2)">Retest</strong> = Số lần task từ Ready to Test → In Progress</span>
    <span><strong style="color:var(--text-2)">Hoàn thành (Dev)</strong> = Task/Bug đã giao lên RTT hoặc Done</span>
    <span><strong style="color:var(--text-2)">Task Xác nhận (Tester)</strong> = Task tester chuyển sang Review Approved / Done</span>
    <span><strong style="color:var(--text-2)">Avg Est.</strong> = Trung bình giờ ước tính của các task dev đã hoàn thành</span>
    <span style="color:var(--green)">● ≥ 80% tốt</span>
    <span style="color:var(--yellow)">● 50–79% trung bình</span>
    <span style="color:var(--red)">● &lt; 50% cần cải thiện</span>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size   = 12;

    // ── Pie chart ──────────────────────────────────────────────────────────
    const pieEl = document.getElementById('pieChart');
    if (pieEl) {
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: ['Bug Mở', 'Bug Đóng'],
                datasets: [{
                    data: [{{ $chartBugStatus['open'] }}, {{ $chartBugStatus['closed'] }}],
                    backgroundColor: ['rgba(220,38,38,.75)', 'rgba(22,163,74,.75)'],
                    borderColor:     ['#dc2626', '#16a34a'],
                    borderWidth: 1.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#374151', padding: 16, boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} bug`
                        }
                    }
                }
            }
        });
    }

    // ── Bar chart ──────────────────────────────────────────────────────────
    const barEl = document.getElementById('barChart');
    if (barEl) {
        const labels = @json($chartRetest['labels']);
        const data   = @json($chartRetest['data']);
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Số lần Retest',
                    data,
                    backgroundColor: 'rgba(217,119,6,.7)',
                    borderColor:     '#d97706',
                    borderWidth: 1.5,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#6b7280' },
                        grid:  { color: '#f3f4f6' }
                    },
                    x: {
                        ticks: { color: '#6b7280' },
                        grid:  { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Retest: ${ctx.parsed.y} lần`
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
