<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Carbon\Carbon;

class Story extends Model
{
    // ── Constants ──────────────────────────────────────────────────────────
    const STATUS_TODO            = 'todo';
    const STATUS_IN_PROGRESS     = 'in_progress';
    const STATUS_READY_TO_REVIEW = 'ready_to_review';
    const STATUS_DONE            = 'done';

    const PRIORITY_LOW      = 'low';
    const PRIORITY_MEDIUM   = 'medium';
    const PRIORITY_HIGH     = 'high';
    const PRIORITY_CRITICAL = 'critical';

    const STATUS_LABELS = [
        'todo'            => 'To Do',
        'in_progress'     => 'In Progress',
        'ready_to_review' => 'Ready to Review',
        'done'            => 'Done',
    ];

    const PRIORITY_LABELS = [
        'low'      => 'Low',
        'medium'   => 'Medium',
        'high'     => 'High',
        'critical' => 'Critical',
    ];

    // Thứ tự hợp lệ để UI biết next/prev state
    const STATUS_ORDER = ['todo', 'in_progress', 'ready_to_review', 'done'];

    // ── Fillable ───────────────────────────────────────────────────────────
    protected $fillable = [
        'code', 'project_id', 'title', 'description', 'priority', 'status',
        'created_by', 'assigned_to', 'confirmed_by',
        'started_at', 'ready_at', 'done_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ready_at'   => 'datetime',
        'done_at'    => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────
    public function project(): BelongsTo   { return $this->belongsTo(Project::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function developer(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function confirmer(): BelongsTo { return $this->belongsTo(User::class, 'confirmed_by'); }
    // public function subtasks(): HasMany    { return $this->hasMany(Subtask::class); }
    // public function bugs(): HasMany        { return $this->hasMany(Bug::class); }
    public function histories(): HasMany   { return $this->hasMany(StoryHistory::class)->orderByDesc('created_at'); }

    // ── Business rule checks ───────────────────────────────────────────────

    /**
     * Developer chuyển sang Ready to Review:
     * Điều kiện: không có subtask hoặc tất cả subtask Done
     */
    public function canMoveToReadyToReview(): array
    {
        $pending = $this->subtasks()->whereNotIn('status', ['done'])->count();
        if ($pending > 0) {
            return [
                'ok'      => false,
                'message' => "Còn {$pending} Subtask chưa hoàn thành. Cần hoàn thành tất cả Subtask trước.",
            ];
        }
        return ['ok' => true];
    }

    /**
     * PM xác nhận Done:
     * Điều kiện: tất cả Bug đã Closed
     */
    public function canBeDone(): array
    {
        $openBugs = $this->bugs()->whereNotIn('status', ['closed'])->count();
        if ($openBugs > 0) {
            return [
                'ok'      => false,
                'message' => "Còn {$openBugs} Bug chưa đóng. PM chưa thể xác nhận Done.",
            ];
        }
        return ['ok' => true];
    }

    /**
     * Luồng chuyển trạng thái hợp lệ theo vai trò:
     *
     *  todo  ──[Developer/PM]──▶  in_progress
     *  in_progress  ──[Developer]──▶  ready_to_review  (kiểm tra subtask)
     *  ready_to_review  ──[PM]──▶  done  (kiểm tra bugs)
     *  ready_to_review  ──[PM/system]──▶  in_progress  (reject review)
     */
    public function transitionTo(string $newStatus, User $actor, ?string $note = null): array
    {
        $projectRole = $this->project->roleOf($actor);

        $allowed = [
            'todo' => [
                'in_progress' => ['pm', 'developer', 'admin'],
            ],
            'in_progress' => [
                'ready_to_review' => ['developer', 'admin'],
            ],
            'ready_to_review' => [
                'done'        => ['pm', 'admin'],
                'in_progress' => ['pm', 'admin'],  // PM reject → trả về dev
            ],
            'done' => [],
        ];

        $allowedRoles = $allowed[$this->status][$newStatus] ?? null;

        if ($allowedRoles === null) {
            return ['ok' => false, 'message' => "Không thể chuyển từ [{$this->statusLabel()}] sang [{$this->statusLabel($newStatus)}]."];
        }

        if (!in_array($projectRole, $allowedRoles)) {
            return ['ok' => false, 'message' => "Vai trò [{$projectRole}] không có quyền thực hiện thao tác này."];
        }

        // Business rule checks
        if ($newStatus === self::STATUS_READY_TO_REVIEW) {
            $check = $this->canMoveToReadyToReview();
            if (!$check['ok']) return $check;
        }

        if ($newStatus === self::STATUS_DONE) {
            $check = $this->canBeDone();
            if (!$check['ok']) return $check;
        }

        // Perform transition
        $old      = $this->status;
        $updates  = ['status' => $newStatus];

        if ($newStatus === self::STATUS_IN_PROGRESS && $old === self::STATUS_TODO) {
            $updates['started_at'] = Carbon::now();
        }
        if ($newStatus === self::STATUS_READY_TO_REVIEW) {
            $updates['ready_at'] = Carbon::now();
        }
        if ($newStatus === self::STATUS_DONE) {
            $updates['done_at']      = Carbon::now();
            $updates['confirmed_by'] = $actor->id;
        }

        $this->update($updates);

        // Log history
        $this->histories()->create([
            'from_status' => $old,
            'to_status'   => $newStatus,
            'note'        => $note,
            'changed_by'  => $actor->id,
        ]);

        return ['ok' => true, 'message' => "Story đã chuyển sang <strong>{$this->statusLabel($newStatus)}</strong>."];
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public static function nextCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'STR-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
    }

    public function statusLabel(string $status = null): string
    {
        return self::STATUS_LABELS[$status ?? $this->status] ?? ($status ?? $this->status);
    }

    public function priorityLabel(): string { return self::PRIORITY_LABELS[$this->priority] ?? $this->priority; }

    public function openBugsCount(): int
    {
        return $this->bugs()->whereNotIn('status', ['closed'])->count();
    }

    public function pendingSubtasksCount(): int
    {
        // return $this->subtasks()->whereNotIn('status', ['done'])->count();
        return 0;
    }

    /** Trả về trạng thái tiếp theo hợp lệ theo vai trò user */
    public function nextTransitions(User $user): array
    {
        $role = $this->project->roleOf($user);

        $map = [
            'todo' => [
                ['status' => 'in_progress', 'label' => 'Bắt đầu', 'roles' => ['pm', 'developer', 'admin']],
            ],
            'in_progress' => [
                ['status' => 'ready_to_review', 'label' => 'Gửi Review', 'roles' => ['developer', 'admin']],
            ],
            'ready_to_review' => [
                ['status' => 'done',        'label' => 'Xác nhận Done',  'roles' => ['pm', 'admin']],
                ['status' => 'in_progress', 'label' => 'Từ chối Review', 'roles' => ['pm', 'admin']],
            ],
            'done' => [],
        ];

        return array_filter(
            $map[$this->status] ?? [],
            fn($t) => in_array($role, $t['roles'])
        );
    }
}
