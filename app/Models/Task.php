<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'created_by',
        'status',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany {
        return $this->belongsToMany(User::class, 'task_user')
            ->withTimestamps();
    }

    public function comments(): HasMany {
        return $this->hasMany(Comment::class);
    }

    public function tags(): BelongsToMany {
        return $this->belongsToMany(Tag::class, 'tag_task')
            ->withTimestamps();
    }
}