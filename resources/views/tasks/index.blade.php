@extends('layouts.app')

@section('title', 'Danh sách Task — ' . $project->name)

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <a href="{{ route('projects.show', $project) }}">{{ $project->code }}</a>
    <span class="sep">/</span>
    <span class="current">Tasks</span>
@endsection

@section('topbar-actions')
    @if ($role === 'pm' || Auth::user()->isAdmin())
        <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-primary">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:14px;height:14px">
                <path d="M8 2v12M2 8h12"/>
            </svg>
            Tạo Task
        </a>
    @endif
@endsection

@section('content')

<div class="page-header">
    <h1>Tasks — <span class="accent">{{ $project->name }}</span></h1>
</div>

{{-- ── Filters ──────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('projects.tasks.index', $project) }}" class="filter-bar">
    <input type="text" name="search" class="form-control" style="max-width:220px"
        placeholder="Tìm code / tiêu đề..." value="{{ request('search') }}">

    <select name="status" class="form-control" style="width:160px">
        <option value="">Tất cả trạng thái</option>
        @foreach (\App\Models\Task::STATUS_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="priority" class="form-control" style="width:140px">
        <option value="">Tất cả ưu tiên</option>
        @foreach (\App\Models\Task::PRIORITY_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('priority') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="type" class="form-control" style="width:130px">
        <option value="">Tất cả loại</option>
        @foreach (\App\Models\Task::TYPE_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-ghost btn-sm">Lọc</button>
    @if (request()->hasAny(['search', 'status', 'priority', 'type']))
        <a href="{{ route('projects.tasks.index', $project) }}" class="btn btn-ghost btn-sm">Xoá lọc</a>
    @endif
</form>

{{-- ── Flash ────────────────────────────────────────────────────────────── --}}
@if (session('success'))
    <div class="alert alert-success" style="margin-bottom:12px">{!! session('success') !!}</div>
@endif

{{-- ── Task table ───────────────────────────────────────────────────────── --}}
<div class="card" style="padding:0">
    @if ($tasks->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--text-3)">
            @if (request()->hasAny(['search', 'status', 'priority', 'type']))
                Không tìm thấy task nào khớp bộ lọc.
            @elseif ($role === 'pm' || Auth::user()->isAdmin())
                Chưa có Task nào. <a href="{{ route('projects.tasks.create', $project) }}" style="color:var(--accent)">Tạo Task đầu tiên</a>
            @else
                Chưa có Task nào được giao cho bạn.
            @endif
        </div>
    @else
        <table class="task-table">
            <thead>
                <tr>
                    <th style="width:90px">Code</th>
                    <th>Tiêu đề</th>
                    <th style="width:80px">Loại</th>
                    <th style="width:90px">Ưu tiên</th>
                    <th style="width:130px">Giao cho</th>
                    <th style="width:90px">Deadline</th>
                    <th style="width:120px">Tiến độ</th>
                    <th style="width:130px">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    @php
                        $pct = $task->children_count > 0
                            ? (int) round(($task->children_count - $task->pending_children_count) / $task->children_count * 100)
                            : ($task->status === 'done' ? 100 : 0);
                    @endphp
                    <tr onclick="location.href='{{ route('projects.tasks.show', [$project, $task]) }}'" class="task-row">
                        <td><span class="task-code-mono">{{ $task->code }}</span></td>
                        <td>
                            <div class="task-title-cell">{{ $task->title }}</div>
                            @if ($task->children_count > 0)
                                <div style="font-size:11px;color:var(--text-3);margin-top:2px">
                                    {{ $task->children_count - $task->pending_children_count }}/{{ $task->children_count }} task con xong
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="type-chip-xs type-{{ $task->type }}">{{ $task->typeLabel() }}</span>
                        </td>
                        <td>
                            <span class="priority-pill priority-{{ $task->priority }}">{{ $task->priorityLabel() }}</span>
                        </td>
                        <td>
                            @if ($task->assignee)
                                <span style="font-size:12.5px">{{ $task->assignee->full_name }}</span>
                            @else
                                <span style="font-size:12px;color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($task->due_date)
                                <span style="font-size:12px;{{ $task->due_date->isPast() && $task->status !== 'done' ? 'color:var(--red)' : 'color:var(--text-3)' }}">
                                    {{ $task->due_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span style="color:var(--text-3)">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="mini-progress-track">
                                <div class="mini-progress-fill {{ $pct === 100 ? 'done' : '' }}"
                                     style="width:{{ $pct }}%"></div>
                            </div>
                            <div style="font-size:10px;color:var(--text-3);margin-top:2px;font-family:var(--font-mono)">
                                {{ $pct }}%
                            </div>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $task->status }}">{{ $task->statusLabel() }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($tasks->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border)">
                {{ $tasks->links() }}
            </div>
        @endif
    @endif
</div>

@endsection

@push('styles')
<style>
    .filter-bar {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .task-table {
        width: 100%; border-collapse: collapse; font-size: 13.5px;
    }
    .task-table th {
        font-family: var(--font-mono); font-size: 11px; text-transform: uppercase;
        letter-spacing: .05em; color: var(--text-3); font-weight: 600;
        padding: 10px 12px; border-bottom: 1px solid var(--border);
        text-align: left; background: var(--bg-1);
    }
    .task-table th:first-child { border-radius: 8px 0 0 0; }
    .task-table th:last-child  { border-radius: 0 8px 0 0; }

    .task-row {
        cursor: pointer; transition: background .1s;
        border-bottom: 1px solid var(--border);
    }
    .task-row:last-child { border-bottom: none; }
    .task-row:hover { background: var(--bg-2); }
    .task-row td { padding: 10px 12px; vertical-align: middle; }

    .task-code-mono {
        font-family: var(--font-mono); font-size: 11px; color: var(--text-3);
    }
    .task-title-cell { font-size: 13.5px; font-weight: 500; }

    .type-chip-xs {
        font-family: var(--font-mono); font-size: 9px; font-weight: 700;
        padding: 1px 5px; border-radius: 3px; text-transform: uppercase;
        white-space: nowrap;
    }
    .type-chip-xs.type-task     { background:rgba(59,130,246,.15);  color:var(--blue); }
    .type-chip-xs.type-subtask  { background:rgba(100,116,139,.15); color:var(--text-2); }
    .type-chip-xs.type-bug      { background:rgba(239,68,68,.12);   color:var(--red); }
    .type-chip-xs.type-research { background:rgba(168,85,247,.12);  color:#a855f7; }
    .type-chip-xs.type-fix      { background:rgba(249,115,22,.12);  color:var(--accent); }
    .type-chip-xs.type-test     { background:rgba(34,197,94,.12);   color:var(--green); }

    .priority-pill {
        font-family: var(--font-mono); font-size: 10px; font-weight: 700;
        padding: 2px 7px; border-radius: 4px; text-transform: uppercase; letter-spacing: .04em;
    }
    .priority-pill.priority-low      { background:rgba(100,116,139,.15); color:var(--text-3); }
    .priority-pill.priority-medium   { background:rgba(59,130,246,.15);  color:var(--blue); }
    .priority-pill.priority-high     { background:rgba(234,179,8,.12);   color:var(--yellow); }
    .priority-pill.priority-critical { background:rgba(239,68,68,.12);   color:var(--red); }

    .mini-progress-track {
        height: 4px; background: var(--bg-3); border-radius: 2px; overflow: hidden;
    }
    .mini-progress-fill {
        height: 100%; background: var(--accent); border-radius: 2px; transition: width .3s;
        min-width: 2px;
    }
    .mini-progress-fill.done { background: var(--green); }

    .status-pill {
        font-size: 11px; font-family: var(--font-mono); font-weight: 700;
        letter-spacing: .04em; padding: 3px 8px; border-radius: 4px; white-space: nowrap;
    }
    .status-pill.status-todo            { background: var(--bg-3); color: var(--text-3); }
    .status-pill.status-in_progress     { background: rgba(249,115,22,.15); color: var(--accent); }
    .status-pill.status-ready_to_test { background: rgba(234,179,8,.12); color: var(--yellow); }
    .status-pill.status-done            { background: rgba(34,197,94,.12); color: var(--green); }

    .alert { padding:10px 14px; border-radius:6px; font-size:13px; }
    .alert-success { background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.25); color:var(--green); }
</style>
@endpush
