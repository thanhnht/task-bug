@extends('layouts.app')

@section('title', $task->code . ' — ' . $task->title)

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <a href="{{ route('projects.show', $project) }}">{{ $project->code }}</a>
    <span class="sep">/</span>
    @if ($task->parent)
        <a href="{{ route('projects.tasks.show', [$project, $task->parent]) }}">{{ $task->parent->code }}</a>
        <span class="sep">/</span>
    @endif
    <span class="current">{{ $task->code }}</span>
@endsection

@section('topbar-actions')
    @if ($role === 'pm' || Auth::user()->isAdmin())
        <button class="btn btn-ghost" onclick="toggleEdit()">
            <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px">
                <path d="M11.5 2.5 13 4l-8 8-2 .5.5-2 8-8zm1-1a1 1 0 0 1 .7.3l1 1a1 1 0 0 1 0 1.4l-9 9-3 .8.8-3 9-9A1 1 0 0 1 12.5 1.5z" />
            </svg>
            Chỉnh sửa
        </button>
    @endif
@endsection

@section('content')

    {{-- ── Task header ─────────────────────────────────────────────────────── --}}
    <div class="task-header-card">

        <div class="task-meta-top">
            <span class="task-code-lg">{{ $task->code }}</span>
            <span class="type-chip type-{{ $task->type }}">{{ $task->typeLabel() }}</span>
            <span class="priority-chip priority-{{ $task->priority }}">{{ $task->priorityLabel() }}</span>
            @if ($task->assignee)
                <span class="assignee-chip">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    {{ $task->assignee->full_name }}
                </span>
            @endif
            <span class="assignee-chip" style="margin-left:auto;color:var(--text-3)">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/></svg>
                {{ $task->creator->full_name }}
            </span>
        </div>

        <h1 class="task-title-lg">{{ $task->title }}</h1>

        @if ($task->description)
            <p class="task-desc-lg">{{ $task->description }}</p>
        @endif

        {{-- ── Dates / hours / progress ─────────────────────────────────────── --}}
        @php
            $progress       = $task->progressPercent();
            $effectiveHours = $task->effectiveEstimatedHours();
            $hoursFromKids  = $task->children->count() > 0 && $effectiveHours !== null;
        @endphp
        <div class="task-info-row">
            @if ($task->start_date)
                <span class="info-badge">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M4 1v1H2a1 1 0 0 0-1 1v11h14V3a1 1 0 0 0-1-1h-2V1h-2v1H6V1H4zm8 3H4v1h8V4z"/></svg>
                    Bắt đầu: {{ $task->start_date->format('d/m/Y') }}
                </span>
            @endif
            @if ($task->due_date)
                <span class="info-badge {{ $task->due_date->isPast() && $task->status !== 'done' ? 'info-badge-overdue' : '' }}">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M4 1v1H2a1 1 0 0 0-1 1v11h14V3a1 1 0 0 0-1-1h-2V1h-2v1H6V1H4zm8 3H4v1h8V4zM3 7h2v2H3V7zm3 0h2v2H6V7zm3 0h2v2H9V7zM3 11h2v2H3v-2zm3 0h2v2H6v-2z"/></svg>
                    Kết thúc: {{ $task->due_date->format('d/m/Y') }}
                    @if($task->due_date->isPast() && $task->status !== 'done') — <strong>Quá hạn</strong>@endif
                </span>
            @endif
            @if ($effectiveHours)
                <span class="info-badge">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 2a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm.5 2H7v4l3 1.5.7-1.3-2.2-1.2V5z"/></svg>
                    {{ $hoursFromKids ? 'Tổng ước tính' : 'Ước tính' }}: {{ $effectiveHours }}h
                    @if ($hoursFromKids)
                        <span style="color:var(--text-3)">({{ $task->children->count() }} task con)</span>
                    @endif
                </span>
            @endif
        </div>

        {{-- Progress bar --}}
        @php
            $totalChildren    = $task->children->count();
            $nonBugChildren   = $task->children->where('type', '!=', 'bug')->count();
            $doneChildren     = $task->children->where('status', 'done')->count();
        @endphp
        <div class="progress-section">
            <div class="progress-label">
                <span>
                    Tiến độ
                    @if ($totalChildren > 0)
                        <span style="font-weight:400;color:var(--text-3)">({{ $doneChildren }}/{{ $totalChildren }} task con)</span>
                    @elseif ($task->manual_progress !== null)
                        <span style="font-weight:400;color:var(--text-3)">(thủ công)</span>
                    @endif
                </span>
                <span class="progress-pct {{ $progress === 100 ? 'progress-done' : '' }}">{{ $progress }}%</span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill {{ $progress === 100 ? 'progress-done' : '' }}"
                     style="width:{{ $progress }}%"></div>
            </div>
            @if ($nonBugChildren === 0 && $task->status !== 'done')
                {{-- Không có task con: cho cập nhật % thủ công từng ngày --}}
                <form method="POST" action="{{ route('projects.tasks.progress', [$project, $task]) }}"
                      style="margin-top:10px">
                    @csrf @method('PATCH')
                    <div style="font-size:11px;color:var(--text-3);margin-bottom:5px">Cập nhật tiến độ hôm nay:</div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        @foreach ([0,10,20,30,40,50,60,70,80,90,100] as $pval)
                            <button type="submit" name="progress" value="{{ $pval }}"
                                class="pct-btn {{ (int)$task->manual_progress === $pval ? 'pct-btn-active' : '' }}">
                                {{ $pval }}%
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif
        </div>

        {{-- ── Status pipeline ──────────────────────────────────────────────── --}}
        <div class="pipeline">
            @php
                $steps      = [
                    ['key' => 'todo',            'label' => 'To Do'],
                    ['key' => 'in_progress',     'label' => 'In Progress'],
                    ['key' => 'ready_to_test', 'label' => 'Ready to Test'],
                    ['key' => 'done',            'label' => 'Done'],
                ];
                $order      = array_column($steps, 'key');
                $currentIdx = array_search($task->status, $order);
            @endphp

            @foreach ($steps as $i => $step)
                <div class="pipeline-step {{ $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'active' : 'pending') }}">
                    <div class="pipeline-dot">
                        @if ($i < $currentIdx)
                            <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M3.5 8.5 6 11l6.5-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                            </svg>
                        @elseif ($i === $currentIdx)
                            <div class="pipeline-dot-inner"></div>
                        @endif
                    </div>
                    @if ($i < count($steps) - 1)
                        <div class="pipeline-line {{ $i < $currentIdx ? 'done' : '' }}"></div>
                    @endif
                    <div class="pipeline-label">{{ $step['label'] }}</div>
                    @if ($i === 0)
                        <div class="pipeline-time">{{ $task->created_at->format('d/m') }}</div>
                    @elseif ($i === 1 && $task->started_at)
                        <div class="pipeline-time">{{ $task->started_at->format('d/m') }}</div>
                    @elseif ($i === 2 && $task->ready_at)
                        <div class="pipeline-time">{{ $task->ready_at->format('d/m') }}</div>
                    @elseif ($i === 3 && $task->done_at)
                        <div class="pipeline-time">{{ $task->done_at->format('d/m') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ── Transition ───────────────────────────────────────────────────── --}}
        @php
            $pendingNonBugChildren = $task->children->where('type', '!=', 'bug')->whereNotIn('status', ['done'])->count();
        @endphp

        @if ($nonBugChildren > 0)
            {{-- Có con không phải bug: trạng thái tự động quản lý --}}
            <div class="auto-status-info">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-.75 3.5h1.5v5h-1.5v-5zm0 6.5h1.5v1.5h-1.5V11z"/>
                </svg>
                Trạng thái tự động cập nhật từ task con.
                @if ($pendingNonBugChildren > 0)
                    Còn <strong>{{ $pendingNonBugChildren }}</strong> task con chưa xong.
                @else
                    <span style="color:var(--green)">Tất cả task con đã hoàn thành.</span>
                @endif
            </div>
        @else
            {{-- Chỉ có bug hoặc không có con: cho phép chuyển thủ công --}}
            <div class="transition-bar">
                <form method="POST" action="{{ route('projects.tasks.transition', [$project, $task]) }}"
                      style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;width:100%">
                    @csrf
                    <div style="flex-shrink:0">
                        <label style="font-size:11px;color:var(--text-3);display:block;margin-bottom:4px">Chuyển sang</label>
                        <select name="status" class="form-control" style="width:200px">
                            @foreach (\App\Models\Task::STATUS_LABELS as $val => $label)
                                @if ($val !== $task->status)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:1;min-width:180px">
                        <label style="font-size:11px;color:var(--text-3);display:block;margin-bottom:4px">Ghi chú (tuỳ chọn)</label>
                        <input type="text" name="note" class="form-control" placeholder="Lý do, ghi chú...">
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>
            @error('transition')
                <div class="alert alert-danger" style="margin-top:8px">{{ $message }}</div>
            @enderror
        @endif
    </div>

    {{-- ── Inline edit form ────────────────────────────────────────────────── --}}
    <div id="editForm" style="display:none">
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span class="card-title">Chỉnh sửa Task</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit()">Huỷ</button>
            </div>
            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label">Tiêu đề <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description) }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select name="priority" class="form-control">
                            @foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $v => $l)
                                <option value="{{ $v }}" {{ $task->priority === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Developer</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">— Chưa phân công —</option>
                            @foreach ($members as $dev)
                                <option value="{{ $dev->id }}"
                                    {{ old('assigned_to', $task->assigned_to) == $dev->id ? 'selected' : '' }}>
                                    {{ $dev->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input type="date" name="start_date" class="form-control"
                            value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ngày kết thúc (Deadline)</label>
                        <input type="date" name="due_date" class="form-control"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thời gian ước tính (giờ)</label>
                        <input type="number" name="estimated_hours" class="form-control" step="0.5" min="0.5" max="999"
                            value="{{ old('estimated_hours', $task->estimated_hours) }}" placeholder="VD: 8, 16">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    {{-- ── Flash messages ───────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="alert alert-success" style="margin-bottom:12px">{!! session('success') !!}</div>
    @endif
    @error('child_error')
        <div class="alert alert-danger" style="margin-bottom:12px">{{ $message }}</div>
    @enderror
    @error('child_transition')
        <div class="alert alert-danger" style="margin-bottom:12px">{{ $message }}</div>
    @enderror

    {{-- ── Tabs: Tasks con / Lịch sử ─────────────────────────────────────── --}}
    @php
        $childCount    = $task->children->count();
        $historyCount  = $task->histories->count();
        $bugCount      = $task->children->where('type', 'bug')->count();
    @endphp

    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('children', this)">
            Tasks con
            <span class="tab-count {{ $task->children->whereNotIn('status',['done'])->count() > 0 ? 'yellow' : '' }}">
                {{ $childCount }}
            </span>
        </button>
        @if ($bugCount > 0)
        <button class="tab-btn" onclick="switchTab('bugs-only', this)">
            Bugs
            <span class="tab-count red">{{ $bugCount }}</span>
        </button>
        @endif
        <button class="tab-btn" onclick="switchTab('history', this)">
            Lịch sử
            <span class="tab-count">{{ $historyCount }}</span>
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- CHILDREN tab                                                         --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div id="tab-children" class="tab-panel card">

        {{-- Form thêm task con: bất kỳ thành viên (không giới hạn tầng) --}}
        <div class="tab-panel-header">
            <button class="btn btn-ghost btn-sm" onclick="toggleChildForm()">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:13px;height:13px">
                    <path d="M8 2v12M2 8h12"/>
                </svg>
                Thêm task
            </button>
        </div>

        <div id="childForm" style="display:none;padding:16px;border:1px solid var(--border);border-radius:6px;margin-bottom:12px;background:var(--bg-0)">
            <form method="POST" action="{{ route('projects.tasks.children.store', [$project, $task]) }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="grid-column:1/-1;margin:0">
                        <label class="form-label">Tiêu đề <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="200"
                            placeholder="VD: Làm API thanh toán, Fix bug login timeout...">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Loại</label>
                        <select name="type" class="form-control">
                            @foreach (\App\Models\Task::TYPE_LABELS as $val => $label)
                                <option value="{{ $val }}"
                                    {{ $val === 'subtask' ? 'selected' : '' }}
                                    {{ $val === 'bug' && $task->status !== 'ready_to_test' ? 'disabled' : '' }}>
                                    {{ $label }}{{ $val === 'bug' && $task->status !== 'ready_to_test' ? ' (chỉ tạo được khi Ready to Test)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Giao cho</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">— Chưa giao —</option>
                            @foreach ($members as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ngày kết thúc</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Thời gian ước tính (giờ)</label>
                        <input type="number" name="estimated_hours" class="form-control"
                            step="0.5" min="0.5" max="999" placeholder="VD: 4, 8, 16">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;margin:0">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="2"
                            placeholder="Mô tả yêu cầu, điều kiện chấp nhận..."></textarea>
                    </div>
                </div>
                <div style="margin-top:14px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary btn-sm">Thêm task</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleChildForm()">Huỷ</button>
                </div>
            </form>
        </div>

        @forelse ($task->children as $child)
            <div class="child-row type-border-{{ $child->type }}">
                <div class="child-left">
                    <span class="type-chip-sm type-{{ $child->type }}">{{ $child->typeLabel() }}</span>
                    <span class="child-code">{{ $child->code }}</span>
                    <a href="{{ route('projects.tasks.show', [$project, $child]) }}"
                       class="child-title {{ $child->status === 'done' ? 'done-text' : '' }}">{{ $child->title }}</a>
                    @if ($child->assignee)
                        <span class="child-assignee">{{ $child->assignee->full_name }}</span>
                    @endif
                    @if ($child->start_date)
                        <span class="child-meta">{{ $child->start_date->format('d/m') }}</span>
                    @endif
                    @if ($child->due_date)
                        <span class="child-meta {{ $child->due_date->isPast() && $child->status !== 'done' ? 'overdue' : '' }}">
                            → {{ $child->due_date->format('d/m') }}
                        </span>
                    @endif
                    @if ($child->estimated_hours)
                        <span class="child-meta">{{ $child->estimated_hours }}h</span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                    <span class="status-pill status-{{ $child->status }}">{{ $child->statusLabel() }}</span>
                </div>
            </div>
        @empty
            <div style="padding:32px;text-align:center;color:var(--text-3);font-size:13px">
                Chưa có task con nào. Thêm subtask, bug, hay task nghiên cứu nếu cần.
            </div>
        @endforelse
    </div>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- BUGS ONLY tab (chỉ hiện khi có bug)                                 --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    @if ($bugCount > 0)
    <div id="tab-bugs-only" class="tab-panel card" style="display:none">
        @foreach ($task->children->where('type', 'bug') as $bug)
            <div class="child-row type-border-bug">
                <div class="child-left">
                    <span class="type-chip-sm type-bug">Bug</span>
                    <span class="child-code">{{ $bug->code }}</span>
                    <a href="{{ route('projects.tasks.show', [$project, $bug]) }}"
                       class="child-title {{ $bug->status === 'done' ? 'done-text' : '' }}">{{ $bug->title }}</a>
                    @if ($bug->assignee)
                        <span class="child-assignee">{{ $bug->assignee->full_name }}</span>
                    @endif
                    <span style="font-size:11px;color:var(--text-3)">
                        · {{ $bug->creator->full_name }} · {{ $bug->created_at->diffForHumans() }}
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                    <span class="status-pill status-{{ $bug->status }}">{{ $bug->statusLabel() }}</span>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- HISTORY tab                                                          --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div id="tab-history" class="tab-panel card" style="display:none">
        @forelse ($task->histories as $h)
            <div class="history-row">
                <div class="history-dot"></div>
                <div class="history-body">
                    <div class="history-action">
                        @if ($h->from_status)
                            <span class="status-pill status-{{ $h->from_status }}" style="font-size:10px">
                                {{ \App\Models\Task::STATUS_LABELS[$h->from_status] ?? $h->from_status }}
                            </span>
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="color:var(--text-3)">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                            </svg>
                        @endif
                        <span class="status-pill status-{{ $h->to_status }}" style="font-size:10px">
                            {{ \App\Models\Task::STATUS_LABELS[$h->to_status] ?? $h->to_status }}
                        </span>
                    </div>
                    @if ($h->note)
                        <div class="history-note">{{ $h->note }}</div>
                    @endif
                    <div class="history-meta">
                        <strong>{{ $h->actor->full_name }}</strong> · {{ $h->created_at->format('d/m/Y H:i') }}
                        ({{ $h->created_at->diffForHumans() }})
                    </div>
                </div>
            </div>
        @empty
            <div style="padding:32px;text-align:center;color:var(--text-3);font-size:13px">Chưa có lịch sử.</div>
        @endforelse
    </div>


@endsection

@push('styles')
<style>
    /* Progress % buttons */
    .pct-btn {
        font-family: var(--font-mono); font-size: 11px; font-weight: 600;
        padding: 3px 8px; border-radius: 4px; cursor: pointer;
        border: 1px solid var(--border); background: var(--bg-2); color: var(--text-3);
        transition: all .12s;
    }
    .pct-btn:hover    { border-color: var(--accent); color: var(--accent); background: var(--accent-glow); }
    .pct-btn-active   { border-color: var(--accent); color: var(--accent); background: var(--accent-glow); }

    /* Progress bar */
    .task-info-row {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .info-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; color: var(--text-3);
        background: var(--bg-2); border: 1px solid var(--border);
        border-radius: 4px; padding: 3px 8px;
    }
    .info-badge.info-badge-overdue { color: var(--red); border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.08); }

    .progress-section { margin-bottom: 16px; }
    .progress-label {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 12px; color: var(--text-3); margin-bottom: 6px;
    }
    .progress-pct { font-family: var(--font-mono); font-weight: 700; color: var(--accent); }
    .progress-pct.progress-done { color: var(--green); }
    .progress-bar-track {
        height: 6px; background: var(--bg-2); border-radius: 3px; overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%; background: var(--accent); border-radius: 3px; transition: width .4s ease;
    }
    .progress-bar-fill.progress-done { background: var(--green); }

    .child-meta {
        font-size: 11px; color: var(--text-3); white-space: nowrap;
    }
    .child-meta.overdue { color: var(--red); }

    /* Task header */
    .task-header-card {
        background: var(--bg-1);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .task-meta-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .task-code-lg  { font-family:var(--font-mono); font-size:12px; color:var(--text-3); }
    .task-title-lg { font-family:var(--font-mono); font-size:20px; font-weight:700; margin-bottom:8px; }
    .task-desc-lg  { font-size:13.5px; color:var(--text-2); line-height:1.7; margin-bottom:16px; }

    /* Type chips */
    .type-chip {
        font-family: var(--font-mono);
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .type-chip.type-task     { background:rgba(59,130,246,.15);  color:var(--blue); }
    .type-chip.type-subtask  { background:rgba(100,116,139,.15); color:var(--text-2); }
    .type-chip.type-bug      { background:rgba(239,68,68,.12);   color:var(--red); }
    .type-chip.type-research { background:rgba(168,85,247,.12);  color:#a855f7; }
    .type-chip.type-fix      { background:rgba(249,115,22,.12);  color:var(--accent); }
    .type-chip.type-test     { background:rgba(34,197,94,.12);   color:var(--green); }

    .type-chip-sm {
        font-family: var(--font-mono);
        font-size: 9px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .type-chip-sm.type-task     { background:rgba(59,130,246,.15);  color:var(--blue); }
    .type-chip-sm.type-subtask  { background:rgba(100,116,139,.15); color:var(--text-2); }
    .type-chip-sm.type-bug      { background:rgba(239,68,68,.12);   color:var(--red); }
    .type-chip-sm.type-research { background:rgba(168,85,247,.12);  color:#a855f7; }
    .type-chip-sm.type-fix      { background:rgba(249,115,22,.12);  color:var(--accent); }
    .type-chip-sm.type-test     { background:rgba(34,197,94,.12);   color:var(--green); }

    /* Priority chips */
    .priority-chip {
        font-family:var(--font-mono); font-size:10px; font-weight:700;
        padding:2px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:.06em;
    }
    .priority-chip.priority-low      { background:rgba(100,116,139,.15); color:var(--text-3); }
    .priority-chip.priority-medium   { background:rgba(59,130,246,.15);  color:var(--blue); }
    .priority-chip.priority-high     { background:rgba(234,179,8,.12);   color:var(--yellow); }
    .priority-chip.priority-critical { background:rgba(239,68,68,.12);   color:var(--red); }

    .assignee-chip { display:flex; align-items:center; gap:4px; font-size:12px; color:var(--text-2); }

    /* Pipeline */
    .pipeline { display:flex; align-items:flex-start; gap:48px; margin:20px 0 16px; }
    .pipeline-step { display:flex; flex-direction:column; align-items:center; position:relative; }
    .pipeline-dot {
        width:22px; height:22px; border-radius:50%;
        border:2px solid var(--border); background:var(--bg-0);
        display:grid; place-items:center; z-index:1; flex-shrink:0;
    }
    .pipeline-step.done   .pipeline-dot { background:var(--green);  border-color:var(--green); }
    .pipeline-step.active .pipeline-dot { border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-glow); }
    .pipeline-dot-inner { width:8px; height:8px; border-radius:50%; background:var(--accent); }
    .pipeline-line { height:2px; width:80px; background:var(--border); margin:10px 0; position:absolute; left:22px; top:10px; }
    .pipeline-line.done { background:var(--green); }
    .pipeline-label { font-size:11px; font-family:var(--font-mono); color:var(--text-3); margin-top:8px; white-space:nowrap; text-align:center; width:90px; }
    .pipeline-step.active .pipeline-label { color:var(--accent); }
    .pipeline-step.done   .pipeline-label { color:var(--green); }
    .pipeline-time { font-size:10px; color:var(--text-3); }

    .transition-bar { display:flex; gap:10px; margin-top:16px; flex-wrap:wrap; }

    .auto-status-info {
        display:flex; align-items:center; gap:8px; padding:9px 14px;
        border-radius:6px; font-size:12.5px; margin-top:12px;
        background:rgba(59,130,246,.06); border:1px solid rgba(59,130,246,.18); color:var(--blue);
    }

    .info-strip {
        display:flex; align-items:center; gap:8px; padding:9px 14px;
        border-radius:6px; font-size:13px; margin-top:12px;
        background:rgba(234,179,8,.08); border:1px solid rgba(234,179,8,.2); color:var(--yellow);
    }

    /* Tabs */
    .tab-bar { display:flex; gap:0; border-bottom:1px solid var(--border); margin-bottom:0; }
    .tab-btn {
        background:none; border:none; border-bottom:2px solid transparent;
        padding:10px 18px; font-size:13px; font-weight:500; color:var(--text-3);
        cursor:pointer; display:flex; align-items:center; gap:6px;
        transition:all .15s; margin-bottom:-1px; font-family:var(--font-body);
    }
    .tab-btn:hover  { color:var(--text-2); }
    .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
    .tab-count {
        font-family:var(--font-mono); font-size:11px; font-weight:700;
        background:var(--bg-2); padding:1px 6px; border-radius:10px;
    }
    .tab-count.red    { background:rgba(239,68,68,.15);  color:var(--red); }
    .tab-count.yellow { background:rgba(234,179,8,.15);  color:var(--yellow); }
    .tab-panel { border-top:none; border-radius:0 0 8px 8px; }
    .tab-panel-header {
        padding:14px 0; margin-bottom:8px;
        border-bottom:1px solid var(--border); display:flex; gap:8px;
    }

    /* Child task rows */
    .child-row {
        display:flex; align-items:center; justify-content:space-between;
        gap:12px; padding:10px 4px 10px 12px;
        border-bottom:1px solid var(--border);
        border-left:3px solid transparent;
    }
    .child-row:last-child { border-bottom:none; }
    .child-row.type-border-task     { border-left-color:var(--blue); }
    .child-row.type-border-subtask  { border-left-color:var(--text-3); }
    .child-row.type-border-bug      { border-left-color:var(--red); }
    .child-row.type-border-research { border-left-color:#a855f7; }
    .child-row.type-border-fix      { border-left-color:var(--accent); }
    .child-row.type-border-test     { border-left-color:var(--green); }

    .child-left { display:flex; align-items:center; gap:8px; min-width:0; flex:1; }
    .child-code     { font-family:var(--font-mono); font-size:11px; color:var(--text-3); white-space:nowrap; }
    .child-title    { font-size:13.5px; }
    .child-title.done-text { text-decoration:line-through; color:var(--text-3); }
    .child-assignee { font-size:11px; color:var(--text-3); white-space:nowrap; }

    /* History */
    .history-row   { display:flex; gap:12px; padding:12px 0; }
    .history-dot   { width:10px; height:10px; border-radius:50%; background:var(--border-lit); flex-shrink:0; margin-top:4px; }
    .history-action { display:flex; align-items:center; gap:8px; margin-bottom:4px; }
    .history-note  { font-size:13px; color:var(--text-2); margin-bottom:4px; }
    .history-meta  { font-size:12px; color:var(--text-3); }

    .alert { padding:10px 14px; border-radius:6px; font-size:13px; }
    .alert-success { background:rgba(34,197,94,.1);  border:1px solid rgba(34,197,94,.25); color:var(--green); }
    .alert-danger  { background:rgba(239,68,68,.1);  border:1px solid rgba(239,68,68,.25);  color:var(--red); }
</style>
@endpush

@push('scripts')
<script>
    function switchTab(name, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(`tab-${name}`).style.display = 'block';
        btn.classList.add('active');
    }

    function toggleEdit() {
        const f = document.getElementById('editForm');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }

    function toggleChildForm() {
        const f = document.getElementById('childForm');
        if (f) f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endpush
