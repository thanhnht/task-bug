<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, TaskHistory, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class QualityController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $projects = Project::accessibleBy($user)
            ->with('members')
            ->orderByDesc('created_at')
            ->get();

        $projectIds = $projects->pluck('id');

        $bugQuery = Task::whereIn('project_id', $projectIds)
            ->where('type', Task::TYPE_BUG);
        if ($dateFrom) $bugQuery->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $bugQuery->whereDate('created_at', '<=', $dateTo);

        $bugRows = (clone $bugQuery)
            ->select(
                'project_id',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'done' then 1 else 0 end) as closed")
            )
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $retestBase = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
            ->whereIn('tasks.project_id', $projectIds)
            ->where('task_histories.from_status', Task::STATUS_READY_TO_TEST)
            ->where('task_histories.to_status', Task::STATUS_IN_PROGRESS);
        if ($dateFrom) $retestBase->whereDate('task_histories.created_at', '>=', $dateFrom);
        if ($dateTo)   $retestBase->whereDate('task_histories.created_at', '<=', $dateTo);

        $retestRows = (clone $retestBase)
            ->select('tasks.project_id', DB::raw('count(*) as cnt'))
            ->groupBy('tasks.project_id')
            ->pluck('cnt', 'project_id');

        $projectStats = $projects->map(function ($p) use ($bugRows, $retestRows, $user) {
            $bugs   = $bugRows->get($p->id);
            $total  = $bugs?->total  ?? 0;
            $closed = $bugs?->closed ?? 0;
            $open   = $total - $closed;
            $dre    = $total > 0 ? round($closed / $total * 100, 1) : null;
            $retest = $retestRows->get($p->id, 0);
            $role   = $p->roleOf($user);

            return (object)[
                'project'     => $p,
                'bugs_total'  => $total,
                'bugs_open'   => $open,
                'bugs_closed' => $closed,
                'dre'         => $dre,
                'retest'      => $retest,
                'role'        => $role,
            ];
        });

        $totals = (object)[
            'bugs'   => $bugRows->sum('total'),
            'open'   => $bugRows->sum('total') - $bugRows->sum('closed'),
            'closed' => $bugRows->sum('closed'),
            'retest' => $retestRows->sum(),
        ];

        return view('quality.index', compact('user', 'projectStats', 'totals', 'dateFrom', 'dateTo'));
    }

    public function show(Request $request, Project $project)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$project->hasMember($user)) {
            abort(403, 'Bạn không phải thành viên của dự án này.');
        }

        $role     = $project->roleOf($user);
        $isPmView = $user->isAdmin() || $role === 'pm';

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // ── Task overview (non-bug) ───────────────────────────────────────────
        $taskBase = Task::where('project_id', $project->id)->where('type', '!=', Task::TYPE_BUG);
        if ($dateFrom) $taskBase->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $taskBase->whereDate('created_at', '<=', $dateTo);

        $taskTotal = (clone $taskBase)->count();
        $taskDone  = (clone $taskBase)->where('status', Task::STATUS_DONE)->count();
        $taskOpen  = $taskTotal - $taskDone;

        // ── Bug overview ─────────────────────────────────────────────────────
        $bugBase = Task::where('project_id', $project->id)->where('type', Task::TYPE_BUG);
        if ($dateFrom) $bugBase->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $bugBase->whereDate('created_at', '<=', $dateTo);

        $bugTotal  = (clone $bugBase)->count();
        $bugClosed = (clone $bugBase)->where('status', Task::STATUS_DONE)->count();
        $bugOpen   = $bugTotal - $bugClosed;
        $dre       = $bugTotal > 0 ? round($bugClosed / $bugTotal * 100, 1) : null;

        // ── Retest total ─────────────────────────────────────────────────────
        $retestBase = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
            ->where('tasks.project_id', $project->id)
            ->where('task_histories.from_status', Task::STATUS_READY_TO_TEST)
            ->where('task_histories.to_status', Task::STATUS_IN_PROGRESS);
        if ($dateFrom) $retestBase->whereDate('task_histories.created_at', '>=', $dateFrom);
        if ($dateTo)   $retestBase->whereDate('task_histories.created_at', '<=', $dateTo);
        $retestTotal = (clone $retestBase)->count();

        // ── Developer stats ──────────────────────────────────────────────────
        $developers = $project->developers()->orderBy('full_name')->get();
        if (!$isPmView) {
            $developers = $developers->filter(fn($d) => $d->id === $user->id)->values();
        }

        $devStats = $developers->map(function ($dev) use ($project, $dateFrom, $dateTo) {
            // Dùng task_histories để xác định chính xác ai đã làm việc,
            // tránh mất credit khi assigned_to bị ghi đè lúc chuyển trạng thái.

            // Dev hoàn thành phần việc của mình = chuyển sang ready_to_test (hoặc done nếu được phép).
            // Không dùng assigned_to vì bị ghi đè khi task chuyển sang tester.
            $makeHist = fn() => TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
                ->where('tasks.project_id', $project->id)
                ->where('task_histories.changed_by', $dev->id)
                ->select('tasks.id');

            // ── Task (non-bug)
            $taskHistQ = $makeHist()
                ->where('tasks.type', '!=', Task::TYPE_BUG)
                ->whereIn('task_histories.to_status', [Task::STATUS_READY_TO_TEST, Task::STATUS_DONE]);
            if ($dateFrom) $taskHistQ->whereDate('task_histories.created_at', '>=', $dateFrom);
            if ($dateTo)   $taskHistQ->whereDate('task_histories.created_at', '<=', $dateTo);
            $completedTaskIds = $taskHistQ->distinct()->pluck('tasks.id');

            // Task còn đang được giao cho dev (chưa hoàn thành), để total phản ánh đủ
            $openTaskIds = Task::where('project_id', $project->id)
                ->where('type', '!=', Task::TYPE_BUG)
                ->where('assigned_to', $dev->id)
                ->where('status', '!=', Task::STATUS_DONE)
                ->pluck('id');

            $allTaskIds = $completedTaskIds->merge($openTaskIds)->unique();
            $tasksTotal = $allTaskIds->count();
            // Dev's job is done when task reaches RTT or beyond (tester/PM handles the rest)
            $tasksDone  = Task::whereIn('id', $completedTaskIds)
                ->whereIn('status', [Task::STATUS_READY_TO_TEST, Task::STATUS_REVIEW_APPROVED, Task::STATUS_DONE])
                ->count();

            // ── Bug: dev fix xong = chuyển sang ready_to_test (dev không được mark done trực tiếp)
            $bugHistQ = $makeHist()
                ->where('tasks.type', Task::TYPE_BUG)
                ->whereIn('task_histories.to_status', [Task::STATUS_READY_TO_TEST, Task::STATUS_DONE]);
            if ($dateFrom) $bugHistQ->whereDate('task_histories.created_at', '>=', $dateFrom);
            if ($dateTo)   $bugHistQ->whereDate('task_histories.created_at', '<=', $dateTo);
            $fixedBugIds = $bugHistQ->distinct()->pluck('tasks.id');

            // Bug còn đang giao cho dev chưa xong
            $openBugIds = Task::where('project_id', $project->id)
                ->where('type', Task::TYPE_BUG)
                ->where('assigned_to', $dev->id)
                ->where('status', '!=', Task::STATUS_DONE)
                ->pluck('id');

            $allBugIds    = $fixedBugIds->merge($openBugIds)->unique();
            $bugsTotal    = $allBugIds->count();
            // Bug "resolved" by dev = moved to RTT (tester verifies and closes)
            $bugsResolved = Task::whereIn('id', $fixedBugIds)
                ->whereIn('status', [Task::STATUS_READY_TO_TEST, Task::STATUS_DONE])
                ->count();

            // ── Retest: task/bug dev đã làm mà bị trả lại in_progress
            $allDevTaskIds = $allTaskIds->merge($allBugIds)->unique()->values();
            $retestCount   = 0;
            if ($allDevTaskIds->isNotEmpty()) {
                $retestQ = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
                    ->where('tasks.project_id', $project->id)
                    ->whereIn('tasks.id', $allDevTaskIds)
                    ->where('task_histories.from_status', Task::STATUS_READY_TO_TEST)
                    ->where('task_histories.to_status', Task::STATUS_IN_PROGRESS);
                if ($dateFrom) $retestQ->whereDate('task_histories.created_at', '>=', $dateFrom);
                if ($dateTo)   $retestQ->whereDate('task_histories.created_at', '<=', $dateTo);
                $retestCount = $retestQ->count();
            }

            // ── Avg estimated hours: trung bình estimated_hours của task dev đã hoàn thành
            $allCompletedIds = $completedTaskIds->merge($fixedBugIds)->unique()->values();
            $avgFixHours = $allCompletedIds->isEmpty() ? null :
                Task::whereIn('id', $allCompletedIds)
                    ->whereNotNull('estimated_hours')
                    ->where('estimated_hours', '>', 0)
                    ->avg('estimated_hours');
            $avgFixHours = $avgFixHours !== null ? round((float) $avgFixHours, 1) : null;

            $totalWork = $tasksTotal + $bugsTotal;
            $totalDone = $tasksDone  + $bugsResolved;

            return (object)[
                'user'          => $dev,
                'tasks_total'   => $tasksTotal,
                'tasks_done'    => $tasksDone,
                'bugs_total'    => $bugsTotal,
                'bugs_resolved' => $bugsResolved,
                'total_work'    => $totalWork,
                'total_done'    => $totalDone,
                'retest_count'  => $retestCount,
                'avg_fix_hours' => $avgFixHours !== null ? round((float) $avgFixHours, 1) : null,
                'done_rate'     => $totalWork > 0 ? round($totalDone / $totalWork * 100) : null,
            ];
        });

        // ── Tester stats ─────────────────────────────────────────────────────
        $testers = $project->testers()->orderBy('full_name')->get();
        if (!$isPmView) {
            $testers = $testers->filter(fn($t) => $t->id === $user->id)->values();
        }

        $testerStats = $testers->map(function ($tester) use ($project, $bugTotal, $dateFrom, $dateTo) {
            $foundQ = Task::where('project_id', $project->id)
                ->where('type', Task::TYPE_BUG)
                ->where('created_by', $tester->id);
            if ($dateFrom) $foundQ->whereDate('created_at', '>=', $dateFrom);
            if ($dateTo)   $foundQ->whereDate('created_at', '<=', $dateTo);

            $bugsFound  = (clone $foundQ)->count();
            $bugsClosed = (clone $foundQ)->where('status', Task::STATUS_DONE)->count();

            // Tasks/bugs the tester verified (moved to done or review_approved for root tasks)
            $verifiedQ = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
                ->where('tasks.project_id', $project->id)
                ->where('task_histories.changed_by', $tester->id)
                ->whereIn('task_histories.to_status', [Task::STATUS_REVIEW_APPROVED, Task::STATUS_DONE])
                ->select('tasks.id');
            if ($dateFrom) $verifiedQ->whereDate('task_histories.created_at', '>=', $dateFrom);
            if ($dateTo)   $verifiedQ->whereDate('task_histories.created_at', '<=', $dateTo);
            $tasksVerified = $verifiedQ->distinct()->pluck('tasks.id')->count();

            return (object)[
                'user'           => $tester,
                'bugs_found'     => $bugsFound,
                'bugs_closed'    => $bugsClosed,
                'tasks_verified' => $tasksVerified,
                'close_rate'     => $bugsFound > 0 ? round($bugsClosed / $bugsFound * 100) : null,
                'dre'            => $bugTotal > 0 ? round($bugsFound / $bugTotal * 100, 1) : null,
            ];
        });

        // ── Chart data ───────────────────────────────────────────────────────
        $chartBugStatus = ['open' => $bugOpen, 'closed' => $bugClosed];

        $chartRetest = [
            'labels' => $devStats->pluck('user')->map(fn($u) => $u->full_name)->values()->toArray(),
            'data'   => $devStats->pluck('retest_count')->values()->toArray(),
        ];

        return view('quality.show', compact(
            'user', 'project', 'role', 'isPmView',
            'dateFrom', 'dateTo',
            'taskTotal', 'taskDone', 'taskOpen',
            'bugTotal', 'bugOpen', 'bugClosed', 'dre', 'retestTotal',
            'devStats', 'testerStats',
            'chartBugStatus', 'chartRetest'
        ));
    }
}
