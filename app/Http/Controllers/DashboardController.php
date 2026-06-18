<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, TaskHistory, User};
use App\Services\KpiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user     = Auth::user();

        // ── My tasks ────────────────────────────────────────────────────────
        $myTasksQuery = Task::where('assigned_to', $user->id);

        $myTasks = [
            'todo'          => (clone $myTasksQuery)->where('status', Task::STATUS_TODO)->count(),
            'in_progress'   => (clone $myTasksQuery)->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'ready_to_test' => (clone $myTasksQuery)->where('status', Task::STATUS_READY_TO_TEST)->count(),
            'done'          => (clone $myTasksQuery)->where('status', Task::STATUS_DONE)->count(),
        ];

        // ── Projects tôi tham gia ───────────────────────────────────────────
        $projects = Project::accessibleBy($user)
            ->withCount([
                'tasks as total_tasks'    => fn($q) => $q->whereNull('parent_id'),
                'tasks as open_bugs'      => fn($q) => $q->where('type', Task::TYPE_BUG)->where('status', '!=', Task::STATUS_DONE),
            ])
            ->with('members')
            ->orderByDesc('created_at')
            ->get();

        // ── Quality metrics theo từng project ───────────────────────────────
        $projectIds = $projects->pluck('id');

        // Bug stats gom theo project_id
        $bugRows = Task::whereIn('project_id', $projectIds)
            ->where('type', Task::TYPE_BUG)
            ->select(
                'project_id',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'done' then 1 else 0 end) as closed")
            )
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        // Retest (ready_to_test → in_progress) theo project
        $retestRows = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
            ->whereIn('tasks.project_id', $projectIds)
            ->where('task_histories.from_status', Task::STATUS_READY_TO_TEST)
            ->where('task_histories.to_status',   Task::STATUS_IN_PROGRESS)
            ->select('tasks.project_id', DB::raw('count(*) as cnt'))
            ->groupBy('tasks.project_id')
            ->pluck('cnt', 'project_id');

        // Reject từ done (done → in_progress) theo project
        $rejectRows = TaskHistory::join('tasks', 'task_histories.task_id', '=', 'tasks.id')
            ->whereIn('tasks.project_id', $projectIds)
            ->where('task_histories.from_status', Task::STATUS_DONE)
            ->where('task_histories.to_status',   Task::STATUS_IN_PROGRESS)
            ->select('tasks.project_id', DB::raw('count(*) as cnt'))
            ->groupBy('tasks.project_id')
            ->pluck('cnt', 'project_id');

        // Gộp thành quality data theo project
        $qualityByProject = $projects->map(fn($p) => (object)[
            'project'    => $p,
            'bug_total'  => $bugRows->get($p->id)?->total  ?? 0,
            'bug_closed' => $bugRows->get($p->id)?->closed ?? 0,
            'bug_open'   => ($bugRows->get($p->id)?->total ?? 0) - ($bugRows->get($p->id)?->closed ?? 0),
            'retest'     => $retestRows->get($p->id, 0),
            'reject'     => $rejectRows->get($p->id, 0),
        ])->filter(fn($r) => $r->bug_total > 0 || $r->retest > 0 || $r->reject > 0);

        // Tổng để hiện summary cards
        $bugStats = [
            'total'  => $bugRows->sum('total'),
            'open'   => $bugRows->sum('total') - $bugRows->sum('closed'),
            'closed' => $bugRows->sum('closed'),
        ];
        $retestCount    = $retestRows->sum();
        $rejectFromDone = $rejectRows->sum();

        // Hiệu suất xử lý bug theo người được giao
        $bugPerformance = Task::whereIn('project_id', $projectIds)
            ->where('type', Task::TYPE_BUG)
            ->whereNotNull('assigned_to')
            ->select(
                'assigned_to',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'done' then 1 else 0 end) as resolved")
            )
            ->groupBy('assigned_to')
            ->with('assignee:id,full_name')
            ->get();

        // Hoạt động gần đây (20 entries)
        $recentActivity = TaskHistory::whereHas('task', fn($q) => $q->whereIn('project_id', $projectIds))
            ->with(['task:id,code,title,project_id', 'task.project:id,code', 'actor:id,full_name'])
            ->where(fn($q) => $q->where('from_status', '!=', 'to_status')->orWhereNull('from_status'))
            ->latest()
            ->limit(15)
            ->get();

        // ── KPI ─────────────────────────────────────────────────────────────
        $currentMonth      = now()->format('Y-m');
        $myKpiScore        = KpiService::scoreForMonth($user->id, $currentMonth);
        $myKpiTransactions = KpiService::transactionsForMonth($user->id, $currentMonth);

        // Project filter cho KPI section
        $kpiProjectId  = request('kpi_project');
        $kpiProject    = $kpiProjectId ? $projects->firstWhere('id', (int)$kpiProjectId) : null;
        $kpiProjectIds = $kpiProject ? collect([$kpiProject->id]) : $projectIds;

        // Role của user trong project được chọn (hoặc null nếu chưa chọn)
        $roleInKpiProject = $kpiProject ? $kpiProject->roleOf($user) : null;

        // isPmOrAdmin: nếu đã chọn project → check role trong project đó
        //              nếu chưa chọn → check toàn bộ (behavior cũ)
        if ($kpiProject) {
            $isPmOrAdmin = $user->isAdmin() || $roleInKpiProject === 'pm' || $roleInKpiProject === 'admin';
        } else {
            $isPmOrAdmin = $user->isAdmin() || DB::table('project_members')
                ->where('user_id', $user->id)->where('role', 'pm')->exists();
        }

        $teamKpiData = collect();
        if ($isPmOrAdmin) {
            $memberIds = DB::table('project_members')
                ->whereIn('project_id', $kpiProjectIds)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            if (!empty($memberIds)) {
                $scores = KpiService::teamScores($memberIds, $currentMonth);
                $teamKpiData = User::whereIn('id', $memberIds)
                    ->get(['id', 'full_name'])
                    ->map(fn($u) => (object)[
                        'user'  => $u,
                        'score' => $scores[$u->id] ?? KpiService::BASE_SCORE,
                        'role'  => DB::table('project_members')
                            ->where('user_id', $u->id)
                            ->whereIn('project_id', $kpiProjectIds)
                            ->value('role'),
                    ])
                    ->sortBy('score');
            }
        }

        return view('dashboard', compact(
            'user', 'myTasks', 'projects',
            'bugStats', 'retestCount', 'rejectFromDone',
            'qualityByProject', 'bugPerformance', 'recentActivity',
            'myKpiScore', 'myKpiTransactions', 'isPmOrAdmin', 'teamKpiData', 'currentMonth',
            'kpiProject', 'roleInKpiProject'
        ));
    }
}
