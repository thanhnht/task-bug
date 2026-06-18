<?php

namespace App\Services;

use App\Models\{KpiTransaction, Task, TaskHistory, User};
use Illuminate\Support\Facades\DB;

class KpiService
{
    const BASE_SCORE           = 100.0;
    const RTT_SLA_HOURS        = 48;    // Tester phải xử lý trong 2 ngày
    const LATE_PER_DAY         = -2.0;  // Dev trễ deadline task
    const BUG_CREATED          = -0.25; // Dev tạo ra lỗi trong quá trình test
    const RTT_SOAK_PER_DAY     = -0.5;  // Tester ngâm RTT quá hạn
    const PRODUCTION_BUG       = -5.0;  // Lỗi lọt lưới ra Production

    // ── Score / History ────────────────────────────────────────────────────

    public static function scoreForMonth(int $userId, ?string $month = null): float
    {
        $month     = $month ?? now()->format('Y-m');
        $sum       = KpiTransaction::where('user_id', $userId)
                         ->where('period_month', $month)
                         ->sum('points');
        return max(0, round(self::BASE_SCORE + $sum, 2));
    }

    public static function transactionsForMonth(int $userId, ?string $month = null)
    {
        $month = $month ?? now()->format('Y-m');
        return KpiTransaction::where('user_id', $userId)
            ->where('period_month', $month)
            ->with(['task:id,code,title', 'project:id,name,code'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Điểm KPI của nhiều user trong một tháng.
     * Trả về mảng [user_id => score].
     */
    public static function teamScores(array $userIds, ?string $month = null): array
    {
        if (empty($userIds)) return [];
        $month = $month ?? now()->format('Y-m');

        $sums = KpiTransaction::whereIn('user_id', $userIds)
            ->where('period_month', $month)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('sum(points) as total'))
            ->pluck('total', 'user_id');

        $result = [];
        foreach ($userIds as $uid) {
            $result[$uid] = max(0, round(self::BASE_SCORE + ($sums[$uid] ?? 0), 2));
        }
        return $result;
    }

    // ── Deduction triggers ────────────────────────────────────────────────

    /**
     * Gọi khi task → done: trừ điểm dev nếu trễ hạn.
     */
    public static function deductForLateness(Task $task): void
    {
        if (!$task->due_date || !$task->done_at) return;

        $dueDay  = $task->due_date->copy()->startOfDay();
        $doneDay = $task->done_at->copy()->startOfDay();

        if ($doneDay->lte($dueDay)) return;

        $daysLate = (int) $dueDay->diffInDays($doneDay);
        if ($daysLate <= 0) return;

        $devId = self::findDevId($task);
        if (!$devId) return;

        self::record(
            $devId,
            $task->project_id,
            $task->id,
            self::LATE_PER_DAY * $daysLate,
            "Trễ hạn {$daysLate} ngày: [{$task->code}] {$task->title}"
        );
    }

    /**
     * Gọi khi bug mới được tạo: trừ 0.25 điểm dev của parent task.
     */
    public static function deductForBugCreated(Task $parentTask): void
    {
        $devId = self::findDevId($parentTask);
        if (!$devId) return;

        self::record(
            $devId,
            $parentTask->project_id,
            $parentTask->id,
            self::BUG_CREATED,
            "Phát sinh Bug trong [{$parentTask->code}] {$parentTask->title}"
        );
    }

    /**
     * Gọi khi task rời RTT → review_approved / done: trừ tester nếu ngâm quá 2 ngày.
     */
    public static function deductForRttSoak(Task $task, int $testerId): void
    {
        $enteredAt = TaskHistory::where('task_id', $task->id)
            ->where('to_status', Task::STATUS_READY_TO_TEST)
            ->orderByDesc('created_at')
            ->value('created_at');

        if (!$enteredAt) return;

        $hoursInRtt = (int) \Carbon\Carbon::parse($enteredAt)->diffInHours(now());
        if ($hoursInRtt <= self::RTT_SLA_HOURS) return;

        $daysOver = (int) ceil(($hoursInRtt - self::RTT_SLA_HOURS) / 24);
        $points   = self::RTT_SOAK_PER_DAY * $daysOver;

        self::record(
            $testerId,
            $task->project_id,
            $task->id,
            $points,
            "Ngâm RTT {$daysOver} ngày quá hạn: [{$task->code}] {$task->title}"
        );
    }

    /**
     * Gọi khi production bug được tạo: trừ -5 dev VÀ tester của story gốc.
     */
    public static function deductForProductionBug(Task $productionBug): void
    {
        if (!$productionBug->linked_story_id) return;

        $story = Task::find($productionBug->linked_story_id);
        if (!$story) return;

        $devId    = self::findDevId($story);
        $testerId = self::findTesterId($story);

        $bugLabel   = "[{$productionBug->code}] {$productionBug->title}";
        $storyLabel = "[{$story->code}] {$story->title}";

        if ($devId) {
            self::record(
                $devId,
                $productionBug->project_id,
                $productionBug->id,
                self::PRODUCTION_BUG,
                "Lỗi lọt lưới (code): {$storyLabel} — {$bugLabel}"
            );
        }
        if ($testerId && $testerId !== $devId) {
            self::record(
                $testerId,
                $productionBug->project_id,
                $productionBug->id,
                self::PRODUCTION_BUG,
                "Lỗi lọt lưới (test sót): {$storyLabel} — {$bugLabel}"
            );
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private static function record(int $userId, ?int $projectId, ?int $taskId, float $points, string $reason): void
    {
        KpiTransaction::create([
            'user_id'      => $userId,
            'project_id'   => $projectId,
            'task_id'      => $taskId,
            'points'       => $points,
            'reason'       => $reason,
            'period_month' => now()->format('Y-m'),
        ]);
    }

    /** Developer = người cuối cùng chuyển task sang RTT (hoặc assigned_to). */
    public static function findDevId(Task $task): ?int
    {
        $id = TaskHistory::where('task_id', $task->id)
            ->where('to_status', Task::STATUS_READY_TO_TEST)
            ->orderByDesc('created_at')
            ->value('changed_by');

        return $id ?? $task->assigned_to;
    }

    /** Tester = người chuyển task sang review_approved hoặc done. */
    private static function findTesterId(Task $task): ?int
    {
        return TaskHistory::where('task_id', $task->id)
            ->whereIn('to_status', [Task::STATUS_REVIEW_APPROVED, Task::STATUS_DONE])
            ->orderByDesc('created_at')
            ->value('changed_by');
    }
}
