<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'invited_by',
        'email',
        'status',
        'token',
    ];

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function inviter(): BelongsTo {
        return $this->belongsTo(User::class, 'invited_by');
    }
}