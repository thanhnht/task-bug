@extends('layouts.app')

@section('title', 'Tạo dự án mới')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <span class="current">Tạo mới</span>
@endsection

@section('content')
<div class="page-header">
    <h1>Tạo dự án <span class="accent">mới</span></h1>
    <p>Điền thông tin và phân công thành viên ngay khi khởi tạo.</p>
</div>

<form method="POST" action="{{ route('projects.store') }}" id="createProjectForm">
@csrf

<div class="create-layout">

    {{-- ── Cột trái: thông tin dự án ─────────────────────────────────── --}}
    <div class="create-main">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Thông tin dự án</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Tên dự án <span class="required">*</span></label>
                <input type="text" id="name" name="name"
                    class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                    value="{{ old('name') }}"
                    placeholder="VD: Hệ thống quản lý kho"
                    autofocus>
                @error('name')
                    <div class="form-error"><svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Mô tả</label>
                <textarea id="description" name="description" class="form-control" rows="4"
                    placeholder="Mô tả ngắn về mục tiêu và phạm vi dự án...">{{ old('description') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">Ngày bắt đầu</label>
                    <input type="date" id="start_date" name="start_date"
                        class="form-control {{ $errors->has('start_date') ? 'is-invalid' : '' }}"
                        value="{{ old('start_date') }}">
                    @error('start_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">Ngày kết thúc dự kiến</label>
                    <input type="date" id="end_date" name="end_date"
                        class="form-control {{ $errors->has('end_date') ? 'is-invalid' : '' }}"
                        value="{{ old('end_date') }}">
                    @error('end_date')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Cột phải: thành viên ───────────────────────────────────────── --}}
    <div class="create-aside">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Phân công thành viên</span>
                <span style="font-size:12px;color:var(--text-3)">Có thể thêm sau</span>
            </div>

            {{-- Member builder ─────────────────────────────────────────── --}}
            <div id="memberList"></div>

            {{-- Add member row --}}
            <div class="add-member-row">
                <select id="memberSelect" class="form-control" style="flex:1">
                    <option value="">— Chọn nhân viên —</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" data-name="{{ $emp->full_name }}">
                        {{ $emp->full_name }}
                    </option>
                    @endforeach
                </select>

                <select id="roleSelect" class="form-control" style="width:130px">
                    <option value="pm">PM</option>
                    <option value="developer">Developer</option>
                    <option value="tester">Tester</option>
                </select>

                <button type="button" class="btn btn-ghost" onclick="addMember()">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
                    Thêm
                </button>
            </div>

            {{-- Hidden inputs sẽ được thêm bởi JS --}}
            <div id="memberInputs"></div>
        </div>

        {{-- Permission summary card --}}
        <div class="card" style="margin-top:14px">
            <div class="card-header">
                <span class="card-title">Phân quyền vai trò</span>
            </div>
            <div class="perm-table">
                <div class="perm-row perm-header">
                    <span>Quyền</span>
                    <span style="color:var(--accent)">PM</span>
                    <span style="color:var(--blue)">Dev</span>
                    <span style="color:var(--green)">Tester</span>
                </div>
                @php
                $perms = [
                    ['Tạo Story',          true,  false, false],
                    ['Cập nhật Story',     true,  false, false],
                    ['Phân công Developer',true,  false, false],
                    ['Bắt đầu Story',      true,  true,  false],
                    ['Gửi Review',         false, true,  false],
                    ['Xác nhận Done',      true,  false, false],
                    ['Tạo Subtask',        true,  true,  false],
                    ['Cập nhật Subtask',   false, true,  false],
                    ['Tạo Bug',            false, false, true ],
                    ['Đóng Bug (Retest)',  false, false, true ],
                    ['Xem tất cả',         true,  true,  true ],
                ];
                @endphp
                @foreach($perms as $perm)
                <div class="perm-row">
                    <span>{{ $perm[0] }}</span>
                    <span>{!! $perm[1] ? '<svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" style="color:var(--green)"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>' : '<span style="color:var(--text-3)">—</span>' !!}</span>
                    <span>{!! $perm[2] ? '<svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" style="color:var(--green)"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>' : '<span style="color:var(--text-3)">—</span>' !!}</span>
                    <span>{!! $perm[3] ? '<svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" style="color:var(--green)"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>' : '<span style="color:var(--text-3)">—</span>' !!}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Actions --}}
<div class="form-actions">
    <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>
        Tạo dự án
    </button>
    <a href="{{ route('projects.index') }}" class="btn btn-ghost">Huỷ</a>
