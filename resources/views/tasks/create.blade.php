@extends('layouts.app')

@section('title', 'Tạo Task')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <a href="{{ route('projects.show', $project) }}">{{ $project->code }}</a>
    <span class="sep">/</span>
    <span class="current">Tạo Task</span>
@endsection

@section('content')
<div class="page-header">
    <h1>Tạo <span class="accent" id="pageTypeLabel">Task</span></h1>
    <p>Trong dự án <strong>{{ $project->name }}</strong></p>
</div>

<div style="max-width:700px">
<form method="POST" action="{{ route('projects.tasks.store', $project) }}">
@csrf

<div class="card">
    <div class="card-header">
        <span class="card-title">Thông tin</span>
        <span class="status-pill status-todo" style="font-size:11px">To Do</span>
    </div>

    {{-- Type selector --}}
    <div class="form-group">
        <label class="form-label">Loại <span class="required">*</span></label>
        <div class="type-selector">
            @if(in_array($role, ['pm', 'admin']) || Auth::user()->isAdmin())
            <label class="type-option" id="typeOptTask">
                <input type="radio" name="type" value="task" {{ old('type', 'task') === 'task' ? 'checked' : '' }} onchange="onTypeChange('task')">
                <span class="type-chip-xs type-task">Task</span>
                <span class="type-opt-desc">Tính năng / công việc mới</span>
            </label>
            @endif
            <label class="type-option" id="typeOptBug">
                <input type="radio" name="type" value="bug" {{ old('type') === 'bug' ? 'checked' : '' }} onchange="onTypeChange('bug')">
                <span class="type-chip-xs type-bug">Bug</span>
                <span class="type-opt-desc">Lỗi phát sinh cần sửa</span>
            </label>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="title">Tiêu đề <span class="required">*</span></label>
        <input type="text" id="title" name="title"
            class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
            value="{{ old('title') }}"
            placeholder="VD: Làm module thanh toán, KH báo không đăng nhập được..."
            autofocus>
        @error('title')<div class="form-error">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Mô tả / Yêu cầu chi tiết</label>
        <textarea id="description" name="description" class="form-control" rows="5"
            placeholder="Mô tả chi tiết yêu cầu, bối cảnh, điều kiện chấp nhận...">{{ old('description') }}</textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
            <label class="form-label" for="priority">Độ ưu tiên <span class="required">*</span></label>
            <select id="priority" name="priority" class="form-control">
                @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $val => $label)
                <option value="{{ $val }}" {{ old('priority', 'medium') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="assigned_to">Phân công</label>
            <select id="assigned_to" name="assigned_to" class="form-control">
                <option value="">— Chưa phân công —</option>
                @foreach($members as $m)
                <option value="{{ $m->id }}" {{ old('assigned_to') == $m->id ? 'selected' : '' }}>
                    {{ $m->full_name }} ({{ \App\Models\Project::ROLE_LABELS[$m->pivot->role] ?? $m->pivot->role }})
                </option>
                @endforeach
            </select>
            @error('assigned_to')<div class="form-error">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="start_date">Ngày bắt đầu</label>
            <input type="date" id="start_date" name="start_date" class="form-control"
                value="{{ old('start_date') }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="due_date">Ngày kết thúc (Deadline)</label>
            <input type="date" id="due_date" name="due_date" class="form-control"
                value="{{ old('due_date') }}">
        </div>

        <div class="form-group">
            <label class="form-label" for="estimated_hours">Thời gian ước tính (giờ)</label>
            <input type="number" id="estimated_hours" name="estimated_hours"
                class="form-control" value="{{ old('estimated_hours') }}"
                placeholder="VD: 8, 16, 4.5" step="0.5" min="0.5" max="999">
        </div>
    </div>

    <div class="priority-preview" id="priorityPreview"></div>

    {{-- Production Bug section (hiện khi type=bug, chỉ PM/Admin điền được) --}}
    @if(in_array($role, ['pm', 'admin']) || Auth::user()->isAdmin())
    <div id="prodBugSection" style="display:none;margin-top:4px;padding:14px;background:rgba(220,38,38,.04);border:1px solid rgba(220,38,38,.2);border-radius:6px">
        <div style="font-size:12px;font-weight:600;color:var(--red);margin-bottom:10px;display:flex;align-items:center;gap:6px">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1L1 14h14L8 1zm-1 8V6h2v3H7zm0 2h2v2H7v-2z"/></svg>
            Truy vết Production Bug
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" for="linked_task_id">Lỗi này phát sinh từ task nào trước đó?</label>
            <input type="text" id="linkedTaskSearch" class="form-control"
                   placeholder="Tìm theo mã hoặc tên task..."
                   style="margin-bottom:6px"
                   oninput="filterLinkedTasks(this.value)">
            <select id="linked_task_id" name="linked_task_id" class="form-control" size="5" style="height:auto">
                <option value="">— Không liên quan đến task cũ —</option>
                @foreach($allTasks as $t)
                <option value="{{ $t->id }}"
                    {{ old('linked_task_id') == $t->id ? 'selected' : '' }}
                    data-search="{{ strtolower($t->code . ' ' . $t->title) }}">
                    @if($t->status === 'done')[✓ DONE]@else[{{ strtoupper($t->status) }}]@endif
                    {{ $t->code }} — {{ Str::limit($t->title, 55) }}
                </option>
                @endforeach
            </select>
            <div style="font-size:11px;color:var(--text-3);margin-top:5px">Nếu chọn → hệ thống tự động trừ -5 KPI của Dev + Tester gốc của task đó.</div>
        </div>
    </div>
    @endif
</div>

<div style="display:flex;gap:10px;margin-top:16px">
    <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px">
            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/>
        </svg>
        Tạo Task
    </button>
    <a href="{{ route('projects.show', $project) }}" class="btn btn-ghost">Huỷ</a>
</div>

</form>
</div>
@endsection

@push('styles')
<style>
    .priority-preview {
        display:flex; align-items:center; gap:8px;
        padding:10px 12px; border-radius:6px;
        font-size:13px; transition:all .2s; margin-top:4px;
    }
    .priority-preview.low      { background:rgba(100,116,139,.1); color:var(--text-3); }
    .priority-preview.medium   { background:rgba(59,130,246,.08); color:var(--blue); }
    .priority-preview.high     { background:rgba(234,179,8,.08);  color:var(--yellow); }
    .priority-preview.critical { background:rgba(239,68,68,.08);  color:var(--red); }

    .type-selector { display:flex; gap:10px; flex-wrap:wrap; }
    .type-option {
        display:flex; align-items:center; gap:8px;
        padding:8px 14px; border:1px solid var(--border);
        border-radius:6px; cursor:pointer; transition:all .15s;
        background:var(--bg-1);
    }
    .type-option:has(input:checked) { border-color:var(--accent); background:var(--bg-3); }
    .type-option input[type=radio] { display:none; }
    .type-opt-desc { font-size:12px; color:var(--text-3); }
</style>
@endpush

@push('scripts')
<script>
    const priorityInfo = {
        low:      { label:'Low',      desc:'Có thể xử lý sau, không ảnh hưởng tiến độ chính.' },
        medium:   { label:'Medium',   desc:'Cần xử lý trong sprint hiện tại.' },
        high:     { label:'High',     desc:'Cần ưu tiên cao, ảnh hưởng đến release.' },
        critical: { label:'Critical', desc:'Chặn release. Phải xử lý ngay lập tức.' },
    };
    function updatePriorityPreview() {
        const val = document.getElementById('priority').value;
        const info = priorityInfo[val];
        const box = document.getElementById('priorityPreview');
        box.className = `priority-preview ${val}`;
        box.innerHTML = `<strong>${info.label}</strong> — ${info.desc}`;
    }
    document.getElementById('priority').addEventListener('change', updatePriorityPreview);
    updatePriorityPreview();

    function onTypeChange(type) {
        const label = document.getElementById('pageTypeLabel');
        const prodSection = document.getElementById('prodBugSection');
        const submitBtn = document.querySelector('button[type=submit]');
        if (label) label.textContent = type === 'bug' ? 'Bug' : 'Task';
        if (prodSection) prodSection.style.display = type === 'bug' ? 'block' : 'none';
        if (submitBtn) submitBtn.textContent = type === 'bug' ? 'Tạo Bug' : 'Tạo Task';
    }

    function filterLinkedTasks(q) {
        q = q.toLowerCase().trim();
        document.querySelectorAll('#linked_task_id option').forEach(opt => {
            opt.hidden = q !== '' && !opt.dataset.search?.includes(q);
        });
    }

    // Init on load (for old() restoration)
    document.addEventListener('DOMContentLoaded', () => {
        const checked = document.querySelector('input[name=type]:checked');
        if (checked) onTypeChange(checked.value);
    });
</script>
@endpush
