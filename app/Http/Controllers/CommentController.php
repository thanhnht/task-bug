<?php

namespace App\Http\Controllers;

use App\Models\{Comment, CommentAttachment, Project, Task};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(Request $request, Project $project, Task $task)
    {
        abort_if(!$project->hasMember(Auth::user()), 403);
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'content'       => 'required|string|max:65535',
            'attachments.*' => 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt',
        ]);

        $comment = Comment::create([
            'task_id'  => $task->id,
            'user_id'  => Auth::id(),
            'content'  => $request->input('content'),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('comments/' . $task->id, 'public');
                CommentAttachment::create([
                    'comment_id'    => $comment->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path'   => $path,
                    'size'          => $file->getSize(),
                    'mime_type'     => $file->getMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Đã thêm bình luận.');
    }

    public function uploadImage(Request $request, Project $project, Task $task)
    {
        abort_if(!$project->hasMember(Auth::user()), 403);
        $request->validate(['image' => 'required|image|max:10240']);

        $path = $request->file('image')->store('comments/' . $task->id . '/images', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }

    public function destroy(Project $project, Task $task, Comment $comment)
    {
        abort_if($task->project_id !== $project->id, 404);
        abort_if($comment->task_id !== $task->id, 404);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($comment->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Chỉ người viết hoặc Admin mới được xoá bình luận.');
        }

        foreach ($comment->attachments as $att) {
            Storage::disk('public')->delete($att->stored_path);
        }

        $comment->delete();

        return back()->with('success', 'Đã xoá bình luận.');
    }
}
