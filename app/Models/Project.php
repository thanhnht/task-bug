<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo, BelongsToMany};


class Project extends Model
{
    // ── Constants ──────────────────────────────────────────────────────────
    const STATUS_ACTIVE    = 'active';
    const STATUS_ON_HOLD   = 'on_hold';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ARCHIVED  = 'archived';

    const STATUS_LABELS = [
        'active'    => 'Đang chạy',
        'on_hold'   => 'Tạm dừng',
        'completed' => 'Hoàn thành',
        'archived'  => 'Lưu trữ',
    ];

    const ROLE_PM        = 'pm';
    const ROLE_DEVELOPER = 'developer';
    const ROLE_TESTER    = 'tester';

    const ROLE_LABELS = [
        'pm'        => 'PM',
        'developer' => 'Developer',
        'tester'    => 'Tester',
    ];

    // ── Fillable ───────────────────────────────────────────────────────────
    protected $fillable = [
        'code', 'name', 'description', 'status',
        'start_date', 'end_date', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // ── Relations ──────────────────────────────────────────────────────────
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Chỉ lấy task gốc (không có parent) */
    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id');
    }

    /**
     * Tất cả thành viên qua pivot project_members.
     * Pivot chứa: role, joined_at
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role', 'joined_at')
                    ->withTimestamps();
    }

    /** Chỉ lấy thành viên với vai trò cụ thể */
    public function membersWithRole(string $role): BelongsToMany
    {
        return $this->members()->wherePivot('role', $role);
    }

    public function pms(): BelongsToMany       { return $this->membersWithRole(self::ROLE_PM); }
    public function developers(): BelongsToMany { return $this->membersWithRole(self::ROLE_DEVELOPER); }
    public function testers(): BelongsToMany    { return $this->membersWithRole(self::ROLE_TESTER); }

    public function hasTesters(): bool
    {
        return $this->members()->wherePivot('role', self::ROLE_TESTER)->exists();
    }

    // ── Permission helpers ─────────────────────────────────────────────────

    /** Lấy vai trò của user trong project này */
    public function roleOf(User $user): ?string
    {
        if ($user->isAdmin()) return 'admin';

        $member = $this->members()
                       ->where('users.id', $user->id)
                       ->first();

        return $member?->pivot->role;
    }

    /** User có phải PM của project này không? */
    public function isPm(User $user): bool
    {
        return $user->isAdmin() || $this->roleOf($user) === self::ROLE_PM;
    }

    /** User có phải Developer của project này không? */
    public function isDeveloper(User $user): bool
    {
        return $user->isAdmin() || $this->roleOf($user) === self::ROLE_DEVELOPER;
    }

    /** User có phải Tester của project này không? */
    public function isTester(User $user): bool
    {
        return $user->isAdmin() || $this->roleOf($user) === self::ROLE_TESTER;
    }

    /** User có thuộc project không (bất kỳ vai trò) */
    public function hasMember(User $user): bool
    {
        return $user->isAdmin()
            || $this->members()->where('users.id', $user->id)->exists();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    /** Chỉ lấy project mà user có thể thấy */
    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isAdmin()) return $query;

        return $query->whereHas('members', fn($q) => $q->where('users.id', $user->id));
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public static function nextCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'PRJ-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string { return self::STATUS_LABELS[$this->status] ?? $this->status; }

    public function progressPercent(): int
    {
        $total = $this->rootTasks()->count();
        if ($total === 0) return 0;

        $done = $this->rootTasks()->where('status', Task::STATUS_DONE)->count();
        return (int) round($done / $total * 100);
    }
}
