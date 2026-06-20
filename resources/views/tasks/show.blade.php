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
    @if ($role === 'pm' || $role === 'developer' || Auth::user()->isAdmin() || $task->created_by === Auth::id())
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
            $nonBugChildren = $task->children->where('type', '!=', 'bug')->count();
            $doneChildren   = $task->children->where('status', 'done')->count();
        @endphp

        {{-- ── Status pipeline ──────────────────────────────────────────────── --}}
        <div class="pipeline">
            @php
                $steps = $task->isMainTask() ? [
                    ['key' => 'todo',            'label' => 'To Do'],
                    ['key' => 'in_progress',     'label' => 'In Progress'],
                    ['key' => 'ready_to_test',   'label' => 'Ready to Test'],
                    ['key' => 'review_approved', 'label' => 'Approved'],
                    ['key' => 'done',            'label' => 'Done'],
                ] : [
                    ['key' => 'todo',            'label' => 'To Do'],
                    ['key' => 'in_progress',     'label' => 'In Progress'],
                    ['key' => 'ready_to_test',   'label' => 'Ready to Test'],
                    ['key' => 'done',            'label' => 'Done'],
                ];
                $order      = array_column($steps, 'key');
                $currentIdx = array_search($task->status, $order);
                if ($currentIdx === false) $currentIdx = count($steps) - 1; // fallback
            @endphp

            @foreach ($steps as $i => $step)
                <div class="pipeline-step {{ ($i < $currentIdx || ($i === $currentIdx && $task->status === 'done')) ? 'done' : ($i === $currentIdx ? 'active' : 'pending') }}">
                    <div class="pipeline-dot">
                        @if ($i < $currentIdx || ($i === $currentIdx && $task->status === 'done'))
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
                    @elseif ($step['key'] === 'in_progress' && $task->started_at)
                        <div class="pipeline-time">{{ $task->started_at->format('d/m') }}</div>
                    @elseif ($step['key'] === 'ready_to_test' && $task->ready_at)
                        <div class="pipeline-time">{{ $task->ready_at->format('d/m') }}</div>
                    @elseif ($step['key'] === 'done' && $task->done_at)
                        <div class="pipeline-time">{{ $task->done_at->format('d/m') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ── Transition ───────────────────────────────────────────────────── --}}
        @php
            $pendingNonBugChildren = $task->children->where('type', '!=', 'bug')->whereNotIn('status', ['done'])->count();
        @endphp

        @if ($pendingNonBugChildren > 0)
            {{-- Còn con chưa xong: chỉ hiện banner thông tin --}}
            <div class="auto-status-info">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-.75 3.5h1.5v5h-1.5v-5zm0 6.5h1.5v1.5h-1.5V11z"/>
                </svg>
                Còn <strong>{{ $pendingNonBugChildren }}</strong> task con chưa hoàn thành.
            </div>
        @else
            {{-- Tất cả con done (hoặc không có con): hiện form chuyển trạng thái --}}
            @if ($nonBugChildren > 0)
                <div class="auto-status-info" style="background:rgba(22,163,74,.07);border-color:rgba(22,163,74,.25);color:var(--green);margin-bottom:10px">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M3.5 8.5 6 11l6.5-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                    </svg>
                    Tất cả task con đã hoàn thành.
                    @if ($role === 'tester' || Auth::user()->isAdmin())
                        Tester có thể phê duyệt <strong>Review Approved</strong>.
                    @elseif ($role === 'pm')
                        Chờ Tester phê duyệt Review Approved trước khi nghiệm thu Done.
                    @endif
                </div>
            @endif
            @if ($task->isMainTask() && $task->status === 'review_approved')
                <div class="auto-status-info" style="background:rgba(37,99,235,.07);border-color:rgba(37,99,235,.25);color:var(--blue);margin-bottom:10px">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM6.5 10.8 3.7 8l1-1 1.8 1.8 4-4 1 1-5 5z"/>
                    </svg>
                    Story đã được Tester phê duyệt.
                    @if ($role === 'pm' || Auth::user()->isAdmin())
                        PM có thể nghiệm thu <strong>Done</strong> nếu tất cả Bug đã đóng.
                    @endif
                </div>
            @endif
            <div class="transition-bar">
                <form method="POST" action="{{ route('projects.tasks.transition', [$project, $task]) }}"
                      style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;width:100%">
                    @csrf
                    <div style="flex-shrink:0">
                        <label class="tbar-label">Chuyển sang</label>
                        <select name="status" id="statusSelect" class="form-control" style="width:190px"
                                onchange="onStatusChange(this.value)">
                            @foreach (\App\Models\Task::STATUS_LABELS as $val => $label)
                                @if ($val !== $task->status)
                                    {{-- Tester dùng nút Pass thay cho review_approved trong dropdown --}}
                                    @if ($val === 'review_approved' && $role === 'tester' && $task->status === 'ready_to_test')
                                        @continue
                                    @endif
                                    @if ($val === 'review_approved' && $role !== 'tester' && !Auth::user()->isAdmin())
                                        @continue
                                    @endif
                                    @if ($val === 'done' && $role !== 'pm' && $role !== 'tester' && !Auth::user()->isAdmin())
                                        @continue
                                    @endif
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- Giao Tester (hiện khi chọn ready_to_test, mọi role đều thấy) --}}
                    @if ($testers->isNotEmpty())
                    <div id="testerField" style="display:none;flex-direction:column;flex-shrink:0">
                        <label class="tbar-label">Giao Tester</label>
                        <select name="assigned_to" id="testerSelect" disabled
                                class="form-control" style="width:175px">
                            <option value="">— Chưa giao —</option>
                            @foreach ($testers as $t)
                                <option value="{{ $t->id }}"
                                    {{ $task->assigned_to == $t->id ? 'selected' : '' }}>
                                    {{ $t->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif


                    <div style="flex:1;min-width:160px">
                        <label class="tbar-label">Ghi chú (tuỳ chọn)</label>
                        <input type="text" name="note" class="form-control" placeholder="Lý do, ghi chú...">
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </form>
            </div>
            @error('transition')
                <div class="alert alert-danger" style="margin-top:8px">{{ $message }}</div>
            @enderror

            {{-- ── Pass / Fail (Tester / Admin, khi task đang ở RTT, không phải bug) ── --}}
            @if ($task->status === 'ready_to_test' && $task->type === 'task' && ($role === 'tester' || Auth::user()->isAdmin()))
            <div class="pass-fail-bar">
                {{-- Pass --}}
                <form method="POST" action="{{ route('projects.tasks.transition', [$project, $task]) }}">
                    @csrf
                    <input type="hidden" name="status" value="review_approved">
                    <button type="submit" class="btn btn-success">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>
                        Pass — Đạt yêu cầu
                    </button>
                </form>

                {{-- Fail --}}
                <button type="button" id="btnFail" class="btn btn-danger">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                    Fail — Có lỗi
                </button>
            </div>

            @error('report_error')
                <div class="alert alert-danger" style="margin-top:8px">{{ $message }}</div>
            @enderror

            {{-- Modal Fail --}}
            <div id="modalFailBackdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
                <div class="modal-fail-card">
                    <div class="modal-fail-header">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="color:var(--red)"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        <span>Ghi nhận Bug mới</span>
                    </div>

                    <div class="modal-fail-info">
                        <div>
                            <span class="mf-label">Story</span>
                            <strong>{{ $task->code }} — {{ $task->title }}</strong>
                        </div>
                        <div>
                            <span class="mf-label">Developer</span>
                            <strong>{{ $devUser?->full_name ?? '(Chưa xác định)' }}</strong>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('projects.tasks.report-bug', [$project, $task]) }}">
                        @csrf
                        <div style="margin-bottom:10px">
                            <label class="tbar-label">Tên bug <span style="color:var(--red)">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="Mô tả lỗi ngắn gọn..." required
                                   value="{{ old('title') }}">
                        </div>
                        <div style="margin-bottom:14px">
                            <label class="tbar-label">Mô tả chi tiết (tuỳ chọn)</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Steps to reproduce, expected vs actual...">{{ old('description') }}</textarea>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end">
                            <button type="button" id="btnCloseModal" class="btn btn-ghost btn-sm">Huỷ</button>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"/></svg>
                                Tạo Bug
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        @endif
    </div>

    {{-- ── Production Bug warning ─────────────────────────────────────────── --}}
    @if ($task->is_production_bug && $task->linkedStory)
    @php
        $origDev    = $task->linkedStory->histories->where('to_status', 'ready_to_test')->last()?->actor;
        $origTester = $task->linkedStory->histories->whereIn('to_status', ['review_approved', 'done'])->last()?->actor;
        $doneEntry  = $task->linkedStory->histories->where('to_status', 'done')->last();
        $doneDate   = $doneEntry?->created_at;
    @endphp
    <div class="alert alert-danger" style="margin-bottom:16px;display:flex;gap:12px;align-items:flex-start">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink:0;margin-top:1px">
            <path d="M8 1L1 14h14L8 1zm-1 8V6h2v3H7zm0 2h2v2H7v-2z"/>
        </svg>
        <div>
            <strong>Production Bug — Lỗi lọt lưới từ tính năng đã nghiệm thu</strong>
            <div style="margin-top:6px;font-size:13px">
                Task gốc:
                <a href="{{ route('projects.tasks.show', [$project, $task->linkedStory]) }}"
                   style="color:inherit;font-family:var(--font-mono)">{{ $task->linkedStory->code }}</a>
                — {{ $task->linkedStory->title }}
            </div>
            <div style="margin-top:4px;font-size:13px;display:flex;gap:16px;flex-wrap:wrap">
                @if($origDev)
                    <span>
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                        Dev: <strong>{{ $origDev->full_name }}</strong>
                    </span>
                @endif
                @if($origTester)
                    <span>
                        <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/></svg>
                        Tester: <strong>{{ $origTester->full_name }}</strong>
                    </span>
                @endif
                @if($doneDate)
                    <span>Done ngày: <strong>{{ $doneDate->format('d/m/Y') }}</strong></span>
                @endif
                <span style="color:var(--text-3)">KPI đã bị trừ -5 điểm mỗi người</span>
            </div>
        </div>
    </div>
    @endif

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
                        <label class="form-label">Phân công</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">— Chưa phân công —</option>
                            @foreach ($allMembers as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('assigned_to', $task->assigned_to) == $m->id ? 'selected' : '' }}>
                                    {{ $m->full_name }}
                                    ({{ \App\Models\Project::ROLE_LABELS[$m->pivot->role] ?? '' }})
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
        <button class="tab-btn" onclick="switchTab('comments', this)">
            Bình luận
            @if($task->comments->count() > 0)
            <span class="tab-count">{{ $task->comments->count() }}</span>
            @endif
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

        <div id="childForm" style="display:none;padding:16px;border:1px solid var(--border);border-radius:6px;margin-bottom:12px;background:var(--bg-2)">
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
                        <select name="type" id="childType" class="form-control" onchange="onChildTypeChange(this.value)">
                            @foreach (\App\Models\Task::TYPE_LABELS as $val => $label)
                                <option value="{{ $val }}"
                                    {{ $val === 'subtask' ? 'selected' : '' }}
                                    {{ $val === 'bug' && $task->status !== 'ready_to_test' ? 'disabled' : '' }}>
                                    {{ $label }}{{ $val === 'bug' && $task->status !== 'ready_to_test' ? ' (chỉ khi Ready to Test)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label" id="childAssignLabel">Giao cho</label>
                        <select name="assigned_to" id="childAssign" class="form-control">
                            <option value="">— Chưa giao —</option>
                            @foreach ($allMembers as $m)
                                <option value="{{ $m->id }}"
                                    data-role="{{ $m->pivot->role }}">
                                    {{ $m->full_name }}
                                    ({{ \App\Models\Project::ROLE_LABELS[$m->pivot->role] ?? $m->pivot->role }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Ngày kết thúc
                            <span id="bugSlaHint" style="display:none;font-size:10px;color:var(--text-3);font-weight:400">
                                (tự động từ SLA nếu để trống)
                            </span>
                        </label>
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

                    {{-- Production Bug fields (chỉ PM / Tester, chỉ khi type = bug) --}}
                    @if(in_array($role, ['pm', 'tester']) || Auth::user()->isAdmin())
                    <div id="prodBugSection" class="form-group" style="grid-column:1/-1;margin:0;display:none;
                        padding:12px;background:rgba(220,38,38,.04);border:1px solid rgba(220,38,38,.2);border-radius:6px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px">
                            <input type="checkbox" name="is_production_bug" id="isProdBug" value="1"
                                onchange="toggleLinkedStory(this.checked)" style="width:14px;height:14px">
                            <span style="font-size:13px;font-weight:500;color:var(--red)">
                                Đây là Production Bug (lỗi lọt ra môi trường thực)
                            </span>
                        </label>
                        <div id="linkedStoryWrap" style="display:none">
                            <label class="form-label" style="font-size:12px">
                                Story / Tính năng gốc phát sinh lỗi <span class="required">*</span>
                            </label>
                            <select name="linked_story_id" id="linkedStoryId" class="form-control">
                                <option value="">— Chọn story đã Done —</option>
                                @forelse ($doneStories as $s)
                                    <option value="{{ $s->id }}">{{ $s->code }} — {{ Str::limit($s->title, 60) }}</option>
                                @empty
                                    <option disabled>Chưa có story nào Done trong dự án</option>
                                @endforelse
                            </select>
                            <div style="font-size:11px;color:var(--red);margin-top:5px">
                                ⚠ Khi lưu, hệ thống sẽ tự động trừ -5 điểm KPI của Dev và Tester từ story gốc.
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div style="margin-top:14px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary btn-sm">Thêm task</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleChildForm()">Huỷ</button>
                </div>
            </form>
        </div>

        @forelse ($task->children as $child)
            <div class="child-row type-border-{{ $child->type }} {{ $child->due_date && $child->due_date->isPast() && $child->status !== 'done' ? 'child-row--overdue' : '' }}"
                 onclick="window.location='{{ route('projects.tasks.show', [$project, $child]) }}'"
                 style="cursor:pointer">
                <div class="child-left">
                    <span class="type-chip-sm type-{{ $child->type }}">{{ $child->typeLabel() }}</span>
                    <span class="child-code">{{ $child->code }}</span>
                    <span class="child-title {{ $child->status === 'done' ? 'done-text' : '' }}">{{ $child->title }}</span>
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
            <div class="child-row type-border-bug {{ $bug->due_date && $bug->due_date->isPast() && $bug->status !== 'done' ? 'child-row--overdue' : '' }}">
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

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- COMMENTS tab                                                         --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div id="tab-comments" class="tab-panel card" style="display:none">

        {{-- Danh sách comment --}}
        <div id="commentList">
        @forelse ($task->comments as $c)
            <div class="comment-item" id="comment-{{ $c->id }}">
                <div class="comment-avatar">
                    <svg viewBox="0 0 16 16" fill="currentColor" width="14" height="14"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-.3-5 5-5 5 5 5 5H3z"/></svg>
                </div>
                <div class="comment-body">
                    <div class="comment-meta">
                        <strong>{{ $c->user->full_name }}</strong>
                        <span class="comment-time">{{ $c->created_at->format('d/m/Y H:i') }}</span>
                        @if(Auth::id() === $c->user_id || Auth::user()->isAdmin())
                        <form method="POST" action="{{ route('projects.tasks.comments.destroy', [$project, $task, $c]) }}" style="margin-left:auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-del-comment" onclick="return confirm('Xoá bình luận này?')"
                                    title="Xoá">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M6 2h4v1H6V2zm-2 2h8l-1 10H5L4 4zm3 2v6H6V6h1zm3 0v6h-1V6h1z"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                    <div class="comment-content ql-editor" style="padding:0">
                        {!! $c->content !!}
                    </div>
                    @if($c->attachments->isNotEmpty())
                    <div class="comment-attachments">
                        @foreach($c->attachments as $att)
                            @php $isImage = str_starts_with($att->mime_type ?? '', 'image/'); @endphp
                            @if($isImage)
                                <a href="{{ $att->url() }}" target="_blank" class="att-img-wrap">
                                    <img src="{{ $att->url() }}" alt="{{ $att->original_name }}" class="att-img-preview">
                                </a>
                            @else
                                <a href="{{ $att->url() }}" target="_blank" class="att-file">
                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M4 1h6l4 4v10H4V1zm6 0v4h4"/></svg>
                                    {{ $att->original_name }}
                                    <span class="att-size">{{ $att->formattedSize() }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="padding:32px;text-align:center;color:var(--text-3);font-size:13px">
                Chưa có bình luận nào. Hãy là người đầu tiên!
            </div>
        @endforelse
        </div>

        {{-- Form thêm comment --}}
        <div class="comment-form-wrap">
            @if($errors->has('content'))
                <div class="alert alert-danger" style="margin-bottom:8px">{{ $errors->first('content') }}</div>
            @endif
            <form id="commentForm" method="POST"
                  action="{{ route('projects.tasks.comments.store', [$project, $task]) }}"
                  enctype="multipart/form-data">
                @csrf
                <div id="quillEditor" style="min-height:120px"></div>
                <input type="hidden" name="content" id="commentContent">

                {{-- File attachments --}}
                <div class="comment-attach-row">
                    <label class="btn btn-ghost btn-sm" style="cursor:pointer">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M4.5 3A2.5 2.5 0 0 1 7 .5h5A2.5 2.5 0 0 1 14.5 3v7a4.5 4.5 0 0 1-9 0V5a.5.5 0 0 1 1 0v5a3.5 3.5 0 0 0 7 0V3A1.5 1.5 0 0 0 12 1.5H7A1.5 1.5 0 0 0 5.5 3v7a.5.5 0 0 1-1 0V3z"/></svg>
                        Đính kèm file
                        <input type="file" name="attachments[]" id="attachmentInput" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
                               style="display:none" onchange="showFileList(this)">
                    </label>
                    <div id="fileList" style="font-size:12px;color:var(--text-3);display:flex;gap:6px;flex-wrap:wrap"></div>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto"
                            onclick="syncContent()">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M1 8l6-6 1.5 1.5L4 8l4.5 4.5L7 14 1 8zm8 0l6-6 1.5 1.5L12 8l4.5 4.5L15 14 9 8z" style="display:none"/><path d="M2 13.5L13.5 2l1 1L3 14.5l-1-1z"/><path d="M13.5 2l1 1-4 4-1-1 4-4z"/><path d="M2 13.5l1 1 4-4-1-1-4 4z"/></svg>
                        Gửi bình luận
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<style>
    /* Quill theme override */
    .ql-toolbar.ql-snow { border-color: var(--border); border-radius: 6px 6px 0 0; background: var(--bg-2); }
    .ql-container.ql-snow { border-color: var(--border); border-radius: 0 0 6px 6px; background: var(--bg-1); }
    .ql-editor { min-height: 120px; font-size: 14px; color: var(--text-1); }
    .ql-editor img { max-width: 100%; border-radius: 4px; margin: 4px 0; }

    /* Comment list */
    .comment-item {
        display: flex; gap: 12px; padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }
    .comment-item:last-child { border-bottom: none; }
    .comment-avatar {
        width: 30px; height: 30px; flex-shrink: 0;
        background: var(--bg-3); border-radius: 50%;
        display: grid; place-items: center; color: var(--accent);
        border: 1px solid var(--border-lit);
    }
    .comment-body { flex: 1; min-width: 0; }
    .comment-meta {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 6px; font-size: 13px;
    }
    .comment-time { font-size: 11px; color: var(--text-3); }
    .btn-del-comment {
        background: none; border: none; cursor: pointer;
        color: var(--text-3); padding: 2px 4px; border-radius: 3px;
    }
    .btn-del-comment:hover { color: var(--red); background: rgba(239,68,68,.08); }
    .comment-content { font-size: 13px; line-height: 1.6; }
    .comment-content p { margin: 0 0 6px; }

    /* Attachments */
    .comment-attachments { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .att-img-wrap { display: inline-block; }
    .att-img-preview { max-width: 180px; max-height: 120px; border-radius: 4px; border: 1px solid var(--border); cursor: zoom-in; }
    .att-file {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; color: var(--accent); text-decoration: none;
        background: var(--bg-3); border: 1px solid var(--border);
        border-radius: 4px; padding: 4px 8px;
    }
    .att-file:hover { background: var(--bg-2); }
    .att-size { color: var(--text-3); font-size: 11px; }

    /* Comment form */
    .comment-form-wrap {
        padding: 16px;
        border-top: 1px solid var(--border);
        background: var(--bg-2);
        border-radius: 0 0 8px 8px;
    }
    .comment-attach-row {
        display: flex; align-items: center; gap: 8px;
        margin-top: 10px; flex-wrap: wrap;
    }

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
    .info-badge.info-badge-overdue { color: var(--red); border-color: rgba(220,38,38,.25); background: rgba(220,38,38,.06); }

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
        box-shadow: var(--shadow-sm);
    }

    .task-meta-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .task-code-lg  { font-family:var(--font-mono); font-size:12px; color:var(--text-3); }
    .task-title-lg { font-size:20px; font-weight:700; margin-bottom:8px; color:var(--text-1); }
    .task-desc-lg  { font-size:14.5px; color:var(--text-2); line-height:1.75; margin-bottom:16px; }

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

    .type-chip.type-task     { background:rgba(37,99,235,.12);   color:var(--blue); }
    .type-chip.type-subtask  { background:rgba(100,116,139,.12); color:var(--text-2); }
    .type-chip.type-bug      { background:rgba(220,38,38,.10);   color:var(--red); }
    .type-chip.type-research { background:rgba(168,85,247,.10);  color:#7c3aed; }
    .type-chip.type-fix      { background:rgba(37,99,235,.10);  color:var(--accent); }
    .type-chip.type-test     { background:rgba(22,163,74,.10);   color:var(--green); }

    .type-chip-sm {
        font-family: var(--font-mono);
        font-size: 9px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .type-chip-sm.type-task     { background:rgba(37,99,235,.12);   color:var(--blue); }
    .type-chip-sm.type-subtask  { background:rgba(100,116,139,.12); color:var(--text-2); }
    .type-chip-sm.type-bug      { background:rgba(220,38,38,.10);   color:var(--red); }
    .type-chip-sm.type-research { background:rgba(168,85,247,.10);  color:#7c3aed; }
    .type-chip-sm.type-fix      { background:rgba(37,99,235,.10);  color:var(--accent); }
    .type-chip-sm.type-test     { background:rgba(22,163,74,.10);   color:var(--green); }

    /* Priority chips */
    .priority-chip {
        font-family:var(--font-mono); font-size:10px; font-weight:700;
        padding:2px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:.06em;
    }
    .priority-chip.priority-low      { background:rgba(100,116,139,.12); color:var(--text-2); }
    .priority-chip.priority-medium   { background:rgba(37,99,235,.12);   color:var(--blue); }
    .priority-chip.priority-high     { background:rgba(180,83,9,.10);    color:var(--yellow); }
    .priority-chip.priority-critical { background:rgba(220,38,38,.10);   color:var(--red); }

    .assignee-chip { display:flex; align-items:center; gap:4px; font-size:12px; color:var(--text-2); }

    /* Pipeline */
    .pipeline { display:flex; align-items:flex-start; gap:48px; margin:20px 0 16px; }
    .pipeline-step { display:flex; flex-direction:column; align-items:center; position:relative; }
    .pipeline-dot {
        width:22px; height:22px; border-radius:50%;
        border:2px solid var(--border); background:var(--bg-1);
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
    .tbar-label { font-size:11px; color:var(--text-3); display:block; margin-bottom:4px; font-family:var(--font-mono); text-transform:uppercase; letter-spacing:.06em; }

    .pass-fail-bar {
        display: flex;
        gap: 10px;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid var(--border);
        flex-wrap: wrap;
    }
    .pass-fail-bar .btn { font-size: 14px; padding: 8px 18px; gap: 6px; }

    .modal-fail-card {
        background: var(--bg-1);
        border: 1px solid var(--border-lit);
        border-radius: 10px;
        padding: 24px;
        width: 480px;
        max-width: calc(100vw - 32px);
        box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }
    .modal-fail-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 16px;
        color: var(--text-1);
    }
    .modal-fail-info {
        background: var(--bg-2);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 12px 14px;
        margin-bottom: 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 13px;
        color: var(--text-2);
    }
    .mf-label {
        font-family: var(--font-mono);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-3);
        margin-right: 6px;
    }

    .auto-status-info {
        display:flex; align-items:center; gap:8px; padding:9px 14px;
        border-radius:6px; font-size:12.5px; margin-top:12px;
        background:rgba(37,99,235,.06); border:1px solid rgba(37,99,235,.2); color:var(--blue);
    }

    .info-strip {
        display:flex; align-items:center; gap:8px; padding:9px 14px;
        border-radius:6px; font-size:13px; margin-top:12px;
        background:rgba(217,119,6,.06); border:1px solid rgba(217,119,6,.2); color:var(--yellow);
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
    .child-row.child-row--overdue { background: rgba(220,38,38,.04); }
    .child-row.type-border-task     { border-left-color:var(--blue); }
    .child-row.type-border-subtask  { border-left-color:var(--text-3); }
    .child-row.type-border-bug      { border-left-color:var(--red); }
    .child-row.type-border-research { border-left-color:#a855f7; }
    .child-row.type-border-fix      { border-left-color:var(--accent); }
    .child-row.type-border-test     { border-left-color:var(--green); }

    .child-left { display:flex; align-items:center; gap:8px; min-width:0; flex:1; }
    .child-code     { font-family:var(--font-mono); font-size:11px; color:var(--text-3); white-space:nowrap; }
    .child-title    { font-size:14px; }
    .child-title.done-text { text-decoration:line-through; color:var(--text-3); }
    .child-assignee { font-size:11px; color:var(--text-3); white-space:nowrap; }

    /* History */
    .history-row   { display:flex; gap:12px; padding:12px 0; }
    .history-dot   { width:10px; height:10px; border-radius:50%; background:var(--border-lit); flex-shrink:0; margin-top:4px; }
    .history-action { display:flex; align-items:center; gap:8px; margin-bottom:4px; }
    .history-note  { font-size:13px; color:var(--text-2); margin-bottom:4px; }
    .history-meta  { font-size:12px; color:var(--text-3); }

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
    // ── Quill rich-text editor for comments ──────────────────────────────
    let quill;
    document.addEventListener('DOMContentLoaded', function () {
        const editorEl = document.getElementById('quillEditor');
        if (!editorEl) return;

        quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Viết bình luận, mô tả lỗi, đính kèm ảnh...',
            modules: {
                toolbar: {
                    container: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        ['clean'],
                    ],
                    handlers: {
                        image: imageUploadHandler,
                    },
                },
            },
        });
    });

    function imageUploadHandler() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();
        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;
            const form = new FormData();
            form.append('image', file);
            form.append('_token', '{{ csrf_token() }}');
            try {
                const res = await fetch('{{ route('projects.tasks.comments.upload-image', [$project, $task]) }}', {
                    method: 'POST', body: form,
                });
                const data = await res.json();
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', data.url);
                quill.setSelection(range.index + 1);
            } catch (e) {
                alert('Upload ảnh thất bại. Vui lòng thử lại.');
            }
        };
    }

    function syncContent() {
        const input = document.getElementById('commentContent');
        if (quill && input) input.value = quill.root.innerHTML;
        return true;
    }

    function showFileList(input) {
        const list = document.getElementById('fileList');
        list.innerHTML = Array.from(input.files).map(f =>
            `<span style="background:var(--bg-3);border:1px solid var(--border);border-radius:3px;padding:2px 6px">${f.name}</span>`
        ).join('');
    }

    function onStatusChange(val) {
        const tf = document.getElementById('testerField');
        const ts = document.getElementById('testerSelect');
        if (tf && ts) {
            const show = val === 'ready_to_test';
            tf.style.display = show ? 'flex' : 'none';
            ts.disabled = !show;
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('statusSelect');
        if (sel) onStatusChange(sel.value);

        const btnFail    = document.getElementById('btnFail');
        const backdrop   = document.getElementById('modalFailBackdrop');
        const btnClose   = document.getElementById('btnCloseModal');
        if (btnFail && backdrop) {
            btnFail.addEventListener('click', () => {
                backdrop.style.display = 'flex';
            });
            btnClose.addEventListener('click', () => {
                backdrop.style.display = 'none';
            });
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) backdrop.style.display = 'none';
            });
        }
    });

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

    function onChildTypeChange(type) {
        const label  = document.getElementById('childAssignLabel');
        const select = document.getElementById('childAssign');
        if (!label || !select) return;

        const opts = [...select.options];
        const placeholder = opts.shift();
        const devs    = opts.filter(o => o.dataset.role === 'developer');
        const pms     = opts.filter(o => o.dataset.role === 'pm');
        const testers = opts.filter(o => o.dataset.role === 'tester');

        if (type === 'bug') {
            label.textContent = 'Giao cho Developer';
            select.innerHTML = '';
            [placeholder, ...devs, ...pms, ...testers].forEach(o => select.appendChild(o));
            if (!select.value && devs.length) select.value = devs[0].value;
        } else {
            label.textContent = 'Giao cho';
            select.innerHTML = '';
            [placeholder, ...pms, ...devs, ...testers].forEach(o => select.appendChild(o));
            select.value = '';
        }

        // Hiện/ẩn khu vực Production Bug
        const prodSec = document.getElementById('prodBugSection');
        const slaHint = document.getElementById('bugSlaHint');
        if (prodSec) prodSec.style.display = type === 'bug' ? 'block' : 'none';
        if (slaHint) slaHint.style.display = type === 'bug' ? 'inline' : 'none';
        // Reset checkbox khi đổi type
        const cb = document.getElementById('isProdBug');
        if (cb && type !== 'bug') { cb.checked = false; toggleLinkedStory(false); }
    }

    function toggleLinkedStory(checked) {
        const wrap = document.getElementById('linkedStoryWrap');
        const sel  = document.getElementById('linkedStoryId');
        if (!wrap) return;
        wrap.style.display = checked ? 'block' : 'none';
        if (sel) sel.required = checked;
        // Khi là production bug: bỏ disable trên option bug (cho phép tạo trên Done task)
        const bugOpt = [...document.getElementById('childType').options].find(o => o.value === 'bug');
        if (bugOpt && checked) bugOpt.disabled = false;
    }

    function toggleChildForm() {
        const f = document.getElementById('childForm');
        if (f) f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endpush
