<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class AdminUserController extends Controller
{
    // =========================================================================
    // DANH SÁCH TÀI KHOẢN
    // =========================================================================

    public function index()
    {
        $baseQuery = User::where('role', User::ROLE_EMPLOYEE);

        $stats = [
            'total'        => (clone $baseQuery)->count(),
            'active'       => (clone $baseQuery)->where('is_active', true)->count(),
            'first_login'  => (clone $baseQuery)->where('is_first_login', true)->count(),
            'locked'       => (clone $baseQuery)->whereNotNull('locked_until')
                                ->where('locked_until', '>', now())->count(),
        ];

        $users = (clone $baseQuery)->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.users.index', compact('users', 'stats'));
    }

    // =========================================================================
    // TẠO TÀI KHOẢN MỚI (Admin cấp khi nhân viên mới vào)
    // =========================================================================

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'     => 'required|email|unique:users,email',
            'full_name' => 'required|string|max:100',
        ], [
            'email.unique' => 'Email đã được sử dụng.',
        ]);

        $username = $this->generateUsername($request->full_name);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'username' => $username,
            'email' => $request->email,
            'full_name' => $request->full_name,
            'password' => Hash::make($temporaryPassword),
            'role' => User::ROLE_EMPLOYEE,
            'is_first_login' => true,
            'is_active' => true,
            'login_attempts' => 0,
        ]);

        Mail::to($user->email)->send(new WelcomeMail($user, $temporaryPassword));

        return redirect()->route('admin.users.index')
            ->with('success', "Tạo tài khoản thành công! Username: <strong>{$username}</strong> | Mật khẩu tạm: <strong>{$temporaryPassword}</strong>")
            ->with('temp_password', $temporaryPassword);
    }

    public function resetPassword(User $user)
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $user->update([
            'password'       => Hash::make($temporaryPassword),
            'is_first_login' => true,    // Bắt buộc đổi pass sau khi được cấp lại
            'login_attempts' => 0,
            'locked_until'   => null,
        ]);

        Mail::to($user->email)->send(new WelcomeMail($user, $temporaryPassword));

        return redirect()->route('admin.users.index')
            ->with('success', "Đã cấp lại mật khẩu cho <strong>{$user->full_name}</strong>. Mật khẩu tạm thời: <strong>{$temporaryPassword}</strong>")
            ->with('temp_password', $temporaryPassword);
    }

    // =========================================================================
    // KÍCH HOẠT / VÔ HIỆU HOÁ TÀI KHOẢN
    // =========================================================================

    public function toggleActive(User $user)
    {
        // Không cho phép vô hiệu hoá Admin
        if ($user->isAdmin()) {
            return back()->withErrors(['error' => 'Không thể vô hiệu hoá tài khoản Admin.']);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'kích hoạt' : 'vô hiệu hoá';
        return back()->with('success', "Đã {$status} tài khoản {$user->full_name}.");
    }

    // =========================================================================
    // MỞ KHOÁ TÀI KHOẢN (bị khoá do nhập sai quá nhiều)
    // =========================================================================

    public function unlock(User $user)
    {
        $user->update([
            'login_attempts' => 0,
            'locked_until'   => null,
        ]);

        return back()->with('success', "Đã mở khoá tài khoản {$user->full_name}.");
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /** Tạo mật khẩu tạm thời đủ mạnh */
    private function generateTemporaryPassword(): string
    {
        // Đảm bảo có đủ: chữ hoa, số, ký tự đặc biệt
        $upper   = strtoupper(Str::random(2));
        $lower   = strtolower(Str::random(4));
        $numbers = rand(10, 99);
        $special = Str::random(1, '@$!%*#?&');

        return str_shuffle($upper . $lower . $numbers . '@');
    }


    private function generateUsername($fullName)
    {
        // Bỏ dấu + chuyển lowercase
        $name = Str::of($fullName)->ascii()->lower();

        // Tách thành mảng
        $parts = explode(' ', $name);

        // Lấy tên (phần cuối)
        $lastName = array_pop($parts);

        // Lấy chữ cái đầu của họ + đệm
        $initials = '';
        foreach ($parts as $part) {
            $initials .= substr($part, 0, 1);
        }

        // Username base
        $baseUsername = $lastName . $initials;

        // Check trùng
        $username = $baseUsername;
        $i = 1;

        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $baseUsername . $i;
            $i++;
        }

        return $username;
    }
}
