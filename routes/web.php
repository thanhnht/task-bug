<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\{ProjectController, TaskController, DashboardController, QualityController, NotificationController};

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

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('employee.dashboard');

        // Báo cáo & Đánh giá chất lượng
        Route::prefix('quality')->name('quality.')->group(function () {
            Route::get('/',            [QualityController::class, 'index'])->name('index');
            Route::get('/{project}',   [QualityController::class, 'show'])->name('show');
        });

        // Thông báo
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/',                          [NotificationController::class, 'index'])->name('index');
            Route::post('/read-all',                 [NotificationController::class, 'markAllRead'])->name('read-all');
            Route::post('/{notification}/read',      [NotificationController::class, 'markRead'])->name('read');
        });

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

        // ─── Projects & Tasks ─────────────────────────────────────────────────
        Route::prefix('projects')->name('projects.')->group(function () {

            Route::get('/',       [ProjectController::class, 'index'])->name('index');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/',      [ProjectController::class, 'store'])->name('store');

            Route::prefix('{project}')->group(function () {
                Route::get('/',     [ProjectController::class, 'show'])->name('show');
                Route::get('/edit', [ProjectController::class, 'edit'])->name('edit');
                Route::put('/',     [ProjectController::class, 'update'])->name('update');

                // Member management (Admin + PM)
                Route::post('/members',             [ProjectController::class, 'addMember'])->name('members.add');
                Route::delete('/members',           [ProjectController::class, 'removeMember'])->name('members.remove');
                Route::patch('/members/role',       [ProjectController::class, 'updateMemberRole'])->name('members.update-role');

                // ══════════════════════════════════════════════════════════════
                // TASKS (nested under project)
                // ══════════════════════════════════════════════════════════════
                Route::prefix('tasks')->name('tasks.')->group(function () {

                    Route::get('/',       [TaskController::class, 'index'])->name('index');
                    Route::get('/create', [TaskController::class, 'create'])->name('create');
                    Route::post('/',      [TaskController::class, 'store'])->name('store');

                    Route::prefix('{task}')->group(function () {
                        Route::get('/',            [TaskController::class, 'show'])->name('show');
                        Route::patch('/',          [TaskController::class, 'update'])->name('update');
                        Route::post('/transition', [TaskController::class, 'transition'])->name('transition');
                        // Task con (bất kỳ thành viên tạo)
                        Route::post('/children',                         [TaskController::class, 'storeChild'])->name('children.store');
                        Route::post('/children/{child}/transition',      [TaskController::class, 'transitionChild'])->name('children.transition');
                        Route::post('/report-bug',                       [TaskController::class, 'reportBug'])->name('report-bug');
                        Route::post('/comments',                         [\App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
                        Route::post('/comments/upload-image',            [\App\Http\Controllers\CommentController::class, 'uploadImage'])->name('comments.upload-image');
                        Route::delete('/comments/{comment}',             [\App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');
                    });
                });
            });
        });
    });
});
