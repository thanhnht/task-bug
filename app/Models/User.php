<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use Notifiable;

    // ─── Hằng số vai trò ─────────────────────────────────────────────────────
    const ROLE_ADMIN    = 'admin';
    const ROLE_EMPLOYEE = 'employee';

    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCK_DURATION_MINUTES = 30;

    // ─── Fillable ─────────────────────────────────────────────────────────────
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'full_name',
        'is_first_login',
        'is_active',
        'login_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_first_login' => 'boolean',
        'is_active'      => 'boolean',
        'locked_until'   => 'datetime',
        'last_login_at'  => 'datetime',
    ];

    // ─── Helper methods ───────────────────────────────────────────────────────

    /** Kiểm tra có phải Admin không */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Tài khoản đang bị khoá? */
    public function isLocked(): bool
    {
        if ($this->locked_until && Carbon::now()->lt($this->locked_until)) {
            return true;
        }
        return false;
    }

    /** Tài khoản đã bị Admin vô hiệu hoá? */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /** Tăng số lần đăng nhập sai, khoá nếu vượt ngưỡng */
    public function incrementLoginAttempts(): void
    {
        $this->login_attempts += 1;

        if ($this->login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->locked_until = Carbon::now()->addMinutes(self::LOCK_DURATION_MINUTES);
        }

        $this->save();
    }

    /** Reset lần nhập sai sau khi đăng nhập thành công */
    public function resetLoginAttempts(): void
    {
        $this->login_attempts = 0;
        $this->locked_until   = null;
        $this->last_login_at  = Carbon::now();
        $this->save();
    }

    /** Số phút còn lại cho đến khi mở khoá */
    public function minutesUntilUnlock(): int
    {
        if (!$this->locked_until) return 0;
        return (int) Carbon::now()->diffInMinutes($this->locked_until, false);
    }
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function roleInProject(Project $project): ?string
    {
        return $project->roleOf($this);
    }

    public function isPmOf(Project $project): bool
    {
        return $project->isPm($this);
    }
}
