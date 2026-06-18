<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Carbon\Carbon;

class Task extends Model
{
    // ── Types ──────────────────────────────────────────────────────────────
    const TYPE_TASK     = 'task';      // công việc chính hoặc task con chung
    const TYPE_SUBTASK  = 'subtask';   // phân rã task con
    const TYPE_BUG      = 'bug';       // bug nội bộ (con của task)
    const TYPE_RESEARCH = 'research';  // điều tra / phân tích
    const TYPE_FIX      = 'fix';       // sửa lỗi cụ thể
    const TYPE_TEST     = 'test';      // kiểm thử

    const TYPE_LABELS = [
        'task'     => 'Task',
        'subtask'  => 'Subtask',
        'bug'      => 'Bug',
        'test'     => 'Test',
    ];

    // ── Status ─────────────────────────────────────────────────────────────
    const STATUS_TODO            = 'todo';
    const STATUS_IN_PROGRESS     = 'in_progress';
    const STATUS_READY_TO_TEST   = 'ready_to_test';
    const STATUS_REVIEW_APPROVED = 'review_approved';
    const STATUS_DONE            = 'done';

    const STATUS_LABELS = [
        'todo'            => 'To Do',
        'in_progress'     => 'In Progress',
        'ready_to_test'   => 'Ready to Test',
        'review_approved' => 'Review Approved',
        'done'            => 'Done',
    ];

    const STATUS_ORDER = ['todo', 'in_progress', 'ready_to_test', 'review_approved', 'done'];

    // ── Priority ───────────────────────────────────────────────────────────
    const PRIORITY_LOW      = 'low';
    const PRIORITY_MEDIUM   = 'medium';
    const PRIORITY_HIGH     = 'high';
    const PRIORITY_CRITICAL = 'critical';

    const PRIORITY_LABELS = [
        'low'      => 'Low',
        'medium'   => 'Medium',
        'high'     => 'High',
        'critical' => 'Critical',
    ];

