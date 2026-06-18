<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CommentAttachment extends Model
{
    protected $fillable = ['comment_id', 'original_name', 'stored_path', 'size', 'mime_type'];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function url(): string
    {
        return Storage::url($this->stored_path);
    }

    public function formattedSize(): string
    {
        $kb = $this->size / 1024;
        return $kb < 1024 ? round($kb, 1) . ' KB' : round($kb / 1024, 1) . ' MB';
    }
}
