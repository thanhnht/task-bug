    @extends('layouts.app')

@section('title', $project->name)

@section('breadcrumb')
    <a href="{{ route('projects.index') }}">Dự án</a>
    <span class="sep">/</span>
    <span class="current">{{ $project->code }}</span>
@endsection

@section('topbar-actions')
    @if($role === 'pm' || Auth::user()->isAdmin())
        <a href="{{ route('projects.stories.create', $project) }}" class="btn btn-primary">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
            Tạo Story
        </a>
    @endif
    @if(Auth::user()->isAdmin() || $role === 'pm')
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-ghost">
            <svg viewBox="0 0 16 16" fill="currentColor" style="width:14px;height:14px"><path d="M11.5 2.5 13 4l-8 8-2 .5.5-2 8-8zm1-1a1 1 0 0 1 .7.3l1 1a1 1 0 0 1 0 1.4l-9 9-3 .8.8-3 9-9A1 1 0 0 1 12.5 1.5z"/></svg>
            Chỉnh sửa
        </a>
    @endif
@endsection

@section('content')

{{-- ── Project header ─────────────────────────────────────────────────── --}}
<div class="project-header">
    <div class="project-header-info">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
            <span class="mono-tag">{{ $project->code }}</span>
            <span class="badge badge-status-{{ $project->status }}">{{ $project->statusLabel() }}</span>
            @if($role && $role !== 'admin')
                <span class="role-tag role-{{ $role }}">{{ \App\Models\Project::ROLE_LABELS[$role] ?? $role }}</span>
            @elseif(Auth::user()->isAdmin())
                <span class="role-tag role-admin">Admin</span>
            @endif
        </div>
        <h1 class="project-title">{{ $project->name }}</h1>
        @if($project->description)
        <p class="project-description">{{ $project->description }}</p>
        @endif
        <div style="display:flex;gap:20px;margin-top:10px">
            @if($project->start_date)
            <div class="meta-item">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M3 1v2H1v12h14V3h-2V1h-2v2H5V1H3zm10 4v8H3V5h10z"/></svg>
                Bắt đầu: {{ $project->start_date->format('d/m/Y') }}
            </div>
            @endif
            @if($project->end_date)
            <div class="meta-item">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm1 3v4H5V7h3V4h1z"/></svg>
                Deadline: <strong style="color:{{ $project->end_date->isPast() ? 'var(--red)' : 'var(--text-1)' }}">{{ $project->end_date->format('d/m/Y') }}</strong>
            </div>
            @endif
            <div class="meta-item">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/></svg>
                Tạo bởi: {{ $project->creator->full_name }}
            </div>
        </div>
    </div>

    {{-- Progress ring --}}
    <div class="progress-ring-wrap">
        @php $pct = $project->progressPercent(); @endphp
        <svg class="progress-ring" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="32" fill="none" stroke="var(--border)" stroke-width="6"/>
            <circle cx="40" cy="40" r="32" fill="none"
                stroke="var(--accent)" stroke-width="6"
                stroke-linecap="round"
                stroke-dasharray="{{ round(2 * 3.14159 * 32, 1) }}"
                stroke-dashoffset="{{ round(2 * 3.14159 * 32 * (1 - $pct/100), 1) }}"
                transform="rotate(-90 40 40)"/>
            <text x="40" y="44" text-anchor="middle" font-family="monospace" font-size="14" font-weight="700" fill="var(--text-1)">{{ $pct }}%</text>
        </svg>
        <div class="progress-ring-label">Tiến độ</div>
    </div>
</div>

