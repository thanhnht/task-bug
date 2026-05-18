@extends('layouts.app')

@section('title', 'Báo cáo Chất lượng')

@section('breadcrumb')
    <span class="current">Báo cáo Chất lượng</span>
@endsection

@section('content')
<div class="page-header">
    <h1>
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:20px;height:20px;color:var(--accent)">
            <path d="M1 11l3-3 3 3 5-6 2 2-7 8-3-3-3 3z"/>
        </svg>
        Báo cáo <span class="accent">Chất lượng</span>
    </h1>
    <p>Thống kê bug, retest và hiệu suất theo dự án</p>
</div>

{{-- Date filter --}}
<form method="GET" action="{{ route('quality.index') }}" style="display:flex;align-items:flex-end;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
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
        <a href="{{ route('quality.index') }}" class="btn btn-ghost">Xoá lọc</a>
    @endif
</form>

{{-- Summary cards --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:28px;">
    <div class="stat-card red">
        <div class="stat-label">Tổng Bug</div>
        <div class="stat-value">{{ $totals->bugs }}</div>
        <div class="stat-sub">trên tất cả dự án</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Bug Mở</div>
        <div class="stat-value" style="color:var(--accent)">{{ $totals->open }}</div>
        <div class="stat-sub">chưa xử lý</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Bug Đóng</div>
        <div class="stat-value" style="color:var(--green)">{{ $totals->closed }}</div>
        <div class="stat-sub">đã xử lý xong</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Tổng Retest</div>
        <div class="stat-value" style="color:var(--blue)">{{ $totals->retest }}</div>
        <div class="stat-sub">lần quay lại</div>
    </div>
</div>

{{-- Project table --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div class="card-header" style="padding:16px 20px;margin:0;border-bottom:1px solid var(--border)">
        <span class="card-title">Theo Dự Án</span>
        <span style="font-size:12px;color:var(--text-3)">{{ $projectStats->count() }} dự án</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Dự Án</th>
                    <th>Vai trò</th>
                    <th style="text-align:center">Tổng Bug</th>
                    <th style="text-align:center">Mở</th>
                    <th style="text-align:center">Đóng</th>
                    <th style="text-align:center">DRE</th>
                    <th style="text-align:center">Retest</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projectStats as $row)
                    <tr>
                        <td>
                            <div style="font-weight:600;color:var(--text-1)">{{ $row->project->name }}</div>
                            <div style="font-size:12px;color:var(--text-3);font-family:var(--font-mono)">{{ $row->project->code }}</div>
                        </td>
                        <td>
                            @if ($row->role)
                                <span class="role-tag role-{{ $row->role }}">{{ \App\Models\Project::ROLE_LABELS[$row->role] ?? $row->role }}</span>
                            @else
                                <span style="color:var(--text-3);font-size:12px">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;font-family:var(--font-mono);font-weight:700">
                            {{ $row->bugs_total ?: '—' }}
                        </td>
                        <td style="text-align:center">
                            @if ($row->bugs_open > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--red)">{{ $row->bugs_open }}</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if ($row->bugs_closed > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--green)">{{ $row->bugs_closed }}</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if ($row->dre !== null)
                                @php $dreColor = $row->dre >= 80 ? 'var(--green)' : ($row->dre >= 50 ? 'var(--yellow)' : 'var(--red)'); @endphp
                                <span style="font-family:var(--font-mono);font-weight:700;color:{{ $dreColor }}">{{ $row->dre }}%</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if ($row->retest > 0)
                                <span style="font-family:var(--font-mono);font-weight:700;color:var(--yellow)">{{ $row->retest }}</span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('quality.show', [$row->project, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                               class="btn btn-ghost btn-sm">
                                Chi tiết →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:var(--text-3)">
                            Không có dự án nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- DRE legend --}}
<div style="margin-top:16px;display:flex;gap:20px;flex-wrap:wrap;font-size:12px;color:var(--text-3)">
    <span><strong style="color:var(--text-2)">DRE</strong> (Defect Removal Efficiency) = Bugs đã đóng / Tổng bugs × 100</span>
    <span style="color:var(--green)">● ≥ 80% tốt</span>
    <span style="color:var(--yellow)">● 50–79% trung bình</span>
    <span style="color:var(--red)">● &lt; 50% cần cải thiện</span>
</div>
@endsection