    // ── Fillable ───────────────────────────────────────────────────────────
    protected $fillable = [
        'code', 'project_id', 'parent_id', 'type',
        'is_production_bug', 'linked_story_id',
        'title', 'description', 'priority', 'status',
        'start_date', 'due_date', 'estimated_hours',
        'created_by', 'assigned_to', 'confirmed_by',
        'started_at', 'ready_at', 'done_at',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'due_date'          => 'date',
        'started_at'        => 'datetime',
        'ready_at'          => 'datetime',
        'done_at'           => 'datetime',
        'is_production_bug' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────
    public function project(): BelongsTo      { return $this->belongsTo(Project::class); }
    public function parent(): BelongsTo       { return $this->belongsTo(Task::class, 'parent_id'); }
    public function children(): HasMany       { return $this->hasMany(Task::class, 'parent_id')->orderBy('created_at'); }
    public function creator(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee(): BelongsTo     { return $this->belongsTo(User::class, 'assigned_to'); }
    public function confirmer(): BelongsTo    { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function histories(): HasMany      { return $this->hasMany(TaskHistory::class)->orderByDesc('created_at'); }
    public function linkedStory(): BelongsTo  { return $this->belongsTo(Task::class, 'linked_story_id'); }
    public function productionBugs(): HasMany { return $this->hasMany(Task::class, 'linked_story_id')->where('is_production_bug', true); }
    public function comments(): HasMany       { return $this->hasMany(\App\Models\Comment::class)->orderBy('created_at'); }

    // ── Hierarchy helpers ──────────────────────────────────────────────────
    public function isMainTask(): bool  { return $this->parent_id === null; }
    public function isChildTask(): bool { return $this->parent_id !== null; }

    // ── Business rule checks ───────────────────────────────────────────────

    /**
     * Bug không tính vào điều kiện block — chỉ kiểm tra non-bug children.
     */
    public function canMoveToReadyToTest(): array
    {
        $pending = $this->children()->where('type', '!=', self::TYPE_BUG)
                        ->whereNotIn('status', ['done'])->count();
        if ($pending > 0) {
            return ['ok' => false, 'message' => "Còn {$pending} task con chưa hoàn thành."];
        }
        return ['ok' => true];
    }

    public function canBeDone(): array
    {
        if ($this->isMainTask()) {
            // Story: all children including bugs must be done
            $pending = $this->children()->whereNotIn('status', ['done'])->count();
            if ($pending > 0) {
                return ['ok' => false, 'message' => "Còn {$pending} task con (bao gồm Bug) chưa hoàn thành."];
            }
        } else {
            $pending = $this->children()->where('type', '!=', self::TYPE_BUG)
                            ->whereNotIn('status', ['done'])->count();
            if ($pending > 0) {
                return ['ok' => false, 'message' => "Còn {$pending} task con chưa hoàn thành."];
            }
        }
        return ['ok' => true];
    }

    /**
     * Chuyển trạng thái tự do — người dùng chọn bất kỳ status nào.
     * Chỉ block khi non-bug children chưa done (ready_to_test / done).
     */
    public function transitionTo(string $newStatus, User $actor, ?string $note = null): array
    {
        if (!array_key_exists($newStatus, self::STATUS_LABELS)) {
            return ['ok' => false, 'message' => 'Trạng thái không hợp lệ.'];
        }

        if ($newStatus === $this->status) {
            return ['ok' => false, 'message' => 'Task đang ở trạng thái này rồi.'];
        }

        if ($newStatus === self::STATUS_READY_TO_TEST) {
            $check = $this->canMoveToReadyToTest();
            if (!$check['ok']) return $check;
        }

        if ($newStatus === self::STATUS_REVIEW_APPROVED) {
            // Only root tasks go through review_approved before PM marks done
            if ($this->isChildTask()) {
                return ['ok' => false, 'message' => 'Task con không cần bước Review Approved — Tester chuyển thẳng sang Done.'];
            }
            $role = $this->project->roleOf($actor);
            if (!$actor->isAdmin() && $role !== 'tester') {
                return ['ok' => false, 'message' => 'Chỉ Tester mới có thể phê duyệt Review Approved.'];
            }
            $openBugs = $this->children()
                ->where('type', self::TYPE_BUG)
                ->where('status', '!=', self::STATUS_DONE)
                ->count();
            if ($openBugs > 0) {
                return ['ok' => false, 'message' => "Còn {$openBugs} bug chưa đóng. Phải đóng hết bug trước khi Approved."];
            }
        }

        if ($newStatus === self::STATUS_DONE) {
            // Root task: must pass through review_approved first (PM approves)
            if ($this->isMainTask() && $this->status !== self::STATUS_REVIEW_APPROVED) {
                return ['ok' => false, 'message' => 'Task chính phải được Tester phê duyệt (Review Approved) trước khi PM nghiệm thu Done.'];
            }

            $check = $this->canBeDone();
            if (!$check['ok']) return $check;

            $role = $this->project->roleOf($actor);
            if ($this->isMainTask()) {
                // Root task done: only PM (or admin)
                if (!$actor->isAdmin() && $role !== 'pm') {
                    return ['ok' => false, 'message' => 'Chỉ PM mới có thể nghiệm thu Done cho Task chính.'];
                }
            } else {
                // Child task done: PM or Tester
                if (!$actor->isAdmin() && $role !== 'pm' && $role !== 'tester') {
                    return ['ok' => false, 'message' => 'Chỉ PM hoặc Tester mới có thể xác nhận hoàn thành (Done).'];
                }
            }
        }

        $old     = $this->status;
        $updates = ['status' => $newStatus];

        if ($newStatus === self::STATUS_IN_PROGRESS && $old === self::STATUS_TODO) {
            $updates['started_at'] = Carbon::now();
        }
        if ($newStatus === self::STATUS_READY_TO_TEST) {
            $updates['ready_at'] = Carbon::now();
        }
        if ($newStatus === self::STATUS_DONE) {
            $updates['done_at']      = Carbon::now();
            $updates['confirmed_by'] = $actor->id;
        }
        // Revert từ done hoặc review_approved về trạng thái khác
        if (in_array($old, [self::STATUS_DONE, self::STATUS_REVIEW_APPROVED]) && $newStatus !== self::STATUS_DONE) {
            $updates['done_at']      = null;
            $updates['confirmed_by'] = null;
        }

        $this->update($updates);

        $this->histories()->create([
            'from_status' => $old,
            'to_status'   => $newStatus,
            'note'        => $note,
            'changed_by'  => $actor->id,
        ]);

        // Cascade auto-update lên task cha (nếu có)
        if ($this->parent_id) {
            $this->parent->autoUpdateFromChildren($actor);
        }

        return ['ok' => true, 'message' => "Task đã chuyển sang <strong>{$this->statusLabel($newStatus)}</strong>."];
    }

    // ── Auto-cascade parent status ─────────────────────────────────────────
    /**
     * Gọi sau khi một task con chuyển trạng thái.
     * - Tất cả con Done → tự Done
     * - Bất kỳ con nào active (in_progress/review) → tự In Progress (nếu đang Todo)
     * - Con chưa Done → revert parent từ Done về In Progress
     * Cascade đệ quy lên các tầng cha.
     */
    public function autoUpdateFromChildren(User $actor): void
    {
        $total      = $this->children()->where('type', '!=', self::TYPE_BUG)->count();
        if ($total === 0) return;

        $doneCount   = $this->children()->where('type', '!=', self::TYPE_BUG)->where('status', self::STATUS_DONE)->count();
        $activeCount = $this->children()->where('type', '!=', self::TYPE_BUG)
            ->whereIn('status', [self::STATUS_IN_PROGRESS, self::STATUS_READY_TO_TEST])
            ->count();

        $old       = $this->status;
        $newStatus = null;
        $note      = null;

        if ($doneCount === $total && !in_array($old, [self::STATUS_READY_TO_TEST, self::STATUS_REVIEW_APPROVED, self::STATUS_DONE])) {
            // Tất cả con xong → chờ Tester review
            $newStatus = self::STATUS_READY_TO_TEST;
            $note      = 'Tự động: tất cả task con đã hoàn thành, chờ Tester/PM xác nhận Done.';
        } elseif ($activeCount > 0 && $old === self::STATUS_TODO) {
            $newStatus = self::STATUS_IN_PROGRESS;
            $note      = 'Tự động: task con bắt đầu.';
        } elseif ($doneCount < $total && in_array($old, [self::STATUS_DONE, self::STATUS_REVIEW_APPROVED, self::STATUS_READY_TO_TEST])) {
            $newStatus = self::STATUS_IN_PROGRESS;
            $note      = 'Tự động: task con chưa hoàn thành đầy đủ, revert về In Progress.';
        }

        if (!$newStatus) return;

        $updates = ['status' => $newStatus];
        if ($newStatus === self::STATUS_IN_PROGRESS && $old === self::STATUS_TODO) {
            $updates['started_at'] = now();
        }
        if ($newStatus === self::STATUS_READY_TO_TEST) {
            $updates['ready_at'] = now();
        }
        if (in_array($old, [self::STATUS_DONE, self::STATUS_REVIEW_APPROVED]) && $newStatus !== self::STATUS_DONE) {
            $updates['done_at']      = null;
            $updates['confirmed_by'] = null;
        }

        $this->update($updates);

        $this->histories()->create([
            'from_status' => $old,
            'to_status'   => $newStatus,
            'note'        => $note,
            'changed_by'  => $actor->id,
        ]);

        // Cascade lên cha nếu có
        if ($this->parent_id) {
            $this->parent->autoUpdateFromChildren($actor);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public static function nextCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'TSK-' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
    }

    public function statusLabel(?string $status = null): string
    {
        return self::STATUS_LABELS[$status ?? $this->status] ?? ($status ?? $this->status);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function pendingChildrenCount(): int
    {
        return $this->children()->whereNotIn('status', ['done'])->count();
    }

    /**
     * Nếu có task con: trả về tổng estimated_hours của con.
     * Nếu không có: trả về estimated_hours của chính task này.
     */
    public function effectiveEstimatedHours(): ?float
    {
        if ($this->relationLoaded('children') && $this->children->count() > 0) {
            $sum = (float) $this->children->sum('estimated_hours');
            return $sum > 0 ? $sum : null;
        }
        $count = $this->children()->count();
        if ($count > 0) {
            $sum = (float) $this->children()->sum('estimated_hours');
            return $sum > 0 ? $sum : null;
        }
        return $this->estimated_hours ? (float) $this->estimated_hours : null;
    }

    /**
     * % hoàn thành.
     */
    public function progressPercent(): int
    {
        if ($this->relationLoaded('children')) {
            $total = $this->children->count();
            if ($total > 0) {
                $done = $this->children->where('status', self::STATUS_DONE)->count();
                return (int) round($done / $total * 100);
            }
        } else {
            $total = $this->children()->count();
            if ($total > 0) {
                $done = $this->children()->where('status', self::STATUS_DONE)->count();
                return (int) round($done / $total * 100);
            }
        }

        return match($this->status) {
            self::STATUS_IN_PROGRESS   => 50,
            self::STATUS_READY_TO_TEST => 80,
            self::STATUS_DONE          => 100,
            default                    => 0,
        };
    }

    /** Trả về tất cả trạng thái có thể chuyển (tất cả trừ trạng thái hiện tại) */
    public function nextTransitions(User $user): array
    {
        $result = [];
        foreach (self::STATUS_LABELS as $status => $label) {
            if ($status === $this->status) continue;
            // Child tasks skip review_approved — they go directly to done
            if ($status === self::STATUS_REVIEW_APPROVED && $this->isChildTask()) continue;
            $result[] = ['status' => $status, 'label' => $label];
        }
        return $result;
    }
}