{{-- ── Stats strip ─────────────────────────────────────────────────────── --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-label">Tổng Story</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card" style="border-top-color:var(--text-3)">
        <div class="stat-label">To Do</div>
        <div class="stat-value" style="font-size:22px;color:var(--text-2)">{{ $stats['todo'] }}</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">In Progress</div>
        <div class="stat-value" style="font-size:22px">{{ $stats['progress'] }}</div>
    </div>
    <div class="stat-card" style="border-top-color:var(--yellow)">
        <div class="stat-label">Ready Review</div>
        <div class="stat-value" style="font-size:22px;color:var(--yellow)">{{ $stats['review'] }}</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Done</div>
        <div class="stat-value" style="font-size:22px">{{ $stats['done'] }}</div>
    </div>
</div>

<div class="detail-layout">

{{-- ── Danh sách Story ─────────────────────────────────────────────────── --}}
<div class="detail-main">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Stories</span>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                {{-- Status filter tabs --}}
                @foreach([''=>'Tất cả','todo'=>'To Do','in_progress'=>'In Progress','ready_to_review'=>'Review','done'=>'Done'] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $val]) }}"
                   class="filter-tab {{ request('status', '') == $val ? 'active' : '' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>

        @if($project->stories->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--text-3)">
            @if($role === 'pm' || Auth::user()->isAdmin())
                Chưa có Story nào. <a href="{{ route('projects.stories.create', $project) }}" style="color:var(--accent)">Tạo Story đầu tiên</a>
            @else
                Chưa có Story nào được tạo.
            @endif
        </div>
        @else
        <div class="story-list">
            @foreach($project->stories as $story)
            <a href="{{ route('projects.stories.show', [$project, $story]) }}" class="story-row">
                <div class="story-row-left">
                    <span class="story-code">{{ $story->code }}</span>
                    <div>
                        <div class="story-title">{{ $story->title }}</div>
                        <div class="story-meta">
                            @if($story->developer)
                                <span>
                                    <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                    {{ $story->developer->full_name }}
                                </span>
                            @else
                                <span style="color:var(--text-3)">Chưa phân công</span>
                            @endif
                            @if($story->updated_at)
                            <span>{{ $story->updated_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="story-row-right">
                    @if($story->open_bugs_count > 0)
                    <span class="mini-badge red">
                        <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm-1 4h2v5H7V5zm0 6h2v2H7v-2z"/></svg>
                        {{ $story->open_bugs_count }} bugs
                    </span>
                    @endif
                    <span class="priority-dot priority-{{ $story->priority }}" title="{{ $story->priorityLabel() }}"></span>
                    <span class="status-pill status-{{ $story->status }}">{{ $story->statusLabel() }}</span>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Sidebar: Thành viên ─────────────────────────────────────────────── --}}
<div class="detail-aside">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Thành viên ({{ $project->members->count() }})</span>
        </div>

        @foreach([['PM', 'pm', 'accent'], ['Developer', 'developer', 'blue'], ['Tester', 'tester', 'green']] as [$label, $roleKey, $color])
        @php $group = $project->members->filter(fn($m) => $m->pivot->role === $roleKey); @endphp
        @if($group->isNotEmpty())
        <div class="member-group">
            <div class="member-group-title" style="color:var(--{{ $color }})">{{ $label }}</div>
            @foreach($group as $member)
            <div class="member-row">
                <div class="member-avatar-sm" style="color:var(--{{ $color }})">
                    {{ strtoupper(substr($member->full_name, 0, 2)) }}
                </div>
                <div class="member-info">
                    <div class="member-name">{{ $member->full_name }}</div>
                </div>
                @if(Auth::user()->isAdmin() || $role === 'pm')
                <div class="dropdown">
                    <button data-dropdown class="btn btn-ghost btn-sm" style="padding:3px 6px">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 3a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm0 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm0 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
                    </button>
                    <div class="dropdown-menu">
                        @foreach(['pm'=>'PM','developer'=>'Developer','tester'=>'Tester'] as $r => $rl)
                        <form method="POST" action="{{ route('projects.members.update-role', $project) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                            <input type="hidden" name="role" value="{{ $r }}">
                            <button type="submit" class="dropdown-item {{ $r === $roleKey ? 'active' : '' }}">
                                {{ $rl }}
                            </button>
                        </form>
                        @endforeach
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('projects.members.remove', $project) }}"
                              onsubmit="return confirm('Xoá {{ $member->full_name }} khỏi dự án?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                            <button type="submit" class="dropdown-item danger">Xoá khỏi dự án</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
        @endforeach

        {{-- Add member (PM / Admin) --}}
        @if(Auth::user()->isAdmin() || $role === 'pm')
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
            <form method="POST" action="{{ route('projects.members.add', $project) }}">
                @csrf
                <div style="display:flex;gap:6px;margin-bottom:8px">
                    <select name="user_id" class="form-control" style="flex:1;padding:6px 8px">
                        <option value="">Chọn nhân viên</option>
                        @foreach($employees as $emp)
                            @if(!$project->hasMember($emp))
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:6px">
                    <select name="role" class="form-control" style="flex:1;padding:6px 8px">
                        <option value="pm">PM</option>
                        <option value="developer" selected>Developer</option>
                        <option value="tester">Tester</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Thêm</button>
                </div>
                @error('user_id')<div class="form-error" style="margin-top:6px">{{ $message }}</div>@enderror
            </form>
        </div>
        @endif
    </div>
</div>

</div>{{-- /detail-layout --}}
@endsection

