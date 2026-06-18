@extends('layouts.app')

@section('title', 'Quản lý tài khoản')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Admin</a>
    <span class="sep">/</span>
    <span class="current">Tài khoản nhân viên</span>
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
        Cấp tài khoản mới
    </a>
@endsection

@section('content')
<div class="page-header">
    <h1>
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" style="color:var(--accent)">
            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/>
        </svg>
        Quản lý tài <span class="accent">khoản</span>
    </h1>
    <p>Admin cấp và quản lý toàn bộ tài khoản nhân viên trong hệ thống.</p>
</div>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Tổng tài khoản</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-sub">nhân viên</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Đang hoạt động</div>
        <div class="stat-value">{{ $stats['active'] }}</div>
        <div class="stat-sub">tài khoản</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Chưa đổi pass</div>
        <div class="stat-value">{{ $stats['first_login'] }}</div>
        <div class="stat-sub">lần đầu đăng nhập</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Đang bị khoá</div>
        <div class="stat-value">{{ $stats['locked'] }}</div>
        <div class="stat-sub">tài khoản</div>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Danh sách nhân viên</span>
        <div style="display:flex;gap:8px;align-items:center">
            <input type="text" placeholder="Tìm kiếm..." class="form-control" style="width:220px;padding:6px 10px" id="searchInput" oninput="filterTable(this.value)">
        </div>
    </div>

    <div class="table-wrap">
        <table id="userTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Họ tên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Lần đầu</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $i => $user)
                <tr data-search="{{ strtolower($user->full_name . ' ' . $user->username . ' ' . $user->email) }}">
                    <td style="color:var(--text-3);font-family:var(--font-mono);font-size:12px">
                        {{ $users->firstItem() + $i }}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:30px;height:30px;background:var(--bg-3);border:1px solid var(--border-lit);border-radius:50%;display:grid;place-items:center;color:var(--blue);flex-shrink:0">
                                <svg viewBox="0 0 16 16" fill="currentColor" width="15" height="15"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-.3-5 5-5 5 5 5 5H3z"/></svg>
                            </div>
                            <span style="font-weight:500">{{ $user->full_name }}</span>
                        </div>
                    </td>
                    <td>
                        <code style="font-family:var(--font-mono);font-size:12px;color:var(--text-2);background:var(--bg-0);padding:2px 6px;border-radius:3px">
                            {{ $user->username }}
                        </code>
                    </td>
                    <td style="color:var(--text-2)">{{ $user->email }}</td>
                    <td>
                        @if(!$user->is_active)
                            <span class="badge badge-inactive">Vô hiệu</span>
                        @elseif($user->isLocked())
                            <span class="badge badge-locked">
                                <svg width="10" height="10" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a4 4 0 0 1 4 4v1h1v7H3V6h1V5a4 4 0 0 1 4-4z"/></svg>
                                Bị khoá
                            </span>
                        @else
                            <span class="badge badge-active">Hoạt động</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_first_login)
                            <span class="badge" style="background:rgba(37,99,235,.15);color:var(--accent)">
                                Chưa đổi pass
                            </span>
                        @else
                            <span style="color:var(--text-3);font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center">

                            {{-- Mở khoá nếu đang bị khoá --}}
                            @if($user->isLocked())
                                <form method="POST" action="{{ route('admin.users.unlock', $user) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Mở khoá tài khoản">
                                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M11 1a3 3 0 0 1 3 3v1h-2V4a1 1 0 0 0-2 0v1H3v8h10V6h-1V4a3 3 0 0 1 3-3zM7 9a1 1 0 1 1 2 0v2H7V9z"/></svg>
                                        Mở khoá
                                    </button>
                                </form>
                            @endif

                            {{-- Cấp lại mật khẩu --}}
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                                  onsubmit="return confirm('Cấp lại mật khẩu tạm thời cho {{ $user->full_name }}?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-ghost" title="Cấp lại mật khẩu">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M14 8A6 6 0 1 1 2 8H0l3-3 3 3H4a4 4 0 1 0 4-4V2a6 6 0 0 1 6 6z"/></svg>
                                    Cấp lại pass
                                </button>
                            </form>

                            {{-- Khoá / Kích hoạt --}}
                            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}"
                                  onsubmit="return confirm('{{ $user->is_active ? 'Vô hiệu hoá' : 'Kích hoạt' }} tài khoản {{ $user->full_name }}?')">
                                @csrf
                                @if($user->is_active)
                                    <button type="submit" class="btn btn-sm btn-danger" title="Vô hiệu hoá">
                                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM5 7h6v2H5V7z"/></svg>
                                        Vô hiệu
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm btn-success" title="Kích hoạt">
                                        <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.5 5.5-4 4-2-2 1-1 1 1 3-3 1 1z"/></svg>
                                        Kích hoạt
                                    </button>
                                @endif
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:var(--text-3)">
                        <svg width="32" height="32" viewBox="0 0 16 16" fill="currentColor" style="display:block;margin:0 auto 10px;opacity:.3">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3z"/>
                        </svg>
                        Chưa có nhân viên nào. <a href="{{ route('admin.users.create') }}" style="color:var(--accent)">Cấp tài khoản đầu tiên</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function filterTable(q) {
        q = q.toLowerCase();
        document.querySelectorAll('#userTable tbody tr[data-search]').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    }
</script>
@endpush
