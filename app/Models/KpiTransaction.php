<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTransaction extends Model
{
    protected $fillable = [
        'user_id', 'project_id', 'task_id', 'points', 'reason', 'period_month',
    ];

    protected $casts = ['points' => 'float'];

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function task(): BelongsTo    { return $this->belongsTo(Task::class); }
}
