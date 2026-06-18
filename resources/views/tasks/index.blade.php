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
@php $hasFilter = request()->hasAny(['search','status','priority','type','assigned_to','date_from','date_to']); @endphp
<form method="GET" action="{{ route('projects.tasks.index', $project) }}" class="filter-strip filter-strip--standalone">

    <svg class="filter-icon-svg" viewBox="0 0 16 16" fill="currentColor">
        <path d="M1 2h14l-5 6v5l-4-2V8L1 2z"/>
    </svg>

    <div class="filter-group">
        <label class="filter-label">Tìm kiếm</label>
        <input type="text" name="search" class="filter-control filter-control--search {{ request('search') ? 'filter-active' : '' }}"
               placeholder="Code / tiêu đề..." value="{{ request('search') }}">
    </div>

    <div class="filter-group">
        <label class="filter-label">Trạng thái</label>
        <select name="status" class="filter-control {{ request('status') ? 'filter-active' : '' }}">
            <option value="">Tất cả</option>
            @foreach (\App\Models\Task::STATUS_LABELS as $val => $label)
                <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label class="filter-label">Ưu tiên</label>
        <select name="priority" class="filter-control {{ request('priority') ? 'filter-active' : '' }}">
            <option value="">Tất cả</option>
            @foreach (\App\Models\Task::PRIORITY_LABELS as $val => $label)
                <option value="{{ $val }}" {{ request('priority') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label class="filter-label">Loại</label>
        <select name="type" class="filter-control {{ request('type') ? 'filter-active' : '' }}">
            <option value="">Tất cả</option>
            <option value="task"           {{ request('type') === 'task'           ? 'selected' : '' }}>Task</option>
            <option value="bug"            {{ request('type') === 'bug'            ? 'selected' : '' }}>Bug (từ task)</option>
            <option value="production_bug" {{ request('type') === 'production_bug' ? 'selected' : '' }}>Bug Production</option>
            <option value="subtask"        {{ request('type') === 'subtask'        ? 'selected' : '' }}>Subtask</option>
            <option value="test"           {{ request('type') === 'test'           ? 'selected' : '' }}>Test</option>
        </select>
    </div>

    @if ($role === 'pm' || Auth::user()->isAdmin())
    <div class="filter-group">
        <label class="filter-label">Người nhận</label>
        <select name="assigned_to" class="filter-control {{ request('assigned_to') ? 'filter-active' : '' }}">
            <option value="">Tất cả</option>
            @foreach ($members as $m)
                <option value="{{ $m->id }}" {{ request('assigned_to') == $m->id ? 'selected' : '' }}>{{ $m->full_name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="filter-group">
        <label class="filter-label">Từ ngày</label>
        <input type="date" name="date_from" class="filter-control {{ request('date_from') ? 'filter-active' : '' }}" value="{{ request('date_from') }}">
    </div>

    <div class="filter-group">
        <label class="filter-label">Đến ngày</label>
        <input type="date" name="date_to" class="filter-control {{ request('date_to') ? 'filter-active' : '' }}" value="{{ request('date_to') }}">
    </div>

    <div class="filter-actions">
        <button type="submit" class="filter-btn-apply">
            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M6.5 1a5.5 5.5 0 1 0 3.89 9.397l3.357 3.356.707-.707-3.356-3.357A5.5 5.5 0 0 0 6.5 1zM2 6.5a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0z"/></svg>
            Lọc
        </button>
        @if ($hasFilter)
            <a href="{{ route('projects.tasks.index', $project) }}" class="filter-btn-clear">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M4.293 4.293a1 1 0 0 1 1.414 0L8 6.586l2.293-2.293a1 1 0 1 1 1.414 1.414L9.414 8l2.293 2.293a1 1 0 0 1-1.414 1.414L8 9.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L6.586 8 4.293 5.707a1 1 0 0 1 0-1.414z"/></svg>
                Xoá lọc
            </a>
        @endif
    </div>
</form>

{{-- ── Flash ────────────────────────────────────────────────────────────── --}}
@if (session('success'))
    <div class="alert alert-success" style="margin-bottom:12px">{!! session('success') !!}</div>
@endif

{{-- ── Task table ───────────────────────────────────────────────────────── --}}
<div class="card" style="padding:0">
    @if ($tasks->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--text-3)">
            @if (request()->hasAny(['search', 'status', 'priority', 'type', 'assigned_to', 'date_from', 'date_to']))
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
                    <tr onclick="location.href='{{ route('projects.tasks.show', [$project, $task]) }}'"
                        class="task-row {{ $task->due_date && $task->due_date->isPast() && !in_array($task->status, ['done','review_approved']) ? 'task-overdue' : '' }}">
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
                                @php
                                    $isDone  = in_array($task->status, ['done', 'review_approved']);
                                    $daysLeft = now()->startOfDay()->diffInDays($task->due_date->startOfDay(), false);
                                    $dlClass = $isDone ? 'dl-ok'
                                        : ($daysLeft < 0 ? 'dl-overdue'
                                        : ($daysLeft <= 2 ? 'dl-soon' : 'dl-ok'));
                                @endphp
                                <span class="deadline-badge {{ $dlClass }}">
                                    @if (!$isDone && $daysLeft < 0)
                                        <svg width="9" height="9" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-.75 3.5h1.5v5h-1.5v-5zm0 6h1.5v1.5h-1.5V10.5z"/></svg>
                                    @elseif (!$isDone && $daysLeft <= 2)
                                        <svg width="9" height="9" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM7.25 4h1.5v4.5l2.5 1.5-.75 1.25-3.25-2V4z"/></svg>
                                    @endif
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
    /* ── Filter strip (shared) ────────────────────────────────────────── */
    .filter-strip {
        display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap;
        padding: 14px 16px;
        background: var(--bg-2);
        border-bottom: 1px solid var(--border);
    }
    .filter-strip--standalone {
        border: 1px solid var(--border);
        border-radius: 8px;
        margin-bottom: 16px;
    }
    .filter-icon-svg { width:14px;height:14px;color:var(--text-3);flex-shrink:0;margin-bottom:7px; }
    .filter-group { display:flex;flex-direction:column;gap:4px; }
    .filter-label {
        font-family: var(--font-mono); font-size: 10px;
        text-transform: uppercase; letter-spacing: .06em;
        color: var(--text-3); white-space: nowrap;
    }
    .filter-control {
        height: 32px; padding: 0 10px; font-size: 12.5px;
        font-family: var(--font-body); color: var(--text-1);
        background: var(--bg-1); border: 1px solid var(--border);
        border-radius: 5px; cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }
    .filter-control--search { width: 190px; cursor: text; }
    .filter-control:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-glow); }
    .filter-control.filter-active {
        border-color: var(--accent);
        background: rgba(37,99,235,.06);
        color: var(--accent); font-weight: 500;
    }
    .filter-actions { display:flex;align-items:center;gap:6px;margin-left:4px; }
    .filter-btn-apply {
        display:inline-flex;align-items:center;gap:5px;
        height:32px;padding:0 14px;
        background:var(--accent);color:#fff;border:none;border-radius:5px;
        font-size:12.5px;font-weight:600;cursor:pointer;transition:opacity .15s;white-space:nowrap;
    }
    .filter-btn-apply svg { width:13px;height:13px; }
    .filter-btn-apply:hover { opacity:.88; }
    .filter-btn-clear {
        display:inline-flex;align-items:center;gap:4px;
        height:32px;padding:0 10px;font-size:12px;
        color:var(--text-3);border:1px solid var(--border);border-radius:5px;
        text-decoration:none;background:var(--bg-1);
        transition:color .15s,border-color .15s;white-space:nowrap;
    }
    .filter-btn-clear svg { width:12px;height:12px; }
    .filter-btn-clear:hover { color:var(--red);border-color:rgba(220,38,38,.3); }

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
    .task-row.task-overdue { background: rgba(220,38,38,.04); box-shadow: inset 3px 0 0 var(--red); }
    .task-row.task-overdue:hover { background: rgba(220,38,38,.08); }
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
    .type-chip-xs.type-fix      { background:rgba(37,99,235,.12);  color:var(--accent); }
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
    .status-pill.status-in_progress     { background: rgba(37,99,235,.15); color: var(--accent); }
    .status-pill.status-ready_to_test { background: rgba(234,179,8,.12); color: var(--yellow); }
    .status-pill.status-done            { background: rgba(34,197,94,.12); color: var(--green); }

    .deadline-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11.5px; font-family: var(--font-mono);
        padding: 2px 7px; border-radius: 4px; white-space: nowrap;
    }
    .deadline-badge.dl-ok       { color: var(--text-3); background: transparent; }
    .deadline-badge.dl-soon     { color: #92400e; background: rgba(245,158,11,.15); border: 1px solid rgba(245,158,11,.3); }
    .deadline-badge.dl-overdue  { color: #991b1b; background: rgba(220,38,38,.12); border: 1px solid rgba(220,38,38,.3); font-weight: 700; }

    .alert { padding:10px 14px; border-radius:6px; font-size:13px; }
    .alert-success { background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.25); color:var(--green); }
</style>
@endpush
