<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    protected $fillable = [
        'task_id', 'from_status', 'to_status', 'note', 'changed_by',
    ];

    public function task(): BelongsTo  { return $this->belongsTo(Task::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }

    public function fromLabel(): string
    {
        return Task::STATUS_LABELS[$this->from_status] ?? '—';
    }

    public function toLabel(): string
    {
        return Task::STATUS_LABELS[$this->to_status] ?? $this->to_status;
    }
}
