<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //
    protected $fillable = [
        'task_id',
        'user_id',
        'message',
    ];

    public function task(): BelongsTo
    {
        // Eloquent assumes:
        // 1. Foreign Key on THIS table: 'task_id' (based on the method name 'task')
        // 2. Local Key on the target model (Task): 'id'
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        // Eloquent assumes:
        // 1. Foreign Key on THIS table: 'user_id' (based on the method name 'user')
        // 2. Local Key on the target model (User): 'id'
        return $this->belongsTo(User::class);
    }
}
