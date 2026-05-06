@extends('layouts.app')

@section('title', $story->code . ' — ' . $story->title)

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <a href="{{ route('projects.show', $project) }}">{{ $project->code }}</a>
    <span class="sep">/</span>
    <span class="current">{{ $story->code }}</span>
@endsection

@section('topbar-actions')
    @if ($role === 'pm' || Auth::user()->isAdmin())
        <button class="btn btn-ghost" onclick="toggleEdit()">
            <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px">
                <path
                    d="M11.5 2.5 13 4l-8 8-2 .5.5-2 8-8zm1-1a1 1 0 0 1 .7.3l1 1a1 1 0 0 1 0 1.4l-9 9-3 .8.8-3 9-9A1 1 0 0 1 12.5 1.5z" />
            </svg>
            Chỉnh sửa
        </button>
    @endif
@endsection

@section('content')

    {{-- ── Story header + pipeline ─────────────────────────────────────────── --}}
    <div class="story-header-card">

        <div class="story-meta-top">
            <span class="story-code-lg">{{ $story->code }}</span>
            <span class="priority-chip priority-{{ $story->priority }}">{{ $story->priorityLabel() }}</span>
            @if ($story->developer)
                <span class="assignee-chip">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                    </svg>
                    {{ $story->developer->full_name }}
                </span>
            @endif
            <span class="assignee-chip" style="margin-left:auto;color:var(--text-3)">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z" />
                </svg>
                {{ $story->creator->full_name }}
            </span>
        </div>

        <h1 class="story-title-lg">{{ $story->title }}</h1>

        @if ($story->description)
            <p class="story-desc-lg">{{ $story->description }}</p>
        @endif

        {{-- ── Status pipeline ──────────────────────────────────────────────── --}}
        <div class="pipeline">
            @php
                $steps = [
                    ['key' => 'todo', 'label' => 'To Do'],
                    ['key' => 'in_progress', 'label' => 'In Progress'],
                    ['key' => 'ready_to_review', 'label' => 'Ready to Review'],
                    ['key' => 'done', 'label' => 'Done'],
                ];
                $order = array_column($steps, 'key');
                $currentIdx = array_search($story->status, $order);
            @endphp

            @foreach ($steps as $i => $step)
                <div class="pipeline-step {{ $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'active' : 'pending') }}">
                    <div class="pipeline-dot">
                        @if ($i < $currentIdx)
                            <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M3.5 8.5 6 11l6.5-6.5" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" fill="none" />
                            </svg>
                        @elseif($i === $currentIdx)
                            <div class="pipeline-dot-inner"></div>
                        @endif
                    </div>
                    @if ($i < count($steps) - 1)
                        <div class="pipeline-line {{ $i < $currentIdx ? 'done' : '' }}"></div>
                    @endif
                    <div class="pipeline-label">{{ $step['label'] }}</div>
                    @if ($i === 0 && $story->started_at)
                        <div class="pipeline-time">{{ $story->created_at->format('d/m') }}</div>
                    @elseif($i === 1 && $story->started_at)
                        <div class="pipeline-time">{{ $story->started_at->format('d/m') }}</div>
                    @elseif($i === 2 && $story->ready_at)
                        <div class="pipeline-time">{{ $story->ready_at->format('d/m') }}</div>
                    @elseif($i === 3 && $story->done_at)
                        <div class="pipeline-time">{{ $story->done_at->format('d/m') }}</div>
                    @endif
                </div>
            @endforeach
        </div>
@php
    var_dump($transitions)
