<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Chỉ cho phép Admin truy cập.
 * Dùng cho tất cả route /admin/*
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }
        return $next($request);
    }
}
