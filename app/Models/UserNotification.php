<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id', 'task_id', 'type', 'title', 'body', 'url', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function task(): BelongsTo { return $this->belongsTo(Task::class); }

    public function isUnread(): bool { return $this->read_at === null; }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public static function notifyUsers(array $userIds, array $data): void
    {
        $now = now();
        $rows = array_map(fn($uid) => array_merge($data, [
            'user_id'    => $uid,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $userIds);

        if (!empty($rows)) {
            static::insert($rows);
        }
    }
}