</div>

</form>
@endsection

@push('styles')
<style>
    .create-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 16px;
        align-items: start;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-actions { display: flex; gap: 10px; margin-top: 20px; }

    .add-member-row {
        display: flex;
        gap: 8px;
        align-items: center;
        padding-top: 14px;
        border-top: 1px solid var(--border);
        margin-top: 6px;
    }

    .member-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        background: var(--bg-2);
        border: 1px solid var(--border);
        border-radius: 6px;
        margin-bottom: 8px;
    }
    .member-avatar {
        width: 28px; height: 28px;
        background: var(--bg-3);
        border-radius: 50%;
        display: grid; place-items: center;
        font-family: var(--font-mono);
        font-size: 10px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .member-name { flex: 1; font-size: 13px; font-weight: 500; }
    .member-role {
        font-family: var(--font-mono);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 2px 7px;
        border-radius: 3px;
    }
    .member-role.pm        { background: rgba(37,99,235,.15); color: var(--accent); }
    .member-role.developer { background: rgba(59,130,246,.15);  color: var(--blue); }
    .member-role.tester    { background: rgba(34,197,94,.15);   color: var(--green); }

    .btn-remove-member {
        background: none; border: none; cursor: pointer;
        color: var(--text-3); padding: 2px;
        display: grid; place-items: center;
    }
    .btn-remove-member:hover { color: var(--red); }
    .btn-remove-member svg { width: 14px; height: 14px; }

    /* Permission table */
    .perm-table { font-size: 12px; }
    .perm-row {
        display: grid;
        grid-template-columns: 1fr 28px 28px 28px;
        align-items: center;
        gap: 4px;
        padding: 6px 0;
        border-bottom: 1px solid var(--border);
    }
    .perm-row:last-child { border-bottom: none; }
    .perm-header {
        font-family: var(--font-mono);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-3);
        margin-bottom: 4px;
    }
    .perm-row span:not(:first-child) { display: grid; place-items: center; }

    @media (max-width: 800px) {
        .create-layout { grid-template-columns: 1fr; }
        .form-row { grid-template-columns: 1fr; }
    }
    /* chữ bên trong */
input[type="date"]::-webkit-datetime-edit {
    color: var(--text-1);
}

/* icon calendar */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1); /* chuyển icon sang trắng cho hợp dark */
    opacity: 0.7;
    cursor: pointer;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}
</style>
@endpush

@push('scripts')
<script>
    const members = [];

    function addMember() {
        const sel  = document.getElementById('memberSelect');
        const role = document.getElementById('roleSelect').value;
        const uid  = sel.value;
        const name = sel.options[sel.selectedIndex]?.dataset.name;

        if (!uid) { alert('Vui lòng chọn nhân viên.'); return; }
        if (members.find(m => m.uid === uid)) { alert('Nhân viên này đã được thêm.'); return; }

        members.push({ uid, name, role });
        renderMembers();
        sel.value = '';
    }

    function removeMember(uid) {
        const idx = members.findIndex(m => m.uid === uid);
        if (idx > -1) members.splice(idx, 1);
        renderMembers();
    }

    const ROLE_COLORS = { pm: 'pm', developer: 'developer', tester: 'tester' };
    const ROLE_LABELS = { pm: 'PM', developer: 'Developer', tester: 'Tester' };

    function renderMembers() {
        const list   = document.getElementById('memberList');
        const inputs = document.getElementById('memberInputs');

        list.innerHTML = members.map((m, i) => `
            <div class="member-item">
                <div class="member-avatar" style="color:var(${m.role === 'pm' ? '--accent' : m.role === 'developer' ? '--blue' : '--green'})">
                    <svg viewBox="0 0 16 16" fill="currentColor" width="14" height="14"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-.3-5 5-5 5 5 5 5H3z"/></svg>
                </div>
                <div class="member-name">${m.name}</div>
                <span class="member-role ${ROLE_COLORS[m.role]}">${ROLE_LABELS[m.role]}</span>
                <button type="button" class="btn-remove-member" onclick="removeMember('${m.uid}')">
                    <svg viewBox="0 0 16 16" fill="currentColor"><path d="M4.5 4.5l7 7m0-7-7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>
                </button>
            </div>
        `).join('');

        inputs.innerHTML = members.map((m, i) => `
            <input type="hidden" name="members[${i}][user_id]" value="${m.uid}">
            <input type="hidden" name="members[${i}][role]"    value="${m.role}">
        `).join('');
    }
</script>
@endpush
