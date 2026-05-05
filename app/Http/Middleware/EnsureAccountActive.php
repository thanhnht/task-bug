<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kiểm tra tài khoản có bị Admin vô hiệu hoá không.
 * Tự động logout nếu tài khoản bị khoá sau khi đã đăng nhập.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login')
                             ->withErrors(['username' => 'Tài khoản của bạn đã bị vô hiệu hoá. Vui lòng liên hệ Admin.']);
        }

        return $next($request);
    }
}
