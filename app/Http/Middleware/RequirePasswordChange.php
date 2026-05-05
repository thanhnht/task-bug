<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Chặn nhân viên dùng hệ thống nếu chưa đổi mật khẩu lần đầu.
 * Redirect về trang đổi mật khẩu.
 */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->is_first_login) {
            // Cho phép truy cập route đổi mật khẩu và logout
            if ($request->routeIs('auth.change-password') || $request->routeIs('auth.logout')) {
                return $next($request);
            }

            return redirect()->route('auth.change-password')
                             ->with('warning', 'Bạn phải đổi mật khẩu trước khi sử dụng hệ thống.');
        }

        return $next($request);
    }
}

