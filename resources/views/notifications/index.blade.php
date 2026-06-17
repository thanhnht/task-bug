@extends('layouts.app')

@section('title', 'Thông báo')

@section('breadcrumb')
    <span class="current">Thông báo</span>
@endsection

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1>
            <svg viewBox="0 0 16 16" fill="currentColor" style="width:20px;height:20px;color:var(--accent)">
                <path d="M8 1a5 5 0 0 0-5 5v2.5l-1 1.5v1h12v-1l-1-1.5V6a5 5 0 0 0-5-5zm-1.5 11a1.5 1.5 0 0 0 3 0h-3z"/>
            </svg>
            Thông báo
        </h1>
        <p>Các thông báo hệ thống gửi đến bạn</p>
    </div>
    @if ($notifications->total() > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Đánh dấu tất cả đã đọc</button>
        </form>
    @endif
</div>

<div class="card" style="padding:0;overflow:hidden;">
    @forelse ($notifications as $notif)
        @php
            $isUnread = $notif->read_at === null;
            $iconColor = match($notif->type) {
                'bug_ready_to_test' => 'var(--yellow)',
                'review_approved'   => 'var(--blue)',
                'assigned'          => 'var(--green)',
                default             => 'var(--text-3)',
            };
            $iconPath = match($notif->type) {
                'bug_ready_to_test' => 'M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 2a5 5 0 1 1 0 10A5 5 0 0 1 8 3zm-1 4h2v4H7V7zm0 5h2v2H7v-2z',
                'review_approved'   => 'M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zM6.5 10.8 3.7 8l1-1 1.8 1.8 4-4 1 1-5 5z',
                'assigned'          => 'M8 1a3 3 0 1 1 0 6A3 3 0 0 1 8 1zm0 8c3.3 0 6 1.3 6 3v1H2v-1c0-1.7 2.7-3 6-3z',
                default             => 'M8 1a5 5 0 0 0-5 5v2.5l-1 1.5v1h12v-1l-1-1.5V6a5 5 0 0 0-5-5zm-1.5 11a1.5 1.5 0 0 0 3 0h-3z',
            };
        @endphp
        <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);
                    background:{{ $isUnread ? 'rgba(37,99,235,.04)' : 'transparent' }};
                    transition:background .1s;"
             onmouseenter="this.style.background='var(--bg-2)'"
             onmouseleave="this.style.background='{{ $isUnread ? 'rgba(37,99,235,.04)' : 'transparent' }}'">

            {{-- Icon --}}
            <div style="width:34px;height:34px;border-radius:8px;background:var(--bg-3);
                        display:flex;align-items:center;justify-content:flex-start;flex-shrink:0;
                        padding:9px;">
                <svg viewBox="0 0 16 16" fill="{{ $iconColor }}" style="width:16px;height:16px;">
                    <path d="{{ $iconPath }}"/>
                </svg>
            </div>

            {{-- Content --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                    <span style="font-weight:{{ $isUnread ? '600' : '400' }};color:var(--text-1);font-size:14px;">
                        {{ $notif->title }}
                    </span>
                    @if ($isUnread)
                        <span style="width:7px;height:7px;border-radius:50%;background:var(--accent);flex-shrink:0;display:inline-block;"></span>
                    @endif
                </div>
                @if ($notif->body)
                    <div style="font-size:13px;color:var(--text-2);margin-bottom:4px;">{{ $notif->body }}</div>
                @endif
                <div style="font-size:11px;color:var(--text-3);font-family:var(--font-mono);">
                    {{ $notif->created_at->diffForHumans() }}
                    @if ($notif->task)
                        · <span style="font-family:var(--font-mono)">{{ $notif->task->code }}</span>
                    @endif
                </div>
            </div>

            {{-- Action --}}
            @if ($notif->url)
                <a href="{{ $notif->url }}" class="btn btn-ghost btn-sm" style="flex-shrink:0;">Xem →</a>
            @endif
        </div>
    @empty
        <div style="text-align:center;padding:56px;color:var(--text-3);">
            <svg viewBox="0 0 16 16" fill="currentColor"
                 style="width:32px;height:32px;opacity:.25;margin-bottom:12px;display:block;margin-inline:auto">
                <path d="M8 1a5 5 0 0 0-5 5v2.5l-1 1.5v1h12v-1l-1-1.5V6a5 5 0 0 0-5-5zm-1.5 11a1.5 1.5 0 0 0 3 0h-3z"/>
            </svg>
            Không có thông báo nào.
        </div>
    @endforelse
</div>

@if ($notifications->hasPages())
    <div style="margin-top:16px;">{{ $notifications->links() }}</div>
@endif
@endsection
