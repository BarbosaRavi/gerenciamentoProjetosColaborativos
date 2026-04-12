<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model {

    use HasFactory;

    protected $fillable = [
        'content',
        'user_id',
        'task_id',
        'parent_id',
    ];

    public function author(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(): BelongsTo {
        return $this->belongsTo(Task::class);
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