@endphp
        {{-- ── Transition buttons ───────────────────────────────────────────── --}}
        @if (count($transitions) > 0)
            <div class="transition-bar">
                @foreach ($transitions as $t)
                    <form method="POST" action="{{ route('projects.stories.transition', [$project, $story]) }}"
                        id="form-trans-{{ $t['status'] }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $t['status'] }}">
                        <input type="hidden" name="note" id="note-{{ $t['status'] }}" value="">
                    </form>

                    <button type="button"
                        class="btn {{ $t['status'] === 'done' ? 'btn-primary' : ($t['status'] === 'in_progress' && $story->status === 'ready_to_review' ? 'btn-danger' : 'btn-ghost') }}"
                        onclick="doTransition('{{ $t['status'] }}', '{{ $t['label'] }}')">
                        {{ $t['label'] }}
                    </button>
                @endforeach
            </div>

            @error('transition')
                <div class="alert alert-danger" style="margin-top:12px">
                    <svg viewBox="0 0 16 16" fill="currentColor" style="width:15px;height:15px">
                        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        @endif

        {{-- Validation warnings --}}
        @if ($story->status === 'in_progress')
            @php $pending = $story->pendingSubtasksCount(); @endphp
            @if ($pending > 0)
                <div class="info-strip">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 1L1 14h14L8 1zm-1 8V6h2v3H7zm0 2h2v2H7v-2z" />
                    </svg>
                    Còn <strong>{{ $pending }} Subtask</strong> chưa hoàn thành — không thể gửi Review.
                </div>
            @endif
        @endif
        @if ($story->status === 'ready_to_review')
            @php $openBugs = $story->openBugsCount(); @endphp
            @if ($openBugs > 0)
                <div class="info-strip danger">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z" />
                    </svg>
                    Còn <strong>{{ $openBugs }} Bug</strong> chưa đóng — PM chưa thể xác nhận Done.
                </div>
            @endif
        @endif
    </div>

    {{-- ── Inline edit form (hidden by default) ────────────────────────────── --}}
    <div id="editForm" style="display:none">
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span class="card-title">Chỉnh sửa Story</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit()">Huỷ</button>
            </div>
            <form method="POST" action="{{ route('projects.stories.update', [$project, $story]) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label">Tiêu đề <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $story->title) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $story->description) }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select name="priority" class="form-control">
                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $v => $l)
                                <option value="{{ $v }}" {{ $story->priority === $v ? 'selected' : '' }}>
                                    {{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Developer</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">— Chưa phân công —</option>

                            @foreach ($developers as $dev)
                                <option value="{{ $dev->id }}"
                                    {{ old('assigned_to', $story->assigned_to) == $dev->id ? 'selected' : '' }}>
                                    {{ $dev->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    {{-- ── Tabs: Subtasks / Bugs / History ────────────────────────────────── --}}
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('subtasks', this)">
            Subtasks
            {{-- <span class="tab-count">{{ $story->subtasks->count() }}</span> --}}
            <span class="tab-count">123</span>
        </button>
        <button class="tab-btn" onclick="switchTab('bugs', this)">
            Bugs
            {{-- @php $openBugs = $story->bugs->where('status', '!=', 'closed')->count(); @endphp --}}
            {{-- <span class="tab-count {{ $openBugs > 0 ? 'red' : '' }}">{{ $story->bugs->count() }}</span> --}}
            <span class="tab-count ">123</span>
        </button>
        <button class="tab-btn" onclick="switchTab('history', this)">
            Lịch sử
            {{-- <span class="tab-count">{{ $story->histories->count() }}</span> --}}
            <span class="tab-count">123</span>
        </button>
    </div>

    {{-- SUBTASKS tab --}}
    <div id="tab-subtasks" class="tab-panel card">

        @if ($role === 'pm' || $role === 'developer' || Auth::user()->isAdmin())
            <div class="tab-panel-header">
                <form method="POST" action="" style="display:flex;gap:8px;flex:1">
                    {{-- <form method="POST" action="{{ route('projects.stories.subtasks.store', [$project, $story]) }}" style="display:flex;gap:8px;flex:1"> --}}
                    @csrf
                    <input type="text" name="title" class="form-control" placeholder="Tiêu đề subtask mới..."
                        style="flex:1" required>
                    <select name="assigned_to" class="form-control" style="width:160px">
                        <option value="">Chọn Dev</option>
                        @foreach ($developers as $dev)
                            <option value="{{ $dev->id }}">{{ $dev->full_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">+ Thêm</button>
                </form>
            </div>
        @endif

        @forelse($story->subtasks ?? [] as $sub)
            <div class="subtask-row">
                <div class="subtask-left">
                    <span class="subtask-code">{{ $sub->code }}</span>
                    <span
                        class="subtask-title {{ $sub->status === 'done' ? 'done-text' : '' }}">{{ $sub->title }}</span>
                    @if ($sub->assignee)
                        <span class="subtask-assignee">{{ $sub->assignee->full_name }}</span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span
                        class="status-pill status-{{ $sub->status }}">{{ \App\Models\Subtask::STATUS_LABELS[$sub->status] ?? $sub->status }}</span>

                    {{-- Transition buttons for subtask --}}
                    @if ($sub->status !== 'done')
                        @php
                            $nextSub = [
                                'todo' => 'in_progress',
                                'in_progress' => 'ready_to_review',
                                'ready_to_review' => 'done',
                            ];
                            $next = $nextSub[$sub->status] ?? null;
                        @endphp
                        @if ($next && ($role === 'developer' || $role === 'pm' || Auth::user()->isAdmin()))
                            <form method="POST"
                                action="{{ route('projects.stories.subtasks.transition', [$project, $story, $sub]) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $next }}">
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    {{ ['in_progress' => 'Bắt đầu', 'ready_to_review' => 'Gửi Review', 'done' => 'Hoàn thành'][$next] ?? $next }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div style="padding:32px;text-align:center;color:var(--text-3);font-size:13px">Chưa có Subtask nào.</div>
        @endforelse
    </div>

    {{-- BUGS tab --}}
    <div id="tab-bugs" class="tab-panel card" style="display:none">

        @if ($role === 'tester' || Auth::user()->isAdmin())
            <div class="tab-panel-header">
                <button class="btn btn-primary btn-sm" onclick="toggleBugForm()">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round">
                        <path d="M8 2v12M2 8h12" />
                    </svg>
                    Tạo Bug mới
                </button>
            </div>

            <div id="bugForm"
                style="display:none;padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:8px">
                <form method="POST" action="">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Tiêu đề Bug <span class="required">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                placeholder="Mô tả ngắn lỗi...">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Mức độ nghiêm trọng</label>
                            <select name="severity" class="form-control">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gán cho Developer</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">— Auto (không gán) —</option>
                            @if ($story->developer)
                                <option value="{{ $story->developer->id }}" selected>{{ $story->developer->full_name }}
                                    (Developer của Story)</option>
                            @endif
                            @foreach ($developers as $dev)
                                @if (!$story->developer || $dev->id !== $story->developer->id)
                                    <option value="{{ $dev->id }}">{{ $dev->full_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bước tái hiện</label>
                        <textarea name="steps_to_reproduce" class="form-control" rows="3"
                            placeholder="1. Vào trang..&#10;2. Click...&#10;3. Kết quả thực tế vs mong đợi..."></textarea>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary btn-sm">Lưu Bug</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBugForm()">Huỷ</button>
                    </div>
                </form>
            </div>
        @endif

        @forelse($story->bugs ?? [] as $bug)
            <div class="bug-row severity-{{ $bug->severity }}">
                <div class="bug-left">
                    <div class="bug-severity-bar"></div>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="bug-code">{{ $bug->code }}</span>
                            <span class="severity-chip severity-{{ $bug->severity }}">{{ $bug->severityLabel() }}</span>
                            @if ($bug->retest_count > 0)
                                <span class="mini-badge" style="background:rgba(234,179,8,.1);color:var(--yellow)">
                                    ↺ Retest ×{{ $bug->retest_count }}
                                </span>
                            @endif
                        </div>
                        <div class="bug-title">{{ $bug->title }}</div>
                        <div style="font-size:11px;color:var(--text-3);margin-top:3px">
                            Báo cáo bởi {{ $bug->creator->full_name }} · {{ $bug->created_at->diffForHumans() }}
                            @if ($bug->assignee)
                                · Giao cho {{ $bug->assignee->full_name }}
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="bug-status-pill bug-status-{{ $bug->status }}">{{ $bug->statusLabel() }}</span>

                    {{-- Bug lifecycle transitions --}}
                    @php
                        $bugNext = [
                            'open' => ['in_progress' => 'Nhận xử lý', 'roles' => ['developer']],
                            'in_progress' => ['ready_to_review' => 'Đã sửa xong', 'roles' => ['developer']],
                            'ready_to_review' => null, // Tester action (close or reopen)
                        ];
                    @endphp

                    @if ($bug->status === 'in_progress' && ($role === 'developer' || Auth::user()->isAdmin()))
                        <form method="POST"
                            action="{{ route('projects.stories.bugs.transition', [$project, $story, $bug]) }}">
                            @csrf <input type="hidden" name="status" value="ready_to_review">
                            <button type="submit" class="btn btn-ghost btn-sm">Đã sửa xong</button>
                        </form>
                    @endif

                    @if ($bug->status === 'open' && ($role === 'developer' || Auth::user()->isAdmin()))
                        <form method="POST"
                            action="{{ route('projects.stories.bugs.transition', [$project, $story, $bug]) }}">
                            @csrf <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="btn btn-ghost btn-sm">Nhận xử lý</button>
                        </form>
                    @endif

                    @if ($bug->status === 'ready_to_review' && ($role === 'tester' || Auth::user()->isAdmin()))
                        <form method="POST"
                            action="{{ route('projects.stories.bugs.transition', [$project, $story, $bug]) }}"
                            style="display:inline">
                            @csrf <input type="hidden" name="status" value="closed">
                            <button type="submit" class="btn btn-success btn-sm">✓ Đóng Bug</button>
                        </form>
                        <form method="POST"
                            action="{{ route('projects.stories.bugs.transition', [$project, $story, $bug]) }}"
                            style="display:inline" onsubmit="return confirm('Retest thất bại? Bug sẽ được mở lại.')">
                            @csrf <input type="hidden" name="status" value="open">
                            <input type="hidden" name="note" value="Retest thất bại">
                            <button type="submit" class="btn btn-danger btn-sm">✗ Retest fail</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div style="padding:32px;text-align:center;color:var(--text-3);font-size:13px">Chưa có Bug nào được báo cáo.
            </div>
        @endforelse
    </div>

    {{-- HISTORY tab --}}
    <div id="tab-history" class="tab-panel card" style="display:none">
        @forelse($story->histories as $h)
            <div class="history-row">
                <div class="history-dot"></div>
                <div class="history-line"></div>
                <div class="history-body">
                    <div class="history-action">
                        @if ($h->from_status)
                            <span class="status-pill status-{{ $h->from_status }}"
                                style="font-size:10px">{{ \App\Models\Story::STATUS_LABELS[$h->from_status] ?? $h->from_status }}</span>
                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"
                                style="color:var(--text-3)">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" fill="none" />
                            </svg>
                        @endif
                        <span class="status-pill status-{{ $h->to_status }}"
                            style="font-size:10px">{{ \App\Models\Story::STATUS_LABELS[$h->to_status] ?? $h->to_status }}</span>
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

    {{-- ── Transition modal ────────────────────────────────────────────────── --}}
    <div id="transModal" class="modal-overlay" style="display:none" onclick="closeModal(event)">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">Xác nhận chuyển trạng thái</div>
            <div class="form-group" style="margin-top:16px">
                <label class="form-label">Ghi chú (tuỳ chọn)</label>
                <textarea id="transNote" class="form-control" rows="3" placeholder="Lý do, ghi chú thêm..."></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px">
                <button id="modalConfirmBtn" class="btn btn-primary" onclick="confirmTransition()">Xác nhận</button>
                <button class="btn btn-ghost"
                    onclick="document.getElementById('transModal').style.display='none'">Huỷ</button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .story-header-card {
            background: var(--bg-1);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .story-meta-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .story-code-lg {
            font-family: var(--font-mono);
            font-size: 12px;
            color: var(--text-3);
        }

        .story-title-lg {
            font-family: var(--font-mono);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .story-desc-lg {
            font-size: 13.5px;
            color: var(--text-2);
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .priority-chip {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .priority-chip.priority-low {
            background: rgba(100, 116, 139, .15);
            color: var(--text-3);
        }

        .priority-chip.priority-medium {
            background: rgba(59, 130, 246, .15);
            color: var(--blue);
        }

        .priority-chip.priority-high {
            background: rgba(234, 179, 8, .12);
            color: var(--yellow);
        }

        .priority-chip.priority-critical {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
        }

        .assignee-chip {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: var(--text-2);
        }

        /* Pipeline */
        .pipeline {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin: 20px 0 16px;
        }

        .pipeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .pipeline-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--bg-0);
            display: grid;
            place-items: center;
            z-index: 1;
            flex-shrink: 0;
        }

        .pipeline-step.done .pipeline-dot {
            background: var(--green);
            border-color: var(--green);
        }

        .pipeline-step.active .pipeline-dot {
            background: var(--bg-0);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .pipeline-dot-inner {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
        }

        .pipeline-line {
            height: 2px;
            width: 80px;
            background: var(--border);
            margin: 10px 0;
            position: absolute;
            left: 22px;
            top: 10px;
        }

        .pipeline-line.done {
            background: var(--green);
        }

        .pipeline-label {
            font-size: 11px;
            font-family: var(--font-mono);
            color: var(--text-3);
            margin-top: 8px;
            white-space: nowrap;
            text-align: center;
            width: 90px;
        }

        .pipeline-step.active .pipeline-label {
            color: var(--accent);
        }

        .pipeline-step.done .pipeline-label {
            color: var(--green);
        }

        .pipeline-time {
            font-size: 10px;
            color: var(--text-3);
        }

        /* For spacing between steps */
        .pipeline {
            gap: 48px;
        }

        .transition-bar {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .info-strip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-top: 12px;
            background: rgba(234, 179, 8, .08);
            border: 1px solid rgba(234, 179, 8, .2);
            color: var(--yellow);
        }

        .info-strip.danger {
            background: rgba(239, 68, 68, .08);
            border-color: rgba(239, 68, 68, .2);
            color: var(--red);
        }

        /* Tabs */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
        }

        .tab-btn {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-3);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .15s;
            margin-bottom: -1px;
            font-family: var(--font-body);
        }

        .tab-btn:hover {
            color: var(--text-2);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .tab-count {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            background: var(--bg-2);
            padding: 1px 6px;
            border-radius: 10px;
        }

        .tab-count.red {
            background: rgba(239, 68, 68, .15);
            color: var(--red);
        }

        .tab-panel {
            border-top: none;
            border-radius: 0 0 8px 8px;
        }

        .tab-panel-header {
            padding: 14px 0 14px;
            margin-bottom: 8px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 8px;
        }

        /* Subtask rows */
        .subtask-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 4px;
            border-bottom: 1px solid var(--border);
        }

        .subtask-row:last-child {
            border-bottom: none;
        }

        .subtask-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .subtask-code {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-3);
            white-space: nowrap;
        }

        .subtask-title {
            font-size: 13.5px;
        }

        .subtask-title.done-text {
            text-decoration: line-through;
            color: var(--text-3);
        }

        .subtask-assignee {
            font-size: 11px;
            color: var(--text-3);
            white-space: nowrap;
        }

        /* Bug rows */
        .bug-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 4px 12px 14px;
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .bug-row:last-child {
            border-bottom: none;
        }

        .bug-left {
            display: flex;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .bug-severity-bar {
            position: absolute;
            left: 0;
            top: 12px;
            bottom: 12px;
            width: 3px;
            border-radius: 2px;
        }

        .bug-row.severity-low .bug-severity-bar {
            background: var(--text-3);
        }

        .bug-row.severity-medium .bug-severity-bar {
            background: var(--blue);
        }

        .bug-row.severity-high .bug-severity-bar {
            background: var(--yellow);
        }

        .bug-row.severity-critical .bug-severity-bar {
            background: var(--red);
        }

        .bug-code {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-3);
        }

        .bug-title {
            font-size: 13.5px;
            font-weight: 500;
        }

        .severity-chip {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .severity-chip.severity-low {
            background: rgba(100, 116, 139, .15);
            color: var(--text-3);
        }

        .severity-chip.severity-medium {
            background: rgba(59, 130, 246, .15);
            color: var(--blue);
        }

        .severity-chip.severity-high {
            background: rgba(234, 179, 8, .12);
            color: var(--yellow);
        }

        .severity-chip.severity-critical {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
        }

        .bug-status-pill {
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .bug-status-pill.bug-status-open {
            background: rgba(239, 68, 68, .12);
            color: var(--red);
        }

        .bug-status-pill.bug-status-in_progress {
            background: rgba(249, 115, 22, .15);
            color: var(--accent);
        }

        .bug-status-pill.bug-status-ready_to_review {
            background: rgba(234, 179, 8, .12);
            color: var(--yellow);
        }

        .bug-status-pill.bug-status-closed {
            background: rgba(34, 197, 94, .12);
            color: var(--green);
        }

        /* History */
        .history-row {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            position: relative;
        }

        .history-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--border-lit);
            flex-shrink: 0;
            margin-top: 4px;
        }

        .history-line {
            display: none;
        }

        .history-action {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .history-note {
            font-size: 13px;
            color: var(--text-2);
            margin-bottom: 4px;
        }

        .history-meta {
            font-size: 12px;
            color: var(--text-3);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            display: grid;
            place-items: center;
            z-index: 500;
        }

        .modal-box {
            background: var(--bg-2);
            border: 1px solid var(--border-lit);
            border-radius: 10px;
            padding: 24px;
            width: 420px;
            max-width: 90vw;
            box-shadow: 0 24px 64px rgba(0, 0, 0, .6);
        }

        .modal-title {
            font-family: var(--font-mono);
            font-size: 15px;
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let pendingStatus = null;

        function doTransition(status, label) {
            pendingStatus = status;
            document.getElementById('modalTitle').textContent = `Chuyển sang: ${label}`;
            document.getElementById('transNote').value = '';
            document.getElementById('transModal').style.display = 'grid';
        }

        function confirmTransition() {
            if (!pendingStatus) return;
            const note = document.getElementById('transNote').value;
            document.getElementById(`note-${pendingStatus}`).value = note;
            document.getElementById(`form-trans-${pendingStatus}`).submit();
        }

        function closeModal(e) {
            if (e.target.id === 'transModal') document.getElementById('transModal').style.display = 'none';
        }

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

        function toggleBugForm() {
            const f = document.getElementById('bugForm');
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        }
    </script>
@endpush