@push('styles')
<style>
    .project-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        margin-bottom: 24px;
        background: var(--bg-1);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 24px;
    }
    .project-title { font-family: var(--font-mono); font-size: 22px; font-weight: 700; color: var(--text-1); }
    .project-description { font-size: 13.5px; color: var(--text-2); margin-top: 8px; line-height: 1.6; }
    .meta-item { display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--text-2); }
    .mono-tag { font-family: var(--font-mono); font-size: 12px; color: var(--text-3); }

    .progress-ring-wrap { text-align: center; flex-shrink: 0; }
    .progress-ring { width: 80px; height: 80px; }
    .progress-ring-label { font-size: 11px; color: var(--text-3); font-family: var(--font-mono); margin-top: 4px; }

    .detail-layout { display: grid; grid-template-columns: 1fr 260px; gap: 16px; align-items: start; }

    /* Filter tabs */
    .filter-tab {
        font-size: 12px;
        font-family: var(--font-mono);
        padding: 4px 10px;
        border-radius: 4px;
        color: var(--text-3);
        text-decoration: none;
        transition: all .12s;
        border: 1px solid transparent;
    }
    .filter-tab:hover  { color: var(--text-2); background: var(--bg-2); }
    .filter-tab.active { color: var(--accent); background: var(--accent-glow); border-color: var(--accent-dim); }

    /* Story list */
    .story-list { display: flex; flex-direction: column; }
    .story-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 4px;
        border-bottom: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: background .1s;
        border-radius: 4px;
        padding: 10px 8px;
    }
    .story-row:last-child { border-bottom: none; }
    .story-row:hover { background: var(--bg-2); }
    .story-row-left { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
    .story-code {
        font-family: var(--font-mono);
        font-size: 11px;
        color: var(--text-3);
        white-space: nowrap;
        margin-top: 2px;
    }
    .story-title { font-size: 13.5px; font-weight: 500; color: var(--text-1); }
    .story-meta { font-size: 12px; color: var(--text-3); margin-top: 3px; display: flex; gap: 12px; }
    .story-meta span { display: flex; align-items: center; gap: 3px; }
    .story-row-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .mini-badge {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 11px; font-family: var(--font-mono); padding: 2px 6px; border-radius: 3px;
    }
    .mini-badge.red { background: rgba(239,68,68,.1); color: var(--red); }

    .priority-dot {
        width: 8px; height: 8px; border-radius: 50%;
    }
    .priority-dot.priority-low      { background: var(--text-3); }
    .priority-dot.priority-medium   { background: var(--blue); }
    .priority-dot.priority-high     { background: var(--yellow); }
    .priority-dot.priority-critical { background: var(--red); }

    .status-pill {
        font-size: 11px; font-family: var(--font-mono); font-weight: 700;
        letter-spacing: .04em; padding: 3px 8px; border-radius: 4px; white-space: nowrap;
    }
    .status-pill.status-todo            { background: var(--bg-3); color: var(--text-3); }
    .status-pill.status-in_progress     { background: rgba(249,115,22,.15); color: var(--accent); }
    .status-pill.status-ready_to_review { background: rgba(234,179,8,.12); color: var(--yellow); }
    .status-pill.status-done            { background: rgba(34,197,94,.12); color: var(--green); }

    /* Members sidebar */
    .member-group { margin-bottom: 16px; }
    .member-group-title {
        font-family: var(--font-mono); font-size: 10px; text-transform: uppercase;
        letter-spacing: .1em; margin-bottom: 8px;
    }
    .member-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .member-avatar-sm {
        width: 28px; height: 28px;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: 50%;
        display: grid; place-items: center;
        font-family: var(--font-mono);
        font-size: 10px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .member-info { flex: 1; min-width: 0; }
    .member-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Role tag */
    .role-tag { font-family: var(--font-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; padding: 2px 7px; border-radius: 3px; }
    .role-tag.role-pm        { background: rgba(249,115,22,.15); color: var(--accent); }
    .role-tag.role-developer { background: rgba(59,130,246,.15);  color: var(--blue); }
    .role-tag.role-tester    { background: rgba(34,197,94,.15);   color: var(--green); }
    .role-tag.role-admin     { background: rgba(239,68,68,.12);   color: var(--red); }

    .badge-status-active    { background: rgba(34,197,94,.15);  color: var(--green); font-family: var(--font-mono); font-size: 11px; font-weight:700; padding: 2px 8px; border-radius: 4px; }
    .badge-status-on_hold   { background: rgba(234,179,8,.12);  color: var(--yellow); font-family: var(--font-mono); font-size: 11px; font-weight:700; padding: 2px 8px; border-radius: 4px; }
    .badge-status-completed { background: rgba(59,130,246,.15); color: var(--blue); font-family: var(--font-mono); font-size: 11px; font-weight:700; padding: 2px 8px; border-radius: 4px; }
    .badge-status-archived  { background: rgba(100,116,139,.15);color: var(--text-3); font-family: var(--font-mono); font-size: 11px; font-weight:700; padding: 2px 8px; border-radius: 4px; }

    @media (max-width: 900px) {
        .detail-layout { grid-template-columns: 1fr; }
        .project-header { flex-direction: column; }
    }
</style>
@endpush
