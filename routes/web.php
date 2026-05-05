<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminUserController;

// ─── Public routes (chưa đăng nhập) ─────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login']);
});

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'account.active'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Đổi mật khẩu — nhân viên + admin đều dùng được
    Route::get('/change-password',  [AuthController::class, 'showChangePassword'])->name('auth.change-password');
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // ─── Routes yêu cầu đã đổi pass lần đầu ──────────────────────────────────
    Route::middleware('require.password.change')->group(function () {

        // Dashboard nhân viên
        Route::get('/dashboard', fn() => view('employee.dashboard'))->name('employee.dashboard');

        // ─── Admin only ───────────────────────────────────────────────────────
        Route::middleware('admin.only')->prefix('admin')->name('admin.')->group(function () {

            Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

            // Quản lý tài khoản nhân viên
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/',                       [AdminUserController::class, 'index'])->name('index');
                Route::get('/create',                 [AdminUserController::class, 'create'])->name('create');
                Route::post('/',                      [AdminUserController::class, 'store'])->name('store');
                Route::post('/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset-password');
                Route::post('/{user}/toggle-active',  [AdminUserController::class, 'toggleActive'])->name('toggle-active');
                Route::post('/{user}/unlock',         [AdminUserController::class, 'unlock'])->name('unlock');
            });
        });
    });
});
