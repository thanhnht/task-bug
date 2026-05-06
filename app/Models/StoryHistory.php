<?php
// ══════════════════════════════════════════════════════════
// app/Models/StoryHistory.php
// ══════════════════════════════════════════════════════════
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryHistory extends Model
{
    protected $fillable = [
        'story_id', 'from_status', 'to_status', 'note', 'changed_by',
    ];

    public function story(): BelongsTo  { return $this->belongsTo(Story::class); }
    public function actor(): BelongsTo  { return $this->belongsTo(User::class, 'changed_by'); }

    public function fromLabel(): string
    {
        return Story::STATUS_LABELS[$this->from_status] ?? '—';
    }

    public function toLabel(): string
    {
        return Story::STATUS_LABELS[$this->to_status] ?? $this->to_status;
    }
}




