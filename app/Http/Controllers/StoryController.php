<?php

namespace App\Http\Controllers;

use App\Models\{Project, Story, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoryController extends Controller
{
    // ── Danh sách story trong project ─────────────────────────────────────
    public function index(Request $request, Project $project)
    {
        $this->mustBeMember($project);

        $query = $project->stories()
            ->with(['developer', 'creator'])
            ->withCount([
                'subtasks',
                'bugs',
                'bugs as open_bugs_count' => fn($q) => $q->whereNotIn('status', ['closed']),
            ]);

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('priority')) $query->where('priority', $request->priority);
        if ($request->filled('search'))   $query->where(function($q) use ($request) {
            $q->where('title', 'like', "%{$request->search}%")
              ->orWhere('code', 'like', "%{$request->search}%");
        });

        // Developer chỉ thấy story của mình trong project
        $role = $project->roleOf(Auth::user());
        if ($role === Project::ROLE_DEVELOPER) {
            $query->where('assigned_to', Auth::id());
        }

        $stories = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('stories.index', compact('project', 'stories', 'role'));
    }

    // ── Tạo Story ─────────────────────────────────────────────────────────
    public function create(Project $project)
    {
        $this->mustHaveRole($project, [Project::ROLE_PM]);
        $developers = $project->developers()->orderBy('full_name')->get();
        return view('stories.create', compact('project', 'developers'));
    }

    public function store(Request $request, Project $project)
    {
        $this->mustHaveRole($project, [Project::ROLE_PM]);

        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'required|in:low,medium,high,critical',
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                // Chỉ giao cho developer của project này
                function($attr, $value, $fail) use ($project) {
                    if ($value && !$project->isDeveloper(User::find($value))) {
                        $fail('Người được giao phải là Developer của dự án.');
                    }
                },
            ],
        ]);

        $story = $project->stories()->create([
            ...$data,
            'code'       => Story::nextCode(),
            'status'     => Story::STATUS_TODO,
            'created_by' => Auth::id(),
        ]);

        $story->histories()->create([
            'from_status' => null,
            'to_status'   => Story::STATUS_TODO,
            'note'        => 'Story được tạo',
            'changed_by'  => Auth::id(),
        ]);

        return redirect()->route('projects.stories.show', [$project, $story])
                         ->with('success', "Story <strong>{$story->code}</strong> đã được tạo.");
    }

    // ── Chi tiết Story ────────────────────────────────────────────────────
    public function show(Project $project, Story $story)
    {
        $this->mustBeMember($project);
        abort_if($story->project_id !== $project->id, 404);

        $story->load([
            'creator', 'developer', 'confirmer',
            
            'histories.actor',
        ]);

        $role        = $project->roleOf(Auth::user());
        $developers  = $project->developers()->orderBy('full_name')->get();
        $transitions = $story->nextTransitions(Auth::user());

        return view('stories.show', compact('project', 'story', 'role', 'developers', 'transitions'));
    }

    // ── Cập nhật Story ────────────────────────────────────────────────────
    public function update(Request $request, Project $project, Story $story)
    {
        $this->mustHaveRole($project, [Project::ROLE_PM]);
        abort_if($story->project_id !== $project->id, 404);

        // Không cho edit Story đã Done
        if ($story->status === Story::STATUS_DONE) {
            return back()->withErrors(['error' => 'Không thể chỉnh sửa Story đã Done.']);
        }

        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'required|in:low,medium,high,critical',
            'assigned_to' => [
                'nullable', 'exists:users,id',
                function($attr, $value, $fail) use ($project) {
                    if ($value && !$project->isDeveloper(User::find($value))) {
                        $fail('Người được giao phải là Developer của dự án.');
                    }
                },
            ],
        ]);

        $story->update($data);

        return back()->with('success', 'Story đã được cập nhật.');
    }

    // ── Chuyển trạng thái ─────────────────────────────────────────────────
    public function transition(Request $request, Project $project, Story $story)
    {
        $this->mustBeMember($project);
        abort_if($story->project_id !== $project->id, 404);

        $request->validate([
            'status' => 'required|string',
            'note'   => 'nullable|string|max:500',
        ]);

        $result = $story->transitionTo($request->status, Auth::user(), $request->note);

        if (!$result['ok']) {
            return back()->withErrors(['transition' => $result['message']]);
        }

        return back()->with('success', $result['message']);
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
        if ($user->isAdmin()) return;

        if (!in_array($project->roleOf($user), $roles)) {
            abort(403, 'Bạn không có quyền thực hiện thao tác này.');
        }
    }
}
