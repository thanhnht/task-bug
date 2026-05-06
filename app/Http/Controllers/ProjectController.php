<?php

namespace App\Http\Controllers;

use App\Models\{Project, User, Story};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    // ── Danh sách project ────────────────────────────────────────────────
    public function index()
    {
        $projects = Project::accessibleBy(Auth::user())
            ->withCount('stories')
            ->with(['members', 'creator'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('projects.index', compact('projects'));
    }

    // ── Tạo project ──────────────────────────────────────────────────────
    public function create()
    {
        $this->mustBeAdminOrHaveRole(null, ['admin']);
        $employees = User::where('is_active', true)->where('role', 'employee')->orderBy('full_name')->get();
        return view('projects.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $this->mustBeAdminOrHaveRole(null, ['admin']);

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            // Thành viên: mảng [{user_id, role}]
            'members'           => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.role'    => 'required|in:pm,developer,tester',
        ], [
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu.',
        ]);

        $project = Project::create([
            'code'        => Project::nextCode(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date'  => $data['start_date'] ?? null,
            'end_date'    => $data['end_date'] ?? null,
            'status'      => Project::STATUS_ACTIVE,
            'created_by'  => Auth::id(),
        ]);

        // Gán thành viên
        $syncData = [];
        foreach ($data['members'] ?? [] as $m) {
            $syncData[$m['user_id']] = ['role' => $m['role'], 'joined_at' => now()];
        }
        if ($syncData) {
            $project->members()->sync($syncData);
        }

        return redirect()->route('projects.show', $project)
                         ->with('success', "Dự án <strong>{$project->code}</strong> đã được tạo.");
    }

    // ── Chi tiết project ─────────────────────────────────────────────────
    public function show(Project $project)
    {
        $this->mustBeMember($project);

        $project->load(['creator', 'members', 'stories.developer']);

        $user = Auth::user();
        $role = $project->roleOf($user);

        // Stats
        $stats = [
            'total'    => $project->stories->count(),
            'todo'     => $project->stories->where('status', 'todo')->count(),
            'progress' => $project->stories->where('status', 'in_progress')->count(),
            'review'   => $project->stories->where('status', 'ready_to_review')->count(),
            'done'     => $project->stories->where('status', 'done')->count(),
        ];

        $employees = User::where('is_active', true)->where('role', 'employee')->orderBy('full_name')->get();

        return view('projects.show', compact('project', 'role', 'stats', 'employees'));
    }

    // ── Cập nhật project ─────────────────────────────────────────────────
    public function edit(Project $project)
    {
        $this->mustBeAdminOrHaveRole($project, ['admin', 'pm']);
        $employees = User::where('is_active', true)->orderBy('full_name')->get();
        return view('projects.edit', compact('project', 'employees'));
    }

    public function update(Request $request, Project $project)
    {
        $this->mustBeAdminOrHaveRole($project, ['admin', 'pm']);

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'status'      => Rule::in(array_keys(Project::STATUS_LABELS)),
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)
                         ->with('success', 'Thông tin dự án đã được cập nhật.');
    }

    // ── Quản lý thành viên ───────────────────────────────────────────────
    public function addMember(Request $request, Project $project)
    {
        $this->mustBeAdminOrHaveRole($project, ['admin', 'pm']);

        $data = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('project_members')->where(fn($q) => $q->where('project_id', $project->id)),
            ],
            'role' => 'required|in:pm,developer,tester',
        ], [
            'user_id.unique' => 'Thành viên này đã có trong dự án.',
        ]);

        $project->members()->attach($data['user_id'], [
            'role'      => $data['role'],
            'joined_at' => now(),
        ]);

        return back()->with('success', 'Đã thêm thành viên vào dự án.');
    }

    public function removeMember(Request $request, Project $project)
    {
        $this->mustBeAdminOrHaveRole($project, ['admin', 'pm']);

        $request->validate(['user_id' => 'required|exists:users,id']);

        // Không cho xoá chính mình nếu là PM duy nhất
        $pmCount = $project->pms()->count();
        $memberRole = $project->roleOf(User::find($request->user_id));

        if ($memberRole === Project::ROLE_PM && $pmCount <= 1) {
            return back()->withErrors(['error' => 'Dự án phải có ít nhất 1 PM.']);
        }

        $project->members()->detach($request->user_id);

        return back()->with('success', 'Đã xoá thành viên khỏi dự án.');
    }

    public function updateMemberRole(Request $request, Project $project)
    {
        $this->mustBeAdminOrHaveRole($project, ['admin', 'pm']);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:pm,developer,tester',
        ]);

        $project->members()->updateExistingPivot($data['user_id'], ['role' => $data['role']]);

        return back()->with('success', 'Đã cập nhật vai trò thành viên.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function mustBeMember(Project $project): void
    {
        if (!$project->hasMember(Auth::user())) {
            abort(403, 'Bạn không phải thành viên của dự án này.');
        }
    }

    private function mustBeAdminOrHaveRole(?Project $project, array $roles): void
    {
        $user = Auth::user();
        if ($user->isAdmin()) return;

        if ($project) {
            if (!in_array($project->roleOf($user), $roles)) {
                abort(403, 'Bạn không có quyền thực hiện thao tác này trong dự án.');
            }
        } else {
            abort(403, 'Chỉ Admin mới có quyền thực hiện thao tác này.');
        }
    }
}
