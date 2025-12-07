<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProjectRole;

class ProjectUser extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'project_role',
    ];

    protected function casts(): array
    {
        return [
            'project_role' => ProjectRole::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
