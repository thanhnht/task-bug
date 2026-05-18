<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // =========================================================================
    // ĐĂNG NHẬP
    // =========================================================================

    /** Hiển thị form đăng nhập */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.login');
    }

    /** Xử lý đăng nhập */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // 1. Tìm user theo username
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withInput(['username' => $request->username])
                         ->withErrors(['username' => 'Tên đăng nhập không tồn tại.']);
        }

        // 2. Kiểm tra tài khoản bị Admin vô hiệu hoá
        if (!$user->isActive()) {
            return back()->withInput(['username' => $request->username])
                         ->withErrors(['username' => 'Tài khoản của bạn đã bị vô hiệu hoá. Vui lòng liên hệ Admin.']);
        }

        // 3. Kiểm tra khoá tạm thời do nhập sai quá nhiều
        if ($user->isLocked()) {
            $minutes = $user->minutesUntilUnlock();
            return back()->withInput(['username' => $request->username])
                         ->withErrors([
                             'username' => "Bạn đã nhập sai mật khẩu quá số lần cho phép. "
                                         . "Tài khoản bị khoá tạm thời. Vui lòng thử lại sau {$minutes} phút "
                                         . "hoặc liên hệ Admin.",
                         ]);
        }

        // 4. Kiểm tra mật khẩu
        if (!Hash::check($request->password, $user->password)) {
            $user->incrementLoginAttempts();

            $remaining = User::MAX_LOGIN_ATTEMPTS - $user->login_attempts;

            if ($user->isLocked()) {
                return back()->withInput(['username' => $request->username])
                             ->withErrors([
                                 'username' => 'Bạn đã nhập sai mật khẩu quá số lần cho phép. '
                                             . 'Vui lòng liên hệ Admin.',
                             ]);
            }

            return back()->withInput(['username' => $request->username])
                         ->withErrors([
                             'password' => "Mật khẩu không đúng. Bạn còn {$remaining} lần thử.",
                         ]);
        }

        // 5. Đăng nhập thành công
        $user->resetLoginAttempts();
        Auth::login($user, $request->boolean('remember'));

        // 6. Lần đầu đăng nhập → bắt buộc đổi mật khẩu
        if ($user->is_first_login) {
            return redirect()->route('auth.change-password')
                             ->with('warning', 'Đây là lần đầu đăng nhập. Bạn phải đổi mật khẩu trước khi sử dụng hệ thống.');
        }

        return $this->redirectByRole($user);
    }

    // =========================================================================
    // ĐỔI MẬT KHẨU
    // =========================================================================

    /** Hiển thị form đổi mật khẩu */
    public function showChangePassword()
    {
        return view('auth.change-password', [
            'is_first_login' => Auth::user()->is_first_login,
        ]);
    }

    /** Xử lý đổi mật khẩu */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => [
                'required',
                'string',
                'min:8',
                'confirmed',                         // phải có new_password_confirmation
                'regex:/[A-Z]/',                     // ít nhất 1 chữ hoa
                'regex:/[0-9]/',                     // ít nhất 1 số
                'regex:/[@$!%*#?&]/',                // ít nhất 1 ký tự đặc biệt
            ],
        ], [
            'new_password.min'        => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'new_password.confirmed'  => 'Xác nhận mật khẩu không khớp.',
            'new_password.regex'      => 'Mật khẩu phải chứa chữ hoa, chữ số và ký tự đặc biệt.',
        ]);

        $user = Auth::user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }

        // Không được đặt lại mật khẩu cũ
        if (Hash::check($request->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'Mật khẩu mới không được trùng với mật khẩu cũ.']);
        }

        $user->update([
            'password'       => Hash::make($request->new_password),
            'is_first_login' => false,
        ]);

        return redirect()->route('projects.index')
                         ->with('success', 'Đổi mật khẩu thành công!');
    }

    // =========================================================================
    // ĐĂNG XUẤT
    // =========================================================================

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login')->with('success', 'Đã đăng xuất thành công.');
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function redirectByRole(User $user)
    {
        return redirect()->route('projects.index');
    }
}
