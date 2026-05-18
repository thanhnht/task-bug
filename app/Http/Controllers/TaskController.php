<?php

namespace App\Http\Controllers;

use App\Models\{Project, Task, User, UserNotification};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    // ── Danh sách task chính (root) của project ───────────────────────────
    public function index(Request $request, Project $project)
    {
        $this->mustBeMember($project);

        $query = $project->tasks()
            ->whereNull('parent_id')
            ->with(['assignee', 'creator'])
            ->withCount([
                'children',
                'children as pending_children_count' => fn($q) => $q->whereNotIn('status', ['done']),
            ]);

        if ($request->filled('status'))      $query->where('status', $request->status);
        if ($request->filled('priority'))    $query->where('priority', $request->priority);
        if ($request->filled('type'))        $query->where('type', $request->type);
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
        $tasks   = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('tasks.index', compact('project', 'tasks', 'role', 'members'));
    }

    // ── Tạo task chính ────────────────────────────────────────────────────
    public function create(Project $project)
    {
        $this->mustHaveRole($project, [Project::ROLE_PM]);
        $members = $project->members()->orderBy('full_name')->get();
        return view('tasks.create', compact('project', 'members'));
    }

    public function store(Request $request, Project $project)
    {
        $this->mustHaveRole($project, [Project::ROLE_PM]);

        $data = $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'priority'        => 'required|in:low,medium,high,critical',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:999',
            'assigned_to'     => [
                'nullable', 'exists:users,id',
                function ($_attr, $value, $fail) use ($project) {
                    if ($value && !$project->isDeveloper(User::find($value))) {
                        $fail('Người được giao phải là Developer của dự án.');
                    }
                },
            ],
        ]);

        $task = $project->tasks()->create([
            ...$data,
            'code'       => Task::nextCode(),
            'type'       => Task::TYPE_TASK,
            'parent_id'  => null,
            'status'     => Task::STATUS_TODO,
            'created_by' => Auth::id(),
        ]);

        $task->histories()->create([
            'from_status' => null,
            'to_status'   => Task::STATUS_TODO,
            'note'        => 'Task được tạo',
            'changed_by'  => Auth::id(),
        ]);

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
        ]);

        $role       = $project->roleOf(Auth::user());
        $allMembers = $project->members()->orderBy('full_name')->get();
        $members    = $allMembers->filter(fn($m) => in_array($m->pivot->role, ['pm', 'developer']));
        $testers    = $allMembers->filter(fn($m) => $m->pivot->role === 'tester');
        $transitions = $task->nextTransitions(Auth::user());

        return view('tasks.show', compact('project', 'task', 'role', 'members', 'testers', 'allMembers', 'transitions'));
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

        if ($task->status === Task::STATUS_DONE) {
            return back()->withErrors(['error' => 'Không thể chỉnh sửa Task đã Done.']);
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

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] != $task->assigned_to) {
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

        // Notify testers when a bug moves to ready_to_test
        if ($task->type === Task::TYPE_BUG && $request->status === Task::STATUS_READY_TO_TEST) {
            $this->notifyTestersForBug($task, $project);
        }

        // Notify PMs when a story moves to review_approved
        if ($task->isMainTask() && $request->status === Task::STATUS_REVIEW_APPROVED) {
            $this->notifyPmsForReviewApproved($task, $project);
        }

        return back()->with('success', $result['message']);
    }

    // ── Tạo task con (bất kỳ thành viên, không giới hạn tầng) ────────────
    public function storeChild(Request $request, Project $project, Task $task)
    {
        $this->mustBeMember($project);
        abort_if($task->project_id !== $project->id, 404);

        // Bug chỉ được tạo khi task đang ở trạng thái Ready to Test
        if ($request->input('type') === Task::TYPE_BUG && $task->status !== Task::STATUS_READY_TO_TEST) {
            return back()->withErrors([
                'child_error' => 'Bug chỉ có thể tạo khi task đang ở trạng thái Ready to Test.',
            ]);
        }

        $data = $request->validate([
            'type'             => 'required|in:task,subtask,bug,research,fix,test',
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string|max:2000',
            'estimated_hours'  => 'nullable|numeric|min:0.5|max:999',
            'start_date'       => 'nullable|date',
            'due_date'         => 'nullable|date|after_or_equal:start_date',
            'assigned_to'      => [
                'nullable', 'exists:users,id',
                function ($_attr, $value, $fail) use ($project) {
                    if ($value && !$project->hasMember(User::find($value))) {
                        $fail('Người được giao phải là thành viên của dự án.');
                    }
                },
            ],
        ]);

        $project->tasks()->create([
            'code'            => Task::nextCode(),
            'parent_id'       => $task->id,
            'type'            => $data['type'],
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'start_date'      => $data['start_date'] ?? null,
            'due_date'        => $data['due_date'] ?? null,
            'assigned_to'     => $data['assigned_to'] ?? null,
            'priority'        => $task->priority,
            'status'          => Task::STATUS_TODO,
            'created_by'      => Auth::id(),
        ]);

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

        $result = $child->transitionTo($request->status, Auth::user(), $request->note);

        if (!$result['ok']) {
            return back()->withErrors(['child_transition' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    // ── Notification helpers ──────────────────────────────────────────────
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
