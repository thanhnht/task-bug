<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, User, UserNotification};
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    // ── Danh sách task chính (root) của project ───────────────────────────
    public function index(Request $request, Project $project)
    {
        $this->mustBeMember($project);

        $query = $project->tasks()
            ->with(['assignee', 'creator'])
            ->withCount([
                'children',
                'children as pending_children_count' => fn($q) => $q->whereNotIn('status', ['done']),
            ]);

        // Type filter tách Bug Production vs Bug từ task
        $typeParam = $request->input('type');
        if ($typeParam === 'production_bug') {
            $query->where('type', Task::TYPE_BUG)->where('is_production_bug', true)->whereNull('parent_id');
        } elseif ($typeParam === 'bug') {
            $query->where('type', Task::TYPE_BUG)
                  ->where(fn($q) => $q->where('is_production_bug', false)->orWhereNull('is_production_bug'))
                  ->whereNotNull('parent_id');
        } else {
            $query->whereNull('parent_id');
            if ($request->filled('type')) $query->where('type', $typeParam);
        }

        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->assigned_to);
        if ($request->filled('date_from'))   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))     $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search'))      $query->where(function ($q) use ($request) {
            $q->where('title', 'like', "%{$request->search}%")
              ->orWhere('code', 'like', "%{$request->search}%");
        });

        $role = $project->roleOf(Auth::user());
        if ($role === Project::ROLE_DEVELOPER) {
            $query->where('assigned_to', Auth::id());
        }

        $members = $project->members()->orderBy('full_name')->get();
        $tasks   = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('tasks.index', compact('project', 'tasks', 'role', 'members'));
    }

    // ── Tạo task chính ────────────────────────────────────────────────────
    public function create(Project $project)
    {
        $this->mustBeMember($project);
        /** @var User $user */
        $user    = Auth::user();
        $role    = $project->roleOf($user);
        $members = $project->members()->orderBy('full_name')->get();
        $allTasks = $project->tasks()->orderBy('code')->get(['id', 'code', 'title', 'type', 'status', 'parent_id']);
        return view('tasks.create', compact('project', 'members', 'role', 'allTasks'));
    }

    public function store(Request $request, Project $project)
    {
        $this->mustBeMember($project);
        /** @var User $user */
        $user = Auth::user();
        $role = $project->roleOf($user);

        $type = $request->input('type', Task::TYPE_TASK);

        // Task thường chỉ PM tạo được; bug thì ai cũng tạo được
        if ($type !== Task::TYPE_BUG && $role !== Project::ROLE_PM && !$user->isAdmin()) {
            abort(403, 'Chỉ PM mới có thể tạo task thường.');
        }

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'priority'        => 'required|in:low,medium,high,critical',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:999',
            'assigned_to'     => ['nullable', 'exists:users,id'],
            'linked_task_id'  => ['nullable', 'exists:tasks,id'],
        ]);

        $linkedTaskId   = $data['linked_task_id'] ?? null;
        $isProductionBug = $type === Task::TYPE_BUG && $linkedTaskId
                           && ($role === Project::ROLE_PM || $user->isAdmin());

        $task = $project->tasks()->create([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'priority'         => $data['priority'],
            'start_date'       => $data['start_date'] ?? null,
            'due_date'         => $data['due_date'] ?? null,
            'estimated_hours'  => $data['estimated_hours'] ?? null,
            'assigned_to'      => $data['assigned_to'] ?? null,
            'code'             => Task::nextCode(),
            'type'             => $type,
            'parent_id'        => null,
            'status'           => Task::STATUS_TODO,
            'created_by'       => Auth::id(),
            'is_production_bug' => $isProductionBug,
            'linked_story_id'  => $isProductionBug ? $linkedTaskId : null,
        ]);

        $task->histories()->create([
            'from_status' => null,
            'to_status'   => Task::STATUS_TODO,
            'note'        => 'Task được tạo',
            'changed_by'  => Auth::id(),
        ]);

        if ($isProductionBug) {
            KpiService::deductForProductionBug($task);
        }

        if ($task->assigned_to) {
            $this->notifyAssigned($task, $project, $task->assigned_to);
        }

        return redirect()->route('projects.tasks.show', [$project, $task])
                         ->with('success', "Task <strong>{$task->code}</strong> đã được tạo.");
    }

    // ── Chi tiết task ─────────────────────────────────────────────────────
    public function show(Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        $task->load([
            'parent',
            'creator', 'assignee', 'confirmer',
            'children.assignee', 'children.creator',
            'histories.actor',
            'linkedStory.histories.actor',
            'comments.user', 'comments.attachments',
        ]);

        $role       = $project->roleOf(Auth::user());
        $allMembers = $project->members()->orderBy('full_name')->get();
        $members    = $allMembers->filter(fn($m) => in_array($m->pivot->role, ['pm', 'developer']));
        $testers    = $allMembers->filter(fn($m) => $m->pivot->role === 'tester');
        $transitions = $task->nextTransitions(Auth::user());

        // Stories đã Done trong project (cho production bug selector)
        $doneStories = $project->tasks()
            ->whereNull('parent_id')
            ->where('status', Task::STATUS_DONE)
            ->where('id', '!=', $task->id)
            ->orderBy('code')
            ->get(['id', 'code', 'title']);

        // Dev gốc cho modal Fail (Pass/Fail UX)
        $devUser = null;
        $devId = KpiService::findDevId($task);
        if ($devId) {
            $devUser = User::find($devId, ['id', 'full_name']);
        }

        return view('tasks.show', compact('project', 'task', 'role', 'members', 'testers', 'allMembers', 'transitions', 'doneStories', 'devUser'));
    }

    // ── Cập nhật task chính ───────────────────────────────────────────────
    public function update(Request $request, Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        $user = Auth::user();
        $isCreator = $task->created_by === $user->id;
        if (!$user->isAdmin() && !in_array($project->roleOf($user), [Project::ROLE_PM, Project::ROLE_DEVELOPER]) && !$isCreator) {
            abort(403, 'Bạn không có quyền chỉnh sửa task này.');
        }

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'priority'        => 'required|in:low,medium,high,critical',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:999',
            'note'            => 'nullable|string|max:500',
            'assigned_to'     => [
                'nullable', 'exists:users,id',
                function ($_attr, $value, $fail) use ($project) {
                    if ($value && !$project->hasMember(User::find($value))) {
                        $fail('Người được giao phải là thành viên của dự án.');
                    }
                },
            ],
        ]);

        $note = $data['note'] ?? null;
        unset($data['note']);

        // Ghi lại các thay đổi quan trọng
        $changes = [];

        $assigneeChanged = array_key_exists('assigned_to', $data) && $data['assigned_to'] != $task->assigned_to;
        $newAssigneeId   = $assigneeChanged ? ($data['assigned_to'] ?? null) : null;

        if ($assigneeChanged) {
            $oldName = $task->assignee?->full_name ?? 'Chưa giao';
            $newName = $data['assigned_to'] ? User::find($data['assigned_to'])?->full_name : 'Chưa giao';
            $changes[] = "Assign: {$oldName} → {$newName}";
        }
        if (isset($data['priority']) && $data['priority'] !== $task->priority) {
            $changes[] = "Ưu tiên: {$task->priorityLabel()} → " . (Task::PRIORITY_LABELS[$data['priority']] ?? $data['priority']);
        }
        if (isset($data['due_date']) && $data['due_date'] != $task->due_date?->format('Y-m-d')) {
            $old = $task->due_date?->format('d/m/Y') ?? '—';
            $new = $data['due_date'] ? \Carbon\Carbon::parse($data['due_date'])->format('d/m/Y') : '—';
            $changes[] = "Deadline: {$old} → {$new}";
        }

        $historyNote = collect([$note, implode('; ', $changes)])->filter()->implode(' — ');

        $task->update($data);

        if ($newAssigneeId) {
            $this->notifyAssigned($task, $project, $newAssigneeId);
        }

        $task->histories()->create([
            'from_status' => $task->status,
            'to_status'   => $task->status,
            'note'        => $historyNote ?: 'Cập nhật thông tin task.',
            'changed_by'  => Auth::id(),
        ]);

        return back()->with('success', 'Task đã được cập nhật.');
    }

    // ── Chuyển trạng thái task ────────────────────────────────────────────
    public function transition(Request $request, Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'status'      => 'required|string',
            'note'        => 'nullable|string|max:500',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldAssigneeId = $task->assigned_to;

        if ($request->filled('assigned_to')) {
            $assignee  = User::find($request->assigned_to);
            $actorRole = $project->roleOf(Auth::user());

            if ($assignee && $project->hasMember($assignee)) {
                $assigneeRole = $project->roleOf($assignee);

                // Bất kỳ role nào cũng có thể giao cho Tester khi chuyển sang ready_to_test
                if ($request->status === Task::STATUS_READY_TO_TEST
                    && $assigneeRole === 'tester') {
                    $task->update(['assigned_to' => $assignee->id]);
                }
                // Tester/PM giao lại cho PM hoặc Developer khi đang ở ready_to_test
                elseif ($task->status === Task::STATUS_READY_TO_TEST
                    && in_array($assigneeRole, ['pm', 'developer'])) {
                    $task->update(['assigned_to' => $assignee->id]);
                }
            }
        }

        $oldStatus = $task->status;
        $result    = $task->transitionTo($request->status, Auth::user(), $request->note);

        if (!$result['ok']) {
            return back()->withErrors(['transition' => $result['message']]);
        }

        // Notify testers when a bug moves to ready_to_test (handles assignee notification too)
        if ($task->type === Task::TYPE_BUG && $request->status === Task::STATUS_READY_TO_TEST) {
            $this->notifyTestersForBug($task, $project);
        }

        // Notify new assignee if assignment changed (skip bug→RTT: already handled above)
        $task->refresh();
        $newAssigneeId = $task->assigned_to;
        $isBugToRTT = $task->type === Task::TYPE_BUG && $request->status === Task::STATUS_READY_TO_TEST;
        if (!$isBugToRTT && $newAssigneeId && $newAssigneeId !== $oldAssigneeId) {
            $this->notifyAssigned($task, $project, $newAssigneeId);
        }

        // Notify PMs when a story moves to review_approved
        if ($task->isMainTask() && $request->status === Task::STATUS_REVIEW_APPROVED) {
            $this->notifyPmsForReviewApproved($task, $project);
        }

        // ── KPI deductions ────────────────────────────────────────────────
        $task->refresh();
        if ($request->status === Task::STATUS_DONE) {
            KpiService::deductForLateness($task);
        }
        if ($oldStatus === Task::STATUS_READY_TO_TEST
            && in_array($request->status, [Task::STATUS_REVIEW_APPROVED, Task::STATUS_DONE])) {
            KpiService::deductForRttSoak($task, Auth::id());
        }

        return back()->with('success', $result['message']);
    }

    // ── Tạo task con (bất kỳ thành viên, không giới hạn tầng) ────────────
    public function storeChild(Request $request, Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        $isProductionBug = $request->boolean('is_production_bug');

        // Bug thường chỉ được tạo khi task đang ở RTT; production bug không bị hạn chế
        if ($request->input('type') === Task::TYPE_BUG
            && !$isProductionBug
            && $task->status !== Task::STATUS_READY_TO_TEST) {
            return back()->withErrors([
                'child_error' => 'Bug chỉ có thể tạo khi task đang ở trạng thái Ready to Test.',
            ]);
        }

        // Chỉ PM hoặc Tester được tạo Production Bug
        if ($isProductionBug) {
            $actorRole = $project->roleOf(Auth::user());
            if (!Auth::user()->isAdmin() && !in_array($actorRole, ['pm', 'tester'])) {
                return back()->withErrors(['child_error' => 'Chỉ PM hoặc Tester mới có thể tạo Production Bug.']);
            }
        }

        $data = $request->validate([
            'type'              => 'required|in:task,subtask,bug,research,fix,test',
            'title'             => 'required|string|max:200',
            'description'       => 'nullable|string|max:2000',
            'estimated_hours'   => 'nullable|numeric|min:0.5|max:999',
            'start_date'        => 'nullable|date',
            'due_date'          => 'nullable|date|after_or_equal:start_date',
            'is_production_bug' => 'nullable|boolean',
            'linked_story_id'   => [
                'nullable',
                'required_if:is_production_bug,1',
                'exists:tasks,id',
            ],
            'assigned_to'       => [
                'nullable', 'exists:users,id',
                function ($_attr, $value, $fail) use ($project) {
                    if ($value && !$project->hasMember(User::find($value))) {
                        $fail('Người được giao phải là thành viên của dự án.');
                    }
                },
            ],
        ]);

        // Auto-set due_date cho bug dựa trên priority (SLA)
        if ($data['type'] === Task::TYPE_BUG && empty($data['due_date'])) {
            $slaDays = match($task->priority) {
                'critical' => 0,
                'high'     => 1,
                default    => 2,
            };
            $data['due_date'] = now()->addDays($slaDays)->toDateString();
        }

        $child = $project->tasks()->create([
            'code'              => Task::nextCode(),
            'parent_id'         => $task->id,
            'type'              => $data['type'],
            'is_production_bug' => $data['is_production_bug'] ?? false,
            'linked_story_id'   => $data['linked_story_id'] ?? null,
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'estimated_hours'   => $data['estimated_hours'] ?? null,
            'start_date'        => $data['start_date'] ?? null,
            'due_date'          => $data['due_date'] ?? null,
            'assigned_to'       => $data['assigned_to'] ?? null,
            'priority'          => $task->priority,
            'status'            => Task::STATUS_TODO,
            'created_by'        => Auth::id(),
        ]);

        if ($child->assigned_to) {
            $this->notifyAssigned($child, $project, $child->assigned_to);
        }

        // ── KPI deductions ────────────────────────────────────────────────
        if ($child->type === Task::TYPE_BUG) {
            KpiService::deductForBugCreated($task);
            if ($child->is_production_bug) {
                KpiService::deductForProductionBug($child);
            }
        }

        return back()->with('success', 'Task con đã được thêm.');
    }

    // ── Chuyển trạng thái task con ────────────────────────────────────────
    public function transitionChild(Request $request, Project $project, Task $task, Task $child)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);
        abort_if($child->parent_id !== $task->id, 404);

        $request->validate([
            'status' => 'required|string',
            'note'   => 'nullable|string|max:500',
        ]);

        $oldChildStatus = $child->status;
        $result         = $child->transitionTo($request->status, Auth::user(), $request->note);

        if (!$result['ok']) {
            return back()->withErrors(['child_transition' => $result['message']]);
        }

        // ── KPI deductions ────────────────────────────────────────────────
        $child->refresh();
        if ($request->status === Task::STATUS_DONE) {
            KpiService::deductForLateness($child);
        }
        if ($oldChildStatus === Task::STATUS_READY_TO_TEST
            && in_array($request->status, [Task::STATUS_REVIEW_APPROVED, Task::STATUS_DONE])) {
            KpiService::deductForRttSoak($child, Auth::id());
        }

        return back()->with('success', $result['message']);
    }

    // ── Báo lỗi từ Ready to Test ──────────────────────────────────────────
    public function reportBug(Request $request, Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$project->isTester($user) && !$user->isAdmin()) {
            abort(403);
        }

        if ($task->status !== Task::STATUS_READY_TO_TEST) {
            return back()->withErrors(['report_error' => 'Task phải ở trạng thái Ready to Test.']);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $slaDays = match($task->priority) {
            'critical' => 0,
            'high'     => 1,
            default    => 2,
        };

        $bug = Task::create([
            'code'        => Task::nextCode(),
            'project_id'  => $task->project_id,
            'parent_id'   => $task->id,
            'type'        => Task::TYPE_BUG,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'priority'    => $task->priority,
            'status'      => Task::STATUS_TODO,
            'assigned_to' => KpiService::findDevId($task),
            'created_by'  => $user->id,
            'due_date'    => now()->addDays($slaDays)->toDateString(),
        ]);

        $task->transitionTo(Task::STATUS_IN_PROGRESS, $user, 'Tester báo lỗi: ' . $bug->code);

        KpiService::deductForBugCreated($task);

        return redirect()->route('projects.tasks.show', [$project, $task])
            ->with('success', 'Đã tạo ' . $bug->code . ' và gán cho developer.');
    }

    // ── Notification helpers ──────────────────────────────────────────────
    private function notifyAssigned(Task $task, Project $project, int $assigneeId): void
    {
        if ($assigneeId === Auth::id()) return;

        $actor = Auth::user();
        $url   = route('projects.tasks.show', [$project, $task]);
        UserNotification::notifyUsers([$assigneeId], [
            'task_id' => $task->id,
            'type'    => 'assigned',
            'title'   => "Bạn được giao: [{$task->code}]",
            'body'    => "{$actor->full_name} đã giao {$task->typeLabel()} \"{$task->title}\" cho bạn.",
            'url'     => $url,
        ]);
    }

    private function notifyTestersForBug(Task $task, Project $project): void
    {
        $actor = Auth::user();

        // Prefer assigned tester; fall back to all project testers
        $assignee = $task->assignee;
        if ($assignee && $project->roleOf($assignee) === 'tester') {
            $recipients = [$assignee->id];
        } else {
            $recipients = $project->testers()->pluck('users.id')->toArray();
        }

        // Never notify yourself
        $recipients = array_filter($recipients, fn($id) => $id !== $actor->id);

        if (empty($recipients)) return;

        $url = route('projects.tasks.show', [$project, $task]);
        UserNotification::notifyUsers(array_values($recipients), [
            'task_id' => $task->id,
            'type'    => 'bug_ready_to_test',
            'title'   => "Bug chờ kiểm tra: [{$task->code}]",
            'body'    => "{$actor->full_name} đã chuyển bug \"{$task->title}\" sang Ready to Test.",
            'url'     => $url,
        ]);
    }

    private function notifyPmsForReviewApproved(Task $task, Project $project): void
    {
        $actor      = Auth::user();
        $recipients = $project->pms()->pluck('users.id')
            ->filter(fn($id) => $id !== $actor->id)
            ->toArray();

        if (empty($recipients)) return;

        $url = route('projects.tasks.show', [$project, $task]);
        UserNotification::notifyUsers(array_values($recipients), [
            'task_id' => $task->id,
            'type'    => 'review_approved',
            'title'   => "Story đã được phê duyệt: [{$task->code}]",
            'body'    => "{$actor->full_name} đã phê duyệt story \"{$task->title}\" — sẵn sàng nghiệm thu Done.",
            'url'     => $url,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function mustBeMember(Project $project): void
    {
        if (!$project->hasMember(Auth::user())) {
            abort(403, 'Bạn không phải thành viên của dự án này.');
        }
    }

    private function mustHaveRole(Project $project, array $roles): void
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if ($user->isAdmin()) return;

        if (!in_array($project->roleOf($user), $roles)) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }
    }
}
